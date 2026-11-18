<?php
// app/Helpers/functions.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';

/**
 * Escape HTML
 */
function e(?string $string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Generate or get CSRF token
 */
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * CSRF HTML Input
 */
function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf_token(?string $token): bool {
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Flash message helpers
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type' => $type, // 'success', 'danger', 'warning', 'info'
        'message' => $message,
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * English to Bengali numbers conversion
 */
function en2bn(mixed $number): string {
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    return str_replace($en, $bn, (string)$number);
}

/**
 * Bengali to English numbers conversion
 */
function bn2en(mixed $number): string {
    $bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
    $en = ['0','1','2','3','4','5','6','7','8','9'];
    return str_replace($bn, $en, (string)$number);
}

/**
 * Format currency with Bengali Taka sign
 */
function formatTaka(float|int|string $amount): string {
    $formatted = number_format((float)$amount, 0);
    return '৳ ' . en2bn($formatted);
}

/**
 * Client IP address
 */
function getClientIp(): string {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
}

/**
 * Audit Logger
 */
function logAudit(string $action, string $entityType, ?string $entityId = null, ?array $oldValues = null, ?array $newValues = null): void {
    try {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("
            INSERT INTO audit_logs (tenant_id, user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $userId = $_SESSION['user_id'] ?? null;
        $ip = getClientIp();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $stmt->execute([
            DEFAULT_TENANT_ID,
            $userId,
            $action,
            $entityType,
            $entityId,
            $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
            $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
            $ip,
            $ua
        ]);
    } catch (Exception $e) {
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

/**
 * Current user array
 */
function currentUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'       => $_SESSION['user_id'] ?? null,
        'name'     => $_SESSION['user_name'] ?? '',
        'username' => $_SESSION['user_username'] ?? '',
        'role'     => $_SESSION['user_role'] ?? '',
        'role_id'  => $_SESSION['user_role_id'] ?? 0,
    ];
}

/**
 * Check permission for current user
 */
function hasPermission(string $permissionName): bool {
    if (!isLoggedIn()) {
        return false;
    }
    // Main Admin always has all permissions
    if (($_SESSION['user_role'] ?? '') === 'admin') {
        return true;
    }

    $userPerms = $_SESSION['user_permissions'] ?? [];
    return in_array($permissionName, $userPerms, true);
}

/**
 * Require permission or redirect/abort
 */
function requirePermission(string $permissionName): void {
    if (!isLoggedIn()) {
        header('Location: ' . getBaseUrl() . '/auth/login.php');
        exit;
    }

    if (!hasPermission($permissionName)) {
        http_response_code(403);
        require_once APP_ROOT . '/auth/unauthorized.php';
        exit;
    }
}

