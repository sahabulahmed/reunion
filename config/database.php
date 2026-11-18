<?php
// config/database.php

declare(strict_types=1);

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = getenv('DB_HOST') ?: ($_SERVER['DB_HOST'] ?? ($_ENV['DB_HOST'] ?? '127.0.0.1'));
            $port = getenv('DB_PORT') ?: ($_SERVER['DB_PORT'] ?? ($_ENV['DB_PORT'] ?? '3306'));
            $db   = getenv('DB_DATABASE') ?: ($_SERVER['DB_DATABASE'] ?? ($_ENV['DB_DATABASE'] ?? 'reunion'));
            $user = getenv('DB_USERNAME') ?: ($_SERVER['DB_USERNAME'] ?? ($_ENV['DB_USERNAME'] ?? 'root'));
            $pass = getenv('DB_PASSWORD') ?: ($_SERVER['DB_PASSWORD'] ?? ($_ENV['DB_PASSWORD'] ?? ''));
            $charset = 'utf8mb4';

            $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            ];

            // If connecting to remote cloud database (e.g. TiDB Cloud / Aiven), enable SSL
            if ($host !== '127.0.0.1' && $host !== 'localhost') {
                $options[PDO::MYSQL_ATTR_SSL_CA] = true;
                $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
            }

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                if (isset($_GET['debug'])) {
                    die("DB Error: " . $e->getMessage() . " | Resolved Host: " . var_export($host, true) . " | getenv: " . var_export(getenv('DB_HOST'), true) . " | SERVER: " . var_export($_SERVER['DB_HOST'] ?? null, true));
                }
                die("ডাটাবেজ সংযোগে সমস্যা হয়েছে। দয়া করে কিছুক্ষণ পর আবার চেষ্টা করুন।");
            }
        }
        return self::$instance;
    }
}

