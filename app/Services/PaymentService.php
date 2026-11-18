<?php
// app/Services/PaymentService.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/functions.php';
require_once __DIR__ . '/ReceiptService.php';

class PaymentService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Approve a payment safely with transaction and row-level locking
     */
    public function approvePayment(int $paymentId, int $reviewerId, string $reviewerName, ?string $remarks = null): array {
        $this->pdo->beginTransaction();
        try {
            // Lock payment row
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ? FOR UPDATE");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'পেমেন্ট রেকর্ড পাওয়া যায়নি।'];
            }

            if ($payment['payment_status'] === PAY_APPROVED) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'এই পেমেন্টটি ইতিমধ্যে অনুমোদিত (Approved) হয়েছে!'];
            }

            // Lock registration row
            $regStmt = $this->pdo->prepare("SELECT * FROM registrations WHERE id = ? FOR UPDATE");
            $regStmt->execute([$payment['registration_id']]);
            $registration = $regStmt->fetch();

            if (!$registration) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'সংশ্লিষ্ট নিবন্ধন তথ্য পাওয়া যায়নি।'];
            }

            // Update payment status
            $upPay = $this->pdo->prepare("UPDATE payments SET payment_status = 'approved', notes = ? WHERE id = ?");
            $upPay->execute([$remarks, $paymentId]);

            // Update registration status
            $upReg = $this->pdo->prepare("UPDATE registrations SET payment_status = 'approved', registration_status = 'confirmed' WHERE id = ?");
            $upReg->execute([$payment['registration_id']]);

            // Insert approval history record
            $inApp = $this->pdo->prepare("
                INSERT INTO payment_approvals (
                    tenant_id, payment_id, registration_id, action, reviewed_by, reviewer_name, remarks
                ) VALUES (?, ?, ?, 'approved', ?, ?, ?)
            ");
            $inApp->execute([
                DEFAULT_TENANT_ID,
                $paymentId,
                $payment['registration_id'],
                $reviewerId,
                $reviewerName,
                $remarks ?: 'অনুমোদিত'
            ]);

            // Generate Receipt if not exists
            $receiptService = new ReceiptService();
            $receiptResult = $receiptService->generateReceipt($registration['id'], $reviewerId);

            $this->pdo->commit();

            logAudit('payment.approved', 'payment', (string)$paymentId, 
                ['old_status' => $payment['payment_status']], 
                ['new_status' => 'approved', 'reviewer' => $reviewerName, 'registration_code' => $registration['registration_code']]
            );

            return [
                'success' => true,
                'message' => 'পেমেন্ট সফলভাবে অনুমোদিত হয়েছে এবং রশিদ প্রস্তুত হয়েছে।',
                'receipt' => $receiptResult
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Payment approval failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'অনুমোদনে সমস্যা হয়েছে: ' . $e->getMessage()];
        }
    }

    /**
     * Reject a payment safely with reason
     */
    public function rejectPayment(int $paymentId, int $reviewerId, string $reviewerName, string $reason): array {
        $reason = trim($reason);
        if (empty($reason)) {
            return ['success' => false, 'message' => 'পেমেন্ট বাতিলের সুনির্দিষ্ট কারণ উল্লেখ করা আবশ্যক।'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ? FOR UPDATE");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();

            if (!$payment) {
                $this->pdo->rollBack();
                return ['success' => false, 'message' => 'পেমেন্ট রেকর্ড পাওয়া যায়নি।'];
            }

            // Update payment
            $upPay = $this->pdo->prepare("UPDATE payments SET payment_status = 'rejected', notes = ? WHERE id = ?");
            $upPay->execute([$reason, $paymentId]);

            // Update registration status to rejected
            $upReg = $this->pdo->prepare("UPDATE registrations SET payment_status = 'rejected', registration_status = 'rejected' WHERE id = ?");
            $upReg->execute([$payment['registration_id']]);

            // Record in payment_approvals
            $inApp = $this->pdo->prepare("
                INSERT INTO payment_approvals (
                    tenant_id, payment_id, registration_id, action, reviewed_by, reviewer_name, remarks
                ) VALUES (?, ?, ?, 'rejected', ?, ?, ?)
            ");
            $inApp->execute([
                DEFAULT_TENANT_ID,
                $paymentId,
                $payment['registration_id'],
                $reviewerId,
                $reviewerName,
                $reason
            ]);

            $this->pdo->commit();

            logAudit('payment.rejected', 'payment', (string)$paymentId, 
                ['old_status' => $payment['payment_status']], 
                ['new_status' => 'rejected', 'reviewer' => $reviewerName, 'reason' => $reason]
            );

            return ['success' => true, 'message' => 'পেমেন্ট সফলভাবে বাতিল করা হয়েছে।'];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Payment rejection failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'বাতিলকরণে ত্রুটি: ' . $e->getMessage()];
        }
    }

    /**
     * Get financial statistics
     */
    public function getFinancialStats(?string $startDate = null, ?string $endDate = null): array {
        $params = [DEFAULT_TENANT_ID];
        $dateFilter = "";
        if ($startDate && $endDate) {
            $dateFilter = " AND DATE(submitted_at) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }

        // Approved sum
        $stmtApp = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = ? AND payment_status = 'approved'" . $dateFilter);
        $stmtApp->execute($params);
        $totalApproved = (float)$stmtApp->fetchColumn();

        // Pending sum
        $stmtPen = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = ? AND payment_status = 'pending'" . $dateFilter);
        $stmtPen->execute($params);
        $totalPending = (float)$stmtPen->fetchColumn();

        // Rejected sum
        $stmtRej = $this->pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = ? AND payment_status = 'rejected'" . $dateFilter);
        $stmtRej->execute($params);
        $totalRejected = (float)$stmtRej->fetchColumn();

        // Method breakdown
        $stmtMethod = $this->pdo->prepare("
            SELECT payment_method, COUNT(*) as count, COALESCE(SUM(amount), 0) as total
            FROM payments 
            WHERE tenant_id = ? AND payment_status = 'approved'" . $dateFilter . "
            GROUP BY payment_method
        ");
        $stmtMethod->execute($params);
        $byMethod = $stmtMethod->fetchAll();

        // Category breakdown
        $stmtCat = $this->pdo->prepare("
            SELECT r.student_category, COUNT(p.id) as count, COALESCE(SUM(p.amount), 0) as total
            FROM payments p
            JOIN registrations r ON p.registration_id = r.id
            WHERE p.tenant_id = ? AND p.payment_status = 'approved'" . $dateFilter . "
            GROUP BY r.student_category
        ");
        $stmtCat->execute($params);
        $byCategory = $stmtCat->fetchAll();

        return [
            'total_approved' => $totalApproved,
            'total_pending'  => $totalPending,
            'total_rejected' => $totalRejected,
            'net_collection' => $totalApproved,
            'by_method'      => $byMethod,
            'by_category'    => $byCategory,
        ];
    }
}

