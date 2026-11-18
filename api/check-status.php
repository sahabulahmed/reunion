<?php
// api/check-status.php

declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/RegistrationService.php';

$query = trim($_GET['q'] ?? $_POST['q'] ?? '');
if (empty($query)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'অনুগ্রহ করে রেজিস্ট্রেশন কোড বা মোবাইল নম্বর লিখুন।']);
    exit;
}

$service = new RegistrationService();
$reg = $service->findByCodeOrPhone($query);

if (!$reg) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'কোনো নিবন্ধন তথ্য পাওয়া যায়নি। অনুগ্রহ করে সঠিক কোড বা নম্বর দিন।']);
    exit;
}

echo json_encode([
    'success' => true,
    'data' => [
        'registration_code'   => $reg['registration_code'],
        'student_name'        => $reg['student_name'],
        'student_category'    => $reg['student_category'] === CAT_FORMER ? 'সাবেক শিক্ষার্থী' : 'বর্তমান শিক্ষার্থী',
        'mobile_number'       => substr($reg['mobile_number'], 0, 3) . '****' . substr($reg['mobile_number'], -4),
        'payment_status'      => $reg['payment_status'],
        'registration_status' => $reg['registration_status'],
        'total_fee'           => formatTaka($reg['total_fee']),
        'payment_method'      => strtoupper($reg['payment_method'] ?? ''),
        'transaction_id'      => $reg['transaction_id'] ?? '',
        'receipt_url'         => $reg['payment_status'] === PAY_APPROVED ? getBaseUrl() . '/receipt.php?code=' . urlencode($reg['registration_code']) : null,
        'verification_url'    => getBaseUrl() . '/verification/index.php?token=' . urlencode($reg['qr_token']),
        'created_at'          => date('d M Y, h:i A', strtotime($reg['created_at'])),
        'remarks'             => $reg['approval_remarks'] ?? null
    ]
], JSON_UNESCAPED_UNICODE);

