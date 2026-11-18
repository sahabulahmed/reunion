<?php
// api/register.php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/RegistrationService.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// CSRF check
$csrf = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'নিরাপত্তা টোকেন (CSRF) ত্রুটি! পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।']);
    exit;
}

$proofFile = $_FILES['payment_proof'] ?? null;

$service = new RegistrationService();
$result = $service->register($_POST, $proofFile);

if (!$result['success']) {
    http_response_code(422);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

