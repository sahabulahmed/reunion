<?php
// config/app.php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Start secure session if not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

// Helper: base URL
function getBaseUrl(): string {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || ($_SERVER['SERVER_PORT'] ?? 80) == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    // Check if under /reunion/
    $dir = dirname($script);
    $dir = str_replace('\\', '/', $dir);
    // Find base path up to root of project
    $pos = strpos($dir, '/reunion');
    if ($pos !== false) {
        return $protocol . $host . substr($dir, 0, $pos + 8);
    }
    if ($dir === '/' || $dir === '\\' || $dir === '.' || $dir === '/api') {
        return $protocol . $host;
    }
    return $protocol . $host . $dir;
}

// Global settings cache
function getSetting(string $key, string $default = ''): string {
    static $settingsCache = null;
    if ($settingsCache === null) {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM event_settings WHERE tenant_id = ?");
            $stmt->execute([DEFAULT_TENANT_ID]);
            $settingsCache = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settingsCache = [];
        }
    }
    return $settingsCache[$key] ?? $default;
}

function updateSetting(string $key, string $value): bool {
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("UPDATE event_settings SET setting_value = ? WHERE setting_key = ? AND tenant_id = ?");
        return $stmt->execute([$value, $key, DEFAULT_TENANT_ID]);
    } catch (Exception $e) {
        error_log("Failed to update setting {$key}: " . $e->getMessage());
        return false;
    }
}

