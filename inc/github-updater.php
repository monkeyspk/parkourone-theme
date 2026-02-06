<?php
/**
 * ParkourONE Theme - GitHub Auto-Updater
 * Automatische Updates direkt von GitHub
 */

if (!defined('ABSPATH')) exit;

class ParkourONE_GitHub_Updater {
    
    private $github_repo = 'monkeyspk/parkourone-theme';
    private $theme_slug = 'parkourone-theme';
    private $check_interval = 43200; // 12 Stunden in Sekunden
    private $transient_key = 'parkourone_github_update_check';
    private $last_error = null;
    
    public function __construct() {
        // Nur im Admin
        if (!is_admin()) {
            return;
        }

        // Auto-Update Check bei Admin-Seiten (nicht bei AJAX)
        if (!wp_doing_ajax()) {
            add_action('admin_init', [$this, 'maybe_auto_update']);
        }

        // Admin-Seite für manuelle Updates
        add_action('admin_menu', [$this, 'add_admin_page']);

        // Manueller Update-Check Handler
        add_action('admin_init', [$this, 'handle_manual_check']);

        // Cache-Löschen Handler
        add_action('admin_init', [$this, 'handle_cache_clear']);

        // Info im Admin anzeigen
        add_action('admin_notices', [$this, 'show_update_notice']);
    }

    /**
     * Cache-Löschen Handler
     */
    public function handle_cache_clear() {
        if (!isset($_POST['parkourone_clear_cache'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['parkourone_cache_nonce'] ?? '', 'parkourone_clear_cache')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $cleared = [];

        // 0. PHP OPcache leeren (wichtig für Dateiänderungen!)
        if (function_exists('opcache_reset')) {
            opcache_reset();
            $cleared[] = 'OPcache';
        }

        // 1. WordPress Object Cache
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
            $cleared[] = 'Object Cache';
        }

        // 2. Alle Transients löschen
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_site_transient_%'");
        $cleared[] = 'Transients';

        // 3. WP Super Cache
        if (function_exists('wp_cache_clear_cache')) {
            wp_cache_clear_cache();
            $cleared[] = 'WP Super Cache';
        }

        // 4. W3 Total Cache
        if (function_exists('w3tc_flush_all')) {
            w3tc_flush_all();
            $cleared[] = 'W3 Total Cache';
        }

        // 5. WP Fastest Cache
        if (function_exists('wpfc_clear_all_cache')) {
            wpfc_clear_all_cache();
            $cleared[] = 'WP Fastest Cache';
        }

        // 6. LiteSpeed Cache
        if (class_exists('LiteSpeed_Cache_API') && method_exists('LiteSpeed_Cache_API', 'purge_all')) {
            LiteSpeed_Cache_API::purge_all();
            $cleared[] = 'LiteSpeed Cache';
        }

        // 7. Autoptimize
        if (class_exists('autoptimizeCache') && method_exists('autoptimizeCache', 'clearall')) {
            autoptimizeCache::clearall();
            $cleared[] = 'Autoptimize';
        }

        // 8. Elementor Cache (falls vorhanden)
        if (did_action('elementor/loaded')) {
            \Elementor\Plugin::$instance->files_manager->clear_cache();
            $cleared[] = 'Elementor';
        }

        // Redirect mit Erfolgs-Parameter (admin.php für custom menu pages)
        wp_redirect(add_query_arg([
            'page' => 'parkourone-updates',
            'cache_cleared' => '1',
            'cleared' => implode(',', $cleared)
        ], admin_url('admin.php')));
        exit;
    }

    /**
     * Admin-Seite hinzufügen
     * Hinweis: Die Menü-Registrierung erfolgt jetzt in inc/admin-menu.php
     * Diese Methode bleibt für Kompatibilität, registriert aber kein eigenes Menü mehr
     */
    public function add_admin_page() {
        // Menü wird jetzt zentral in inc/admin-menu.php registriert
    }

    /**
     * Admin-Seite rendern
     */
    public function render_admin_page() {
        $local_version = $this->get_local_version();
        $remote_version = $this->get_remote_version();
        $last_update = get_option('parkourone_last_update');
        $last_check = get_transient($this->transient_key);

        $is_up_to_date = ($local_version === $remote_version);
        $next_check_in = $last_check ? human_time_diff(time(), $last_check + $this->check_interval) : 'Jetzt';

        ?>
        <div class="wrap">
            <h1>ParkourONE Theme Updates</h1>

            <?php
            // Feedback-Nachrichten basierend auf Query-Parametern
            if (isset($_GET['updated']) && $_GET['updated'] === '1') {
                $version = isset($_GET['version']) ? sanitize_text_field($_GET['version']) : '';
                echo '<div class="notice notice-success is-dismissible"><p><strong>Erfolg!</strong> Theme wurde auf Version <code>' . esc_html($version) . '</code> aktualisiert.</p></div>';
            } elseif (isset($_GET['uptodate']) && $_GET['uptodate'] === '1') {
                $version = isset($_GET['version']) ? sanitize_text_field($_GET['version']) : '';
                echo '<div class="notice notice-info is-dismissible"><p>Theme ist bereits aktuell (Version: <code>' . esc_html($version) . '</code>)</p></div>';
            } elseif (isset($_GET['error'])) {
                if ($_GET['error'] === 'connection') {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Fehler:</strong> Konnte GitHub nicht erreichen. Siehe Debug-Informationen unten.</p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>Fehler:</strong> Update fehlgeschlagen. Siehe Error-Log für Details.</p></div>';
                }
            }
            // Cache gelöscht Nachricht
            if (isset($_GET['cache_cleared']) && $_GET['cache_cleared'] === '1') {
                $cleared = isset($_GET['cleared']) ? sanitize_text_field($_GET['cleared']) : '';
                echo '<div class="notice notice-success is-dismissible"><p><strong>Cache gelöscht!</strong> Folgende Caches wurden geleert: ' . esc_html($cleared) . '</p></div>';
            }
            ?>

            <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); max-width: 600px; margin-top: 20px;">

                <h2 style="margin-top: 0;">Status</h2>

                <table class="form-table" style="margin: 0;">
                    <tr>
                        <th>Lokale Version:</th>
                        <td><code style="font-size: 14px;"><?php echo esc_html($local_version); ?></code></td>
                    </tr>
                    <tr>
                        <th>GitHub Version:</th>
                        <td>
                            <code style="font-size: 14px;"><?php echo esc_html($remote_version ?: 'Nicht erreichbar'); ?></code>
                            <?php if ($is_up_to_date && $remote_version): ?>
                                <span style="color: #46b450; margin-left: 10px;">&#10003; Aktuell</span>
                            <?php elseif ($remote_version): ?>
                                <span style="color: #dc3232; margin-left: 10px;">&#8593; Update verfügbar</span>
                            <?php endif; ?>
                            <?php if (!$remote_version && $this->last_error): ?>
                                <br><small style="color: #dc3232;">Fehler: <?php echo esc_html($this->last_error); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Letztes Update:</th>
                        <td>
                            <?php if ($last_update): ?>
                                <?php echo esc_html($last_update['time']); ?>
                                <span style="color: #666;">(<?php echo human_time_diff(strtotime($last_update['time']), current_time('timestamp')); ?> her)</span>
                            <?php else: ?>
                                <span style="color: #666;">Noch kein Update durchgeführt</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>Nächster Auto-Check:</th>
                        <td>in <?php echo esc_html($next_check_in); ?></td>
                    </tr>
                </table>

                <hr style="margin: 20px 0;">

                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('parkourone_manual_update', 'parkourone_nonce'); ?>
                    <button type="submit" name="parkourone_check_update" class="button button-primary" style="margin-right: 10px;">
                        Jetzt prüfen & aktualisieren
                    </button>
                </form>

                <a href="https://github.com/<?php echo esc_attr($this->github_repo); ?>/commits/main" target="_blank" class="button">
                    GitHub Commits ansehen
                </a>

                <p style="margin-top: 15px; color: #666; font-size: 13px;">
                    Das Theme prüft automatisch alle 12 Stunden auf Updates und aktualisiert sich selbst.
                </p>

                <hr style="margin: 20px 0;">

                <h3 style="margin-top: 0;">Cache leeren</h3>
                <p style="color: #666; font-size: 13px; margin-bottom: 15px;">
                    Löscht alle WordPress-Caches (Object Cache, Transients, Plugin-Caches).
                    Nützlich wenn Änderungen nicht sichtbar werden.
                </p>

                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('parkourone_clear_cache', 'parkourone_cache_nonce'); ?>
                    <button type="submit" name="parkourone_clear_cache" class="button" style="background: #d63638; border-color: #d63638; color: #fff;">
                        Alle Caches leeren
                    </button>
                </form>

                <?php if (!$remote_version): ?>
                <hr style="margin: 20px 0;">
                <details style="font-size: 13px; color: #666;">
                    <summary style="cursor: pointer; font-weight: 600;">Debug-Informationen</summary>
                    <div style="margin-top: 10px; padding: 10px; background: #f5f5f5; border-radius: 4px;">
                        <p><strong>GitHub Repo:</strong> <?php echo esc_html($this->github_repo); ?></p>
                        <p><strong>API URL:</strong> https://api.github.com/repos/<?php echo esc_html($this->github_repo); ?>/commits/main</p>
                        <p><strong>Fehler:</strong> <?php echo esc_html($this->last_error ?: 'Unbekannt'); ?></p>
                        <p style="margin-top: 10px;"><strong>Mögliche Ursachen:</strong></p>
                        <ul style="margin-left: 20px;">
                            <li>Server blockiert ausgehende Verbindungen</li>
                            <li>GitHub API Rate Limit erreicht</li>
                            <li>SSL-Zertifikat Problem</li>
                            <li>Repository ist privat (muss public sein)</li>
                        </ul>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Manuellen Update-Check verarbeiten
     */
    public function handle_manual_check() {
        if (!isset($_POST['parkourone_check_update'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['parkourone_nonce'] ?? '', 'parkourone_manual_update')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Transient löschen um sofortigen Check zu erzwingen
        delete_transient($this->transient_key);

        // Update durchführen
        $remote_version = $this->get_remote_version();
        $local_version = $this->get_local_version();

        $redirect_args = ['page' => 'parkourone-updates'];
        $was_updated = false;

        if ($remote_version && $remote_version !== $local_version) {
            $result = $this->do_update();
            if ($result) {
                $redirect_args['updated'] = '1';
                $redirect_args['version'] = $remote_version;
                $was_updated = true;
            } else {
                $redirect_args['error'] = '1';
            }
        } else if (!$remote_version) {
            $redirect_args['error'] = 'connection';
        } else {
            $redirect_args['uptodate'] = '1';
            $redirect_args['version'] = $local_version;
        }

        // Transient neu setzen
        set_transient($this->transient_key, time(), $this->check_interval);

        // Nach Update: Zur Dashboard-Seite redirecten (sicherer nach Datei-Ersetzung)
        // Dann zeigt admin_notices dort die Erfolgs-Meldung
        if ($was_updated) {
            set_transient('parkourone_update_success', $remote_version, 60);
            wp_redirect(admin_url('index.php'));
        } else {
            wp_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        }
        exit;
    }
    
    /**
     * Prüft und führt Auto-Update durch
     */
    public function maybe_auto_update() {
        // Nur prüfen wenn Interval abgelaufen
        $last_check = get_transient($this->transient_key);
        
        if ($last_check !== false) {
            return; // Noch nicht Zeit für neue Prüfung
        }
        
        // Transient setzen für nächstes Interval
        set_transient($this->transient_key, time(), $this->check_interval);
        
        // GitHub Version holen
        $remote_version = $this->get_remote_version();
        
        if (!$remote_version) {
            return; // Konnte GitHub nicht erreichen
        }
        
        // Lokale Version holen
        $local_version = $this->get_local_version();
        
        // Vergleichen und updaten
        if ($remote_version !== $local_version) {
            $this->do_update();
        }
    }
    
    /**
     * Holt den neuesten Commit SHA von GitHub
     */
    private function get_remote_version() {
        $api_url = "https://api.github.com/repos/{$this->github_repo}/commits/main";

        error_log('ParkourONE Updater: Versuche GitHub API zu erreichen: ' . $api_url);

        // Erst mit SSL versuchen
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WordPress/ParkourONE-Theme-Updater'
            ],
            'timeout' => 20,
            'sslverify' => true
        ]);

        // Fallback ohne SSL-Verifikation
        if (is_wp_error($response)) {
            error_log('ParkourONE Updater: SSL-Versuch fehlgeschlagen: ' . $response->get_error_message());
            error_log('ParkourONE Updater: Versuche ohne SSL-Verifikation...');

            $response = wp_remote_get($api_url, [
                'headers' => [
                    'Accept' => 'application/vnd.github.v3+json',
                    'User-Agent' => 'WordPress/ParkourONE-Theme-Updater'
                ],
                'timeout' => 20,
                'sslverify' => false
            ]);
        }

        if (is_wp_error($response)) {
            $this->last_error = $response->get_error_message();
            error_log('ParkourONE Updater: Endgültiger Fehler: ' . $this->last_error);
            return false;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        error_log('ParkourONE Updater: HTTP Response Code: ' . $response_code);

        if ($response_code !== 200) {
            $body = wp_remote_retrieve_body($response);
            $this->last_error = 'HTTP ' . $response_code . ' - ' . substr($body, 0, 200);
            error_log('ParkourONE Updater: ' . $this->last_error);
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['sha'])) {
            $this->last_error = null;
            error_log('ParkourONE Updater: Erfolgreich! SHA: ' . substr($body['sha'], 0, 7));
            return substr($body['sha'], 0, 7);
        }

        $this->last_error = 'Keine SHA in Antwort gefunden';
        error_log('ParkourONE Updater: ' . $this->last_error);
        return false;
    }

    /**
     * Gibt den letzten Fehler zurück
     */
    public function get_last_error() {
        return $this->last_error ?? null;
    }
    
    /**
     * Holt die lokale Version (gespeicherter Commit Hash)
     */
    private function get_local_version() {
        $version_file = get_template_directory() . '/.git-version';
        
        if (file_exists($version_file)) {
            return trim(file_get_contents($version_file));
        }
        
        return 'unknown';
    }
    
    /**
     * Speichert die aktuelle Version
     */
    private function save_local_version($version) {
        $version_file = get_template_directory() . '/.git-version';
        file_put_contents($version_file, $version);
    }
    
    /**
     * Führt das Update durch
     */
    private function do_update() {
        // WP_Filesystem initialisieren
        global $wp_filesystem;

        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        // Filesystem mit direktem Zugriff initialisieren
        if (!WP_Filesystem(false, false, true)) {
            error_log('ParkourONE Updater: WP_Filesystem konnte nicht initialisiert werden');
            return false;
        }

        // ZIP von GitHub holen
        $zip_url = "https://github.com/{$this->github_repo}/archive/refs/heads/main.zip";

        $temp_file = download_url($zip_url);

        if (is_wp_error($temp_file)) {
            error_log('ParkourONE Updater: Download fehlgeschlagen - ' . $temp_file->get_error_message());
            return false;
        }

        // Theme-Verzeichnis
        $theme_dir = get_template_directory();
        $themes_dir = dirname($theme_dir);
        $temp_dir = $themes_dir . '/parkourone-theme-temp-' . time();

        // ZIP entpacken - erst WP versuchen, dann native PHP ZipArchive
        $unzip_result = unzip_file($temp_file, $temp_dir);

        if (is_wp_error($unzip_result)) {
            error_log('ParkourONE Updater: WP unzip fehlgeschlagen - ' . $unzip_result->get_error_message());
            error_log('ParkourONE Updater: Versuche native PHP ZipArchive...');

            // Fallback: Native PHP ZipArchive
            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($temp_file) === true) {
                    @mkdir($temp_dir, 0755, true);
                    $zip->extractTo($temp_dir);
                    $zip->close();
                    $unzip_result = true;
                    error_log('ParkourONE Updater: Native ZipArchive erfolgreich');
                } else {
                    error_log('ParkourONE Updater: Native ZipArchive konnte ZIP nicht öffnen');
                }
            } else {
                error_log('ParkourONE Updater: ZipArchive Klasse nicht verfügbar');
            }
        }

        @unlink($temp_file);

        if (is_wp_error($unzip_result) || $unzip_result !== true) {
            error_log('ParkourONE Updater: Entpacken endgültig fehlgeschlagen');
            $this->remove_directory($temp_dir);
            return false;
        }
        
        // GitHub erstellt Ordner mit "repo-branch" Namen
        $extracted_dir = $temp_dir . '/parkourone-theme-main';
        
        if (!is_dir($extracted_dir)) {
            error_log('ParkourONE Updater: Extrahierter Ordner nicht gefunden');
            $this->remove_directory($temp_dir);
            return false;
        }
        
        // Alte Dateien löschen (außer .git-version)
        $this->clean_theme_directory($theme_dir);
        
        // Neue Dateien kopieren
        $this->copy_directory($extracted_dir, $theme_dir);
        
        // Temp-Ordner aufräumen
        $this->remove_directory($temp_dir);
        
        // Neue Version speichern
        $new_version = $this->get_remote_version();
        if ($new_version) {
            $this->save_local_version($new_version);
        }
        
        // Update-Log
        update_option('parkourone_last_update', [
            'time' => current_time('mysql'),
            'version' => $new_version
        ]);
        
        error_log('ParkourONE Updater: Theme erfolgreich auf ' . $new_version . ' aktualisiert');

        // OPcache leeren nach Update (wichtig!)
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }

        return true;
    }
    
    /**
     * Löscht Theme-Dateien (behält .git-version)
     */
    private function clean_theme_directory($dir) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $file) {
            // .git-version behalten
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
     * Kopiert Verzeichnis rekursiv
     */
    private function copy_directory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst, 0755, true);
        
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;
            
            $src_path = $src . '/' . $file;
            $dst_path = $dst . '/' . $file;
            
            if (is_dir($src_path)) {
                $this->copy_directory($src_path, $dst_path);
            } else {
                copy($src_path, $dst_path);
            }
        }
        
        closedir($dir);
    }
    
    /**
     * Löscht Verzeichnis rekursiv
     */
    private function remove_directory($dir) {
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
    
    /**
     * Zeigt Info über letztes Update
     */
    public function show_update_notice() {
        // Erfolgs-Nachricht nach Update anzeigen (auf jeder Admin-Seite)
        $update_success = get_transient('parkourone_update_success');
        if ($update_success) {
            delete_transient('parkourone_update_success');
            echo '<div class="notice notice-success is-dismissible"><p>';
            echo '<strong>ParkourONE Theme erfolgreich aktualisiert!</strong> ';
            echo 'Neue Version: <code>' . esc_html($update_success) . '</code>';
            echo ' <a href="' . esc_url(admin_url('admin.php?page=parkourone-updates')) . '">→ Zum Theme Updater</a>';
            echo '</p></div>';
        }

        // Standard-Info auf der Settings-Seite
        $last_update = get_option('parkourone_last_update');
        if ($last_update && isset($_GET['page']) && $_GET['page'] === 'parkourone-settings') {
            $time = human_time_diff(strtotime($last_update['time']), current_time('timestamp'));
            echo '<div class="notice notice-success"><p>';
            echo '<strong>ParkourONE Theme:</strong> Letztes Auto-Update vor ' . $time;
            echo ' (Version: ' . esc_html($last_update['version']) . ')';
            echo '</p></div>';
        }
    }
}

// Initialisieren
new ParkourONE_GitHub_Updater();
