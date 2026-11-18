<?php
// app/Services/RegistrationService.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../Helpers/functions.php';

class RegistrationService {
    private PDO $pdo;

    public function __construct() {
        $this->pdo = Database::getConnection();
    }

    /**
     * Generate unique registration code
     */
    private function generateRegistrationCode(): string {
        $prefix = 'REU27-';
        do {
            $code = $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM registrations WHERE registration_code = ?");
            $stmt->execute([$code]);
            $exists = (int)$stmt->fetchColumn() > 0;
        } while ($exists);
        return $code;
    }

    /**
     * Generate secure QR token
     */
    private function generateQrToken(): string {
        return bin2hex(random_bytes(32)); // 64 chars
    }

    /**
     * Validate and submit registration with manual payment
     */
    public function register(array $data, ?array $proofFile = null): array {
        // Check if registration is enabled
        if (getSetting('registration_enabled', '1') !== '1') {
            return ['success' => false, 'message' => 'দুঃখিত, অনলাইন নিবন্ধন সাময়িকভাবে বন্ধ রয়েছে।'];
        }

        // Sanitize & normalize
        $category     = trim($data['student_category'] ?? '');
        $name         = trim($data['student_name'] ?? '');
        $fatherName   = trim($data['father_name'] ?? '');
        $motherName   = trim($data['mother_name'] ?? '');
        $mobile       = trim(bn2en($data['mobile_number'] ?? ''));
        $address      = trim($data['current_address'] ?? '');
        $occupation   = trim($data['occupation'] ?? '');
        $prevClass    = trim($data['prev_class'] ?? '');
        $passingYear  = trim(bn2en($data['passing_year'] ?? ''));
        $currentClass = trim($data['current_class'] ?? '');
        $hasGuest     = (int)($data['has_guest'] ?? 0);
        $guestCount   = $hasGuest ? (int)bn2en($data['guest_count'] ?? 0) : 0;
        $tshirtSize   = strtoupper(trim($data['tshirt_size'] ?? 'L'));

        // Payment fields
        $payMethod    = strtolower(trim($data['payment_method'] ?? 'bkash'));
        $senderNumber = trim(bn2en($data['sender_number'] ?? $mobile));
        $trxId        = strtoupper(trim($data['transaction_id'] ?? ''));

        // Basic Validation
        if (!in_array($category, [CAT_FORMER, CAT_CURRENT], true)) {
            return ['success' => false, 'message' => 'সঠিক শিক্ষার্থী ধরন (সাবেক অথবা বর্তমান) নির্বাচন করুন।'];
        }

        if (empty($name)) {
            return ['success' => false, 'message' => 'শিক্ষার্থীর নাম পূরণ করা আবশ্যক।'];
        }

        if (empty($fatherName)) {
            return ['success' => false, 'message' => 'পিতার নাম পূরণ করা আবশ্যক।'];
        }

        if (empty($motherName)) {
            return ['success' => false, 'message' => 'মাতার নাম পূরণ করা আবশ্যক।'];
        }

        // Mobile validation (BD numbers: 013, 014, 015, 016, 017, 018, 019 - 11 digits)
        $cleanMobile = preg_replace('/[^0-9]/', '', $mobile);
        if (strlen($cleanMobile) === 13 && str_starts_with($cleanMobile, '8801')) {
            $cleanMobile = substr($cleanMobile, 2);
        }
        if (!preg_match('/^01[3-9]\d{8}$/', $cleanMobile)) {
            return ['success' => false, 'message' => 'সঠিক ১১ ডিজিটের সচল বাংলাদেশি মোবাইল নম্বর প্রদান করুন (যেমন: 017XXXXXXXX)।'];
        }
        $mobile = $cleanMobile;

        if (empty($address)) {
            return ['success' => false, 'message' => 'বর্তমান ঠিকানা পূরণ করা আবশ্যক।'];
        }

        if ($hasGuest && $guestCount < 1) {
            return ['success' => false, 'message' => 'অতিথির সংখ্যা কমপক্ষে ১ জন হতে হবে।'];
        }

        if (!in_array($tshirtSize, ['XS', 'S', 'M', 'L', 'XL', 'XXL'], true)) {
            $tshirtSize = 'L';
        }

        if (empty($trxId)) {
            return ['success' => false, 'message' => 'পেমেন্টের ট্রানজেকশন আইডি (Transaction ID) পূরণ করা আবশ্যক।'];
        }

        // Duplicate transaction ID check
        $stmtTrx = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE transaction_id = ?");
        $stmtTrx->execute([$trxId]);
        if ((int)$stmtTrx->fetchColumn() > 0) {
            return ['success' => false, 'message' => 'এই ট্রানজেকশন আইডি (Transaction ID) দিয়ে ইতিমধ্যে পেমেন্ট সাবমিট করা হয়েছে! অন্য ট্রানজেকশন আইডি ব্যবহার করুন।'];
        }

        // Calculate fees from current settings
        $formerFee = (float)getSetting('former_student_fee', '1000');
        $currentFee = (float)getSetting('current_student_fee', '500');
        $guestFeePerPerson = (float)getSetting('guest_fee', '500');

        $studentFee = ($category === CAT_FORMER) ? $formerFee : $currentFee;
        $totalGuestFee = $hasGuest ? ($guestCount * $guestFeePerPerson) : 0.00;
        $totalFee = $studentFee + $totalGuestFee;

        // Handle proof upload if provided
        $proofPath = null;
        if ($proofFile && !empty($proofFile['tmp_name']) && $proofFile['error'] === UPLOAD_ERR_OK) {
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
            $fileExt = strtolower(pathinfo($proofFile['name'], PATHINFO_EXTENSION));
            if (in_array($fileExt, $allowedExts, true) && $proofFile['size'] <= 5 * 1024 * 1024) {
                $newFileName = 'proof_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $fileExt;
                $dest = PROOFS_PATH . '/' . $newFileName;
                if (!is_dir(PROOFS_PATH)) {
                    mkdir(PROOFS_PATH, 0777, true);
                }
                if (move_uploaded_file($proofFile['tmp_name'], $dest)) {
                    $proofPath = 'storage/payment-proofs/' . $newFileName;
                }
            }
        }

        // Database transaction
        $this->pdo->beginTransaction();
        try {
            $regCode = $this->generateRegistrationCode();
            $qrToken = $this->generateQrToken();

            // Insert Registration
            $stmtReg = $this->pdo->prepare("
                INSERT INTO registrations (
                    tenant_id, registration_code, student_category, student_name,
                    father_name, mother_name, mobile_number, current_address,
                    occupation, prev_class, passing_year, current_class,
                    has_guest, guest_count, tshirt_size, student_fee, guest_fee,
                    total_fee, payment_status, registration_status, qr_token
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, 'pending', 'pending', ?
                )
            ");

            $stmtReg->execute([
                DEFAULT_TENANT_ID,
                $regCode,
                $category,
                $name,
                $fatherName,
                $motherName,
                $mobile,
                $address,
                $occupation,
                $prevClass,
                $passingYear,
                $currentClass,
                $hasGuest,
                $guestCount,
                $tshirtSize,
                $studentFee,
                $totalGuestFee,
                $totalFee,
                $qrToken
            ]);

            $regId = (int)$this->pdo->lastInsertId();

            // Insert Payment
            $stmtPay = $this->pdo->prepare("
                INSERT INTO payments (
                    tenant_id, registration_id, payment_method, sender_number,
                    transaction_id, amount, proof_image, payment_status
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, 'pending'
                )
            ");

            $stmtPay->execute([
                DEFAULT_TENANT_ID,
                $regId,
                $payMethod,
                $senderNumber ?: $mobile,
                $trxId,
                $totalFee,
                $proofPath
            ]);

            $payId = (int)$this->pdo->lastInsertId();

            // Commit
            $this->pdo->commit();

            logAudit('registration.created', 'registration', (string)$regId, null, [
                'registration_code' => $regCode,
                'name' => $name,
                'mobile' => $mobile,
                'amount' => $totalFee
            ]);

            return [
                'success' => true,
                'registration_id' => $regId,
                'registration_code' => $regCode,
                'qr_token' => $qrToken,
                'total_fee' => $totalFee,
                'student_name' => $name,
                'payment_id' => $payId
            ];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Registration error: " . $e->getMessage());
            return ['success' => false, 'message' => 'নিবন্ধন সংরক্ষণে সমস্যা হয়েছে: ' . $e->getMessage()];
        }
    }

    /**
     * Find registration by code or phone
     */
    public function findByCodeOrPhone(string $search): ?array {
        $search = trim(bn2en($search));
        $stmt = $this->pdo->prepare("
            SELECT r.*, 
                   p.id as payment_id, p.payment_method, p.sender_number, p.transaction_id, 
                   p.amount as payment_amount, p.submitted_at as payment_submitted_at,
                   pa.reviewer_name, pa.remarks as approval_remarks, pa.created_at as approval_time
            FROM registrations r
            LEFT JOIN payments p ON r.id = p.registration_id
            LEFT JOIN payment_approvals pa ON p.id = pa.payment_id
            WHERE r.registration_code = ? OR r.mobile_number = ?
            ORDER BY r.id DESC
            LIMIT 1
        ");
        $stmt->execute([$search, $search]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Find registration by secure QR token (for public verification)
     */
    public function findByQrToken(string $token): ?array {
        $stmt = $this->pdo->prepare("
            SELECT registration_code, student_name, student_category, 
                   registration_status, payment_status, tshirt_size, guest_count, created_at
            FROM registrations
            WHERE qr_token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}

