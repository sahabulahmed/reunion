<?php
// api/index.php - Serverless Gateway Router for Vercel

$rootDir = dirname(__DIR__);

$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$path = parse_url($requestUri, PHP_URL_PATH) ?? '/';
$path = trim($path, '/');

// 1. Root / homepage
if (empty($path) || $path === 'index.php') {
    require $rootDir . '/index.php';
    exit;
}

// Security: sanitize path
$cleanPath = str_replace(['..', "\0"], '', $path);
$targetFile = $rootDir . '/' . $cleanPath;

// 2. Direct PHP file (e.g., receipt.php, auth/login.php, api/register.php)
if (is_file($targetFile) && pathinfo($targetFile, PATHINFO_EXTENSION) === 'php') {
    require $targetFile;
    exit;
}

// 3. Extensionless PHP URL (e.g., /receipt -> /receipt.php)
if (is_file($targetFile . '.php')) {
    require $targetFile . '.php';
    exit;
}

// 4. Directory index (e.g., /verification/ -> /verification/index.php)
if (is_dir($targetFile)) {
    if (is_file($targetFile . '/index.php')) {
        require $targetFile . '/index.php';
        exit;
    }
    if (is_file($targetFile . '/dashboard.php')) {
        require $targetFile . '/dashboard.php';
        exit;
    }
}

// 5. Fallback: load index.php
require $rootDir . '/index.php';
