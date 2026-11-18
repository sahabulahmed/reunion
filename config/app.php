<?php
// config/app.php

declare(strict_types=1);

require_once __DIR__ . '/constants.php';
require_once __DIR__ . '/database.php';

if (file_exists(APP_ROOT . '/vendor/autoload.php')) {
    require_once APP_ROOT . '/vendor/autoload.php';
}

// Database-backed Session Handler for multi-instance / Serverless persistence (e.g. Vercel)
class DatabaseSessionHandler implements SessionHandlerInterface {
    private function getDb(): ?PDO {
        try {
            return Database::getConnection();
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        $db = $this->getDb();
        if (!$db) return '';
        try {
            $stmt = $db->prepare("SELECT data FROM sessions WHERE id = ? AND expires_at > ? LIMIT 1");
            $stmt->execute([$id, time()]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string)$data : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        $db = $this->getDb();
        if (!$db) return false;
        try {
            $expires = time() + (86400 * 7); // 7 days session
            $stmt = $db->prepare("REPLACE INTO sessions (id, data, expires_at) VALUES (?, ?, ?)");
            return $stmt->execute([$id, $data, $expires]);
        } catch (\Throwable $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool {
        $db = $this->getDb();
        if (!$db) return true;
        try {
            $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        $db = $this->getDb();
        if (!$db) return 0;
        try {
            $stmt = $db->prepare("DELETE FROM sessions WHERE expires_at < ?");
            $stmt->execute([time()]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}

// Start secure session if not started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_samesite', 'Lax');

    $handler = new DatabaseSessionHandler();
    session_set_save_handler($handler, true);
    register_shutdown_function('session_write_close');

    session_start();
}

// Helper: base URL
function getBaseUrl(): string {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['SERVER_PORT'] ?? 80) == 443)
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $protocol = $isHttps ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    
    // Check if hosted under local XAMPP subfolder /reunion
    $pos = stripos($script, '/reunion');
    if ($pos !== false) {
        return $protocol . $host . substr($script, 0, $pos + 8);
    }
    
    // On Vercel or any root domain/subdomain, base URL is the domain root
    return $protocol . $host;
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

