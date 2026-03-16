<?php
/**
 * ParkourONE GitHub Webhook Auto-Updater
 *
 * Empfängt Push-Events von GitHub und aktualisiert Theme/Plugins sofort.
 * Konfiguration: GITHUB_WEBHOOK_SECRET in wp-config.php definieren.
 */

if (!defined('ABSPATH')) exit;

add_action('rest_api_init', function () {
    register_rest_route('parkourone/v1', '/github-webhook', [
        'methods'             => 'POST',
        'callback'            => 'parkourone_handle_github_webhook',
        'permission_callback' => '__return_true',
    ]);
});

/**
 * Webhook-Handler
 */
function parkourone_handle_github_webhook(WP_REST_Request $request) {
    // Secret prüfen
    if (!defined('GITHUB_WEBHOOK_SECRET') || !GITHUB_WEBHOOK_SECRET) {
        error_log('ParkourONE Webhook: GITHUB_WEBHOOK_SECRET nicht definiert');
        return new WP_REST_Response(['error' => 'Server not configured'], 500);
    }

    // HMAC-Signatur verifizieren
    $signature = $request->get_header('X-Hub-Signature-256');
    if (!$signature) {
        return new WP_REST_Response(['error' => 'Missing signature'], 403);
    }

    $payload = $request->get_body();
    $expected = 'sha256=' . hash_hmac('sha256', $payload, GITHUB_WEBHOOK_SECRET);

    if (!hash_equals($expected, $signature)) {
        error_log('ParkourONE Webhook: Ungültige Signatur');
        return new WP_REST_Response(['error' => 'Invalid signature'], 403);
    }

    // Nur Push-Events verarbeiten
    $event = $request->get_header('X-GitHub-Event');
    if ($event === 'ping') {
        return new WP_REST_Response(['message' => 'pong'], 200);
    }
    if ($event !== 'push') {
        return new WP_REST_Response(['message' => 'Ignored event: ' . $event], 200);
    }

    // Nur main-Branch
    $body = $request->get_json_params();
    $ref  = $body['ref'] ?? '';
    if ($ref !== 'refs/heads/main') {
        return new WP_REST_Response(['message' => 'Ignored ref: ' . $ref], 200);
    }

    // Repo identifizieren
    $repo_full_name = $body['repository']['full_name'] ?? '';
    $repo_map       = parkourone_get_repo_map();

    if (!isset($repo_map[$repo_full_name])) {
        error_log('ParkourONE Webhook: Unbekanntes Repo: ' . $repo_full_name);
        return new WP_REST_Response(['error' => 'Unknown repository'], 400);
    }

    $config     = $repo_map[$repo_full_name];
    $head_sha   = substr($body['after'] ?? '', 0, 7);
    $repo_name  = explode('/', $repo_full_name)[1];

    error_log("ParkourONE Webhook: Push empfangen für {$repo_full_name} ({$head_sha})");

    // Update durchführen
    $result = parkourone_webhook_do_update($repo_full_name, $repo_name, $config['target_dir'], $head_sha);

    if (is_wp_error($result)) {
        error_log('ParkourONE Webhook: Update fehlgeschlagen - ' . $result->get_error_message());
        return new WP_REST_Response([
            'error'      => $result->get_error_message(),
            'repository' => $repo_full_name,
        ], 500);
    }

    error_log("ParkourONE Webhook: Updated {$repo_name} to {$head_sha}");

    return new WP_REST_Response([
        'message'    => 'Updated successfully',
        'repository' => $repo_full_name,
        'type'       => $config['type'],
        'version'    => $head_sha,
    ], 200);
}

/**
 * Repo → Zielverzeichnis Mapping
 */
function parkourone_get_repo_map() {
    return [
        'monkeyspk/parkourone-theme' => [
            'type'       => 'theme',
            'target_dir' => get_template_directory(),
        ],
        'monkeyspk/custom-events-plugin' => [
            'type'       => 'plugin',
            'target_dir' => WP_PLUGIN_DIR . '/custom-events-plugin',
        ],
        'monkeyspk/ab-webhook-endpoint' => [
            'type'       => 'plugin',
            'target_dir' => WP_PLUGIN_DIR . '/ab-webhook-endpoint',
        ],
    ];
}

/**
 * Update durchführen: ZIP downloaden, entpacken, Dateien ersetzen
 */
function parkourone_webhook_do_update($repo_full_name, $repo_name, $target_dir, $version) {
    if (!function_exists('WP_Filesystem')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    if (!function_exists('download_url')) {
        require_once ABSPATH . 'wp-admin/includes/file.php';
    }

    WP_Filesystem(false, false, true);

    // ZIP downloaden
    $zip_url   = "https://github.com/{$repo_full_name}/archive/refs/heads/main.zip";
    $temp_file = download_url($zip_url);

    if (is_wp_error($temp_file)) {
        return $temp_file;
    }

    // Temp-Verzeichnis zum Entpacken
    $temp_dir = dirname($target_dir) . '/' . $repo_name . '-temp-' . time();

    // Entpacken (WP, dann ZipArchive Fallback)
    $unzip_result = unzip_file($temp_file, $temp_dir);

    if (is_wp_error($unzip_result)) {
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($temp_file) === true) {
                @mkdir($temp_dir, 0755, true);
                $zip->extractTo($temp_dir);
                $zip->close();
                $unzip_result = true;
            }
        }
    }

    @unlink($temp_file);

    if (is_wp_error($unzip_result) || $unzip_result !== true) {
        parkourone_webhook_remove_dir($temp_dir);
        return new WP_Error('unzip_failed', 'ZIP konnte nicht entpackt werden');
    }

    // GitHub erstellt Ordner: "repo-name-main"
    $extracted_dir = $temp_dir . '/' . $repo_name . '-main';

    if (!is_dir($extracted_dir)) {
        parkourone_webhook_remove_dir($temp_dir);
        return new WP_Error('extract_missing', 'Extrahierter Ordner nicht gefunden');
    }

    // Zielverzeichnis erstellen falls nötig (neues Plugin)
    if (!is_dir($target_dir)) {
        @mkdir($target_dir, 0755, true);
    }

    // Alte Dateien löschen (.git-version behalten)
    parkourone_webhook_clean_dir($target_dir);

    // Neue Dateien kopieren
    parkourone_webhook_copy_dir($extracted_dir, $target_dir);

    // Temp aufräumen
    parkourone_webhook_remove_dir($temp_dir);

    // .git-version aktualisieren
    file_put_contents($target_dir . '/.git-version', $version);

    // OPcache leeren
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }

    // Update loggen
    $log = get_option('parkourone_webhook_updates', []);
    $log[] = [
        'repo'    => $repo_full_name,
        'version' => $version,
        'time'    => current_time('mysql'),
    ];
    // Nur letzte 50 Einträge behalten
    $log = array_slice($log, -50);
    update_option('parkourone_webhook_updates', $log);

    // Auch parkourone_last_update aktualisieren (für Theme-Updater Admin-Seite)
    if ($repo_full_name === 'monkeyspk/parkourone-theme') {
        update_option('parkourone_last_update', [
            'time'    => current_time('mysql'),
            'version' => $version,
        ]);
        // Transient zurücksetzen damit Updater nicht sofort nochmal prüft
        delete_transient('parkourone_github_update_check');
    }

    return true;
}

/**
 * Verzeichnis leeren (.git-version behalten)
 */
function parkourone_webhook_clean_dir($dir) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if (strpos($file->getPathname(), '.git-version') !== false) {
            continue;
        }
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
}

/**
 * Verzeichnis rekursiv kopieren
 */
function parkourone_webhook_copy_dir($src, $dst) {
    $dir = opendir($src);
    @mkdir($dst, 0755, true);

    while (($file = readdir($dir)) !== false) {
        if ($file === '.' || $file === '..') continue;

        $src_path = $src . '/' . $file;
        $dst_path = $dst . '/' . $file;

        if (is_dir($src_path)) {
            parkourone_webhook_copy_dir($src_path, $dst_path);
        } else {
            copy($src_path, $dst_path);
        }
    }
    closedir($dir);
}

/**
 * Verzeichnis rekursiv löschen
 */
function parkourone_webhook_remove_dir($dir) {
    if (!is_dir($dir)) return;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
        if ($file->isDir()) {
            @rmdir($file->getPathname());
        } else {
            @unlink($file->getPathname());
        }
    }
    @rmdir($dir);
}
