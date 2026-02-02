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
    
    public function __construct() {
        // Nur im Admin und nicht bei AJAX
        if (!is_admin() || wp_doing_ajax()) {
            return;
        }
        
        // Auto-Update Check bei Admin-Seiten
        add_action('admin_init', [$this, 'maybe_auto_update']);
        
        // Info im Admin anzeigen (optional)
        add_action('admin_notices', [$this, 'show_update_notice']);
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
        
        $response = wp_remote_get($api_url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'ParkourONE-Theme-Updater'
            ],
            'timeout' => 10
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (isset($body['sha'])) {
            return substr($body['sha'], 0, 7); // Kurzer Hash
        }
        
        return false;
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
        
        // ZIP entpacken
        $unzip_result = unzip_file($temp_file, $temp_dir);
        @unlink($temp_file);
        
        if (is_wp_error($unzip_result)) {
            error_log('ParkourONE Updater: Entpacken fehlgeschlagen - ' . $unzip_result->get_error_message());
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
