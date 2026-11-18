<?php
// app/Services/ReceiptService.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/functions.php';

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ReceiptService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Generate Receipt record and return its details
     */
    public function generateReceipt(int $registrationId, ?int $generatedBy = null): ?array {
        // Fetch registration
        $stmt = $this->pdo->prepare("
            SELECT r.*, p.payment_method, p.transaction_id, p.sender_number, p.amount as paid_amount,
                   p.submitted_at as payment_time
            FROM registrations r
            LEFT JOIN payments p ON r.id = p.registration_id AND p.payment_status = 'approved'
            WHERE r.id = ?
            LIMIT 1
        ");
        $stmt->execute([$registrationId]);
        $reg = $stmt->fetch();

        if (!$reg || $reg['payment_status'] !== PAY_APPROVED) {
            return null;
        }

        // Check if receipt already exists
        $rcStmt = $this->pdo->prepare("SELECT * FROM receipts WHERE registration_id = ?");
        $rcStmt->execute([$registrationId]);
        $existing = $rcStmt->fetch();

        if ($existing) {
            return array_merge($reg, $existing);
        }

        // Create new receipt number
        $receiptNumber = 'RCT-2027-' . str_pad((string)$registrationId, 5, '0', STR_PAD_LEFT);
        $receiptPath = 'storage/receipts/' . $receiptNumber . '.html';

        $ins = $this->pdo->prepare("
            INSERT INTO receipts (tenant_id, registration_id, receipt_number, receipt_file_path, generated_by)
            VALUES (?, ?, ?, ?, ?)
        ");
        $ins->execute([DEFAULT_TENANT_ID, $registrationId, $receiptNumber, $receiptPath, $generatedBy]);

        $receiptId = (int)$this->pdo->lastInsertId();

        return array_merge($reg, [
            'id' => $receiptId,
            'receipt_number' => $receiptNumber,
            'receipt_file_path' => $receiptPath,
            'generated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Generate QR Code Data URI for verification URL
     */
    public function getQrCodeDataUri(string $token): string {
        $verifyUrl = getBaseUrl() . '/verification/index.php?token=' . urlencode($token);
        return (new QRCode())->render($verifyUrl);
    }
}
