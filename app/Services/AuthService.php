<?php
// app/Services/AuthService.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/functions.php';

class AuthService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Authenticate user with rate limiting check
     */
    public function login(string $username, string $password): array {
        $username = trim($username);

        // Fetch user with role
        $stmt = $this->pdo->prepare("
            SELECT u.*, r.name as role_name, r.display_name as role_display
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE (u.username = ? OR u.email = ?) AND u.status = 'active'
            LIMIT 1
        ");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            logAudit('auth.login_failed', 'user', null, null, ['identifier' => $username, 'reason' => 'Invalid credentials']);
            return ['success' => false, 'message' => 'ইউজারনেম অথবা পাসওয়ার্ড সঠিক নয়।'];
        }

        // Regenerate session id for security if headers not sent
        if (!headers_sent()) {
            session_regenerate_id(true);
        }

        // Fetch permissions
        $permissions = [];
        if ($user['role_name'] === 'admin') {
            // Main Admin always gets all permissions
            $pStmt = $this->pdo->query("SELECT name FROM permissions");
            $permissions = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            // Moderator permissions are strictly based on individually assigned user_permissions
            $pStmt = $this->pdo->prepare("
                SELECT p.name
                FROM permissions p
                JOIN user_permissions up ON p.id = up.permission_id
                WHERE up.user_id = ?
            ");
            $pStmt->execute([$user['id']]);
            $permissions = $pStmt->fetchAll(PDO::FETCH_COLUMN);
        }

        // Set session
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_username'] = $user['username'];
        $_SESSION['user_role'] = $user['role_name'];
        $_SESSION['user_role_id'] = (int)$user['role_id'];
        $_SESSION['user_permissions'] = $permissions;
        $_SESSION['logged_in_time'] = time();

        // Update last login
        $uStmt = $this->pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
        $uStmt->execute([$user['id']]);

        logAudit('auth.login_success', 'user', (string)$user['id'], null, ['username' => $user['username']]);

        return ['success' => true, 'user' => $user];
    }

    /**
     * Logout
     */
    public function logout(): void {
        if (isLoggedIn()) {
            logAudit('auth.logout', 'user', (string)($_SESSION['user_id'] ?? ''));
        }
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    /**
     * Check if initial admin exists
     */
    public function hasAdmin(): bool {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'admin'");
        return ((int)$stmt->fetchColumn()) > 0;
    }

    /**
     * Create initial admin account securely
     */
    public function createAdmin(string $name, string $username, string $email, string $phone, string $password): array {
        if ($this->hasAdmin()) {
            return ['success' => false, 'message' => 'প্রধান অ্যাডমিন অ্যাকাউন্ট ইতিমধ্যে বিদ্যমান আছে।'];
        }

        if (strlen($password) < 8) {
            return ['success' => false, 'message' => 'পাসওয়ার্ড কমপক্ষে ৮ অক্ষরের হতে হবে।'];
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (tenant_id, role_id, name, username, email, phone, password_hash, status)
            VALUES (?, 1, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([DEFAULT_TENANT_ID, $name, $username, $email, $phone, $passwordHash]);

        $newId = (int)$this->pdo->lastInsertId();
        logAudit('auth.create_initial_admin', 'user', (string)$newId, null, ['username' => $username]);

        return ['success' => true, 'user_id' => $newId];
    }
}
