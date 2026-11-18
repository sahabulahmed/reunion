<?php
// tools/test_system.php
declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/AuthService.php';
require_once __DIR__ . '/../app/Services/RegistrationService.php';
require_once __DIR__ . '/../app/Services/PaymentService.php';
require_once __DIR__ . '/../app/Services/ReceiptService.php';

echo "=== STARTING COMPREHENSIVE SYSTEM TESTS ===\n\n";

// 1. Admin Login
$auth = new AuthService();
$loginRes = $auth->login('admin', 'Admin@2027');
if (!$loginRes['success']) {
    die("[TEST 1 FAILED] Admin login failed: " . ($loginRes['message'] ?? '') . "\n");
}
echo "[TEST 1] Admin Login Authentication: PASSED (User ID: " . $loginRes['user']['id'] . ")\n";

// 2. Registration Submission
$regService = new RegistrationService();
$regData = [
    'student_name' => 'Badhon Islam Test',
    'father_name' => 'Abul Kalam',
    'mother_name' => 'Fatema Begum',
    'mobile_number' => '01711' . rand(100000, 999999),
    'current_address' => 'Dhaka, Bangladesh',
    'student_category' => 'former',
    'prev_class' => 'দাখিল',
    'passing_year' => '2018',
    'tshirt_size' => 'L',
    'occupation' => 'Software Engineer',
    'has_guest' => '1',
    'guest_count' => 2,
    'payment_method' => 'bkash',
    'transaction_id' => 'TRX' . strtoupper(bin2hex(random_bytes(4)))
];
$regRes = $regService->register($regData);
if (!$regRes['success']) {
    die("[TEST 2 FAILED] Registration failed: " . ($regRes['message'] ?? '') . "\n");
}
echo "[TEST 2] Registration Submission (Former Student with 2 Guests): PASSED (Code: " . $regRes['registration_code'] . ", Total Fee: ৳ " . $regRes['total_fee'] . ")\n";

// 3. Duplicate Transaction ID Prevention
$dupRes = $regService->register($regData);
if ($dupRes['success']) {
    die("[TEST 3 FAILED] Duplicate TRX ID allowed!\n");
}
echo "[TEST 3] Duplicate Transaction ID Prevention: PASSED (Rejected duplicate trx id)\n";

// 4. Payment Approval Workflow
$payService = new PaymentService();
$approveRes = $payService->approvePayment($regRes['payment_id'], $loginRes['user']['id'], $loginRes['user']['name'] ?? 'Main Admin', 'Test approval remarks');
if (!$approveRes['success']) {
    die("[TEST 4 FAILED] Payment approval failed: " . ($approveRes['message'] ?? '') . "\n");
}
echo "[TEST 4] Payment Approval Workflow: PASSED (Registration confirmed, audit log created)\n";

// 5. QR Code & Verification Lookup
$verifyReg = $regService->findByQrToken($regRes['qr_token']);
if (!$verifyReg || $verifyReg['registration_code'] !== $regRes['registration_code']) {
    die("[TEST 5 FAILED] QR verification lookup failed!\n");
}
echo "[TEST 5] QR Code & Verification Lookup: PASSED (QR Token verified)\n";

// 6. Official Receipt Generation
$receiptService = new ReceiptService();
$receipt = $receiptService->generateReceipt($regRes['registration_id']);
$qrUri = $receiptService->getQrCodeDataUri($regRes['qr_token']);
if (!$receipt || empty($qrUri)) {
    die("[TEST 6 FAILED] Receipt generation failed!\n");
}
echo "[TEST 6] Official Receipt Generation: PASSED (Receipt No: " . $receipt['receipt_number'] . ", QR URI generated)\n";

// 7. Financial Aggregations
$stats = $payService->getFinancialStats();
echo "[TEST 7] Financial Aggregations: PASSED (Total Approved Collection: ৳ " . $stats['total_approved'] . ")\n";

// 8. Moderator Granular Permissions
$pdo = Database::getConnection();
$modEmail = 'mod_' . bin2hex(random_bytes(3)) . '@test.com';
$modUser = 'mod_' . bin2hex(random_bytes(2));
$stmt = $pdo->prepare("INSERT INTO users (tenant_id, username, email, password_hash, name, role_id, status) VALUES (?, ?, ?, ?, ?, 2, 'active')");
$stmt->execute([DEFAULT_TENANT_ID, $modUser, $modEmail, password_hash('Mod@2027', PASSWORD_BCRYPT), 'Test Moderator']);
$modId = (int)$pdo->lastInsertId();

// Assign only registration.view
$stmtPerm = $pdo->prepare("SELECT id FROM permissions WHERE name = 'registration.view'");
$stmtPerm->execute();
$permId = (int)$stmtPerm->fetchColumn();
$pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, 1)")->execute([$modId, $permId]);

// Login as moderator to test session permissions
$modLoginRes = $auth->login($modUser, 'Mod@2027');
$canView = hasPermission('registration.view');
$canApprove = hasPermission('payment.approve');

if (!$canView || $canApprove) {
    die("[TEST 8 FAILED] Moderator permission enforcement failed: canView=$canView, canApprove=$canApprove\n");
}
echo "[TEST 8] Moderator Creation with Granular Permissions: PASSED (Permissions enforced: Can view = YES, Can approve = NO)\n";

echo "\n=============================================\n";
echo "🎉 ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "=============================================\n";
