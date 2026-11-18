<?php
// admin/payments.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/PaymentService.php';

requirePermission('payment.view');

$pageTitle = 'পেমেন্ট ভেরিফিকেশন ও অনুমোদন';
$pdo = Database::getConnection();
$paymentService = new PaymentService();
$currentUser = currentUser();

// Handle Approval Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $csrf = $_POST['csrf_token'] ?? '';

    if (!verify_csrf_token($csrf)) {
        setFlash('danger', 'নিরাপত্তা টোকেন ত্রুটি! আবার চেষ্টা করুন।');
        header('Location: payments.php');
        exit;
    }

    $paymentId = (int)($_POST['payment_id'] ?? 0);

    if ($action === 'approve') {
        requirePermission('payment.approve');
        $remarks = trim($_POST['remarks'] ?? '');
        $res = $paymentService->approvePayment($paymentId, (int)$currentUser['id'], $currentUser['name'], $remarks);
        if ($res['success']) {
            setFlash('success', $res['message']);
        } else {
            setFlash('danger', $res['message']);
        }
    } elseif ($action === 'reject') {
        requirePermission('payment.reject');
        $reason = trim($_POST['reason'] ?? '');
        $res = $paymentService->rejectPayment($paymentId, (int)$currentUser['id'], $currentUser['name'], $reason);
        if ($res['success']) {
            setFlash('warning', $res['message']);
        } else {
            setFlash('danger', $res['message']);
        }
    }

    header('Location: payments.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Filters
$statusFilter = trim($_GET['status'] ?? 'pending');
$methodFilter = trim($_GET['method'] ?? '');
$search       = trim($_GET['search'] ?? '');

$where = ["p.tenant_id = " . DEFAULT_TENANT_ID];
$params = [];

if ($statusFilter !== 'all' && !empty($statusFilter)) {
    $where[] = "p.payment_status = ?";
    $params[] = $statusFilter;
}

if (!empty($methodFilter)) {
    $where[] = "p.payment_method = ?";
    $params[] = $methodFilter;
}

if (!empty($search)) {
    $where[] = "(p.transaction_id LIKE ? OR p.sender_number LIKE ? OR r.registration_code LIKE ? OR r.student_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM payments p
    JOIN registrations r ON p.registration_id = r.id
    WHERE $whereSql
");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

// Fetch payments with registration & approvals
$sql = "
    SELECT p.*, r.registration_code, r.student_name, r.student_category, r.mobile_number, r.tshirt_size,
           r.guest_count, pa.reviewer_name, pa.remarks as approval_remarks, pa.created_at as approval_time
    FROM payments p
    JOIN registrations r ON p.registration_id = r.id
    LEFT JOIN payment_approvals pa ON p.id = pa.payment_id
    WHERE $whereSql
    ORDER BY p.id DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar & Tabs -->
<div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200 shadow-sm mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">ম্যানুয়াল পেমেন্ট যাচাই ও অনুমোদন</h2>
            <p class="text-xs text-gray-500">বিকাশ ও নগদ পেমেন্টের Transaction ID যাচাই করে অনুমোদন নিশ্চিত করুন</p>
        </div>

        <!-- Status Filter Tabs -->
        <div class="flex flex-wrap gap-2 text-xs">
            <a href="?status=pending" class="px-3.5 py-2 rounded-xl font-bold transition <?= $statusFilter === 'pending' ? 'bg-[#7D2431] text-white shadow-sm' : 'bg-amber-50 text-amber-800 hover:bg-amber-100' ?>">
                ⏳ অপেক্ষমাণ (Pending)
            </a>
            <a href="?status=approved" class="px-3.5 py-2 rounded-xl font-bold transition <?= $statusFilter === 'approved' ? 'bg-emerald-600 text-white shadow-sm' : 'bg-emerald-50 text-emerald-800 hover:bg-emerald-100' ?>">
                ✓ অনুমোদিত (Approved)
            </a>
            <a href="?status=rejected" class="px-3.5 py-2 rounded-xl font-bold transition <?= $statusFilter === 'rejected' ? 'bg-red-600 text-white shadow-sm' : 'bg-red-50 text-red-800 hover:bg-red-100' ?>">
                ✕ বাতিল (Rejected)
            </a>
            <a href="?status=all" class="px-3.5 py-2 rounded-xl font-bold transition <?= $statusFilter === 'all' ? 'bg-gray-800 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                সকল পেমেন্ট
            </a>
        </div>
    </div>

    <!-- Search Form -->
    <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        <input type="hidden" name="status" value="<?= e($statusFilter) ?>">
        <div class="sm:col-span-2">
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Transaction ID / প্রেরক নম্বর / কোড / শিক্ষার্থীর নাম..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
        </div>
        <div class="flex gap-2">
            <select name="method" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                <option value="">সকল মাধ্যম</option>
                <option value="bkash" <?= $methodFilter === 'bkash' ? 'selected' : '' ?>>বিকাশ (bKash)</option>
                <option value="nagad" <?= $methodFilter === 'nagad' ? 'selected' : '' ?>>নগদ (Nagad)</option>
            </select>
            <button type="submit" class="px-4 py-2 bg-[#7D2431] text-white rounded-xl text-xs font-semibold hover:bg-[#601923] transition">
                খুঁজুন
            </button>
        </div>
    </form>
</div>

<!-- Payments Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#FFF9ED] text-[#7D2431] font-bold border-b border-amber-200">
                <tr>
                    <th class="p-3.5">কোড ও শিক্ষার্থী</th>
                    <th class="p-3.5">পেমেন্ট মাধ্যম</th>
                    <th class="p-3.5">প্রেরক নম্বর</th>
                    <th class="p-3.5">Transaction ID</th>
                    <th class="p-3.5">টাকার পরিমাণ</th>
                    <th class="p-3.5">জমা দেওয়ার তারিখ</th>
                    <th class="p-3.5">স্ট্যাটাস</th>
                    <th class="p-3.5 text-right">যাচাই / অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($payments)): ?>
                    <tr>
                        <td colspan="8" class="p-8 text-center text-gray-400">
                            কোনো পেমেন্ট তথ্য পাওয়া যায়নি।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($payments as $p): ?>
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="p-3.5">
                                <p class="font-mono font-bold text-[#7D2431]"><?= e($p['registration_code']) ?></p>
                                <p class="font-bold text-gray-900"><?= e($p['student_name']) ?></p>
                                <span class="text-[10px] text-gray-500 font-medium"><?= $p['student_category'] === 'former' ? 'সাবেক শিক্ষার্থী' : 'বর্তমান শিক্ষার্থী' ?></span>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase <?= $p['payment_method'] === 'bkash' ? 'bg-pink-100 text-pink-700' : 'bg-amber-100 text-amber-800' ?>">
                                    <?= e($p['payment_method']) ?>
                                </span>
                            </td>
                            <td class="p-3.5 font-mono font-semibold text-gray-800">
                                <?= e($p['sender_number']) ?>
                            </td>
                            <td class="p-3.5 font-mono font-black text-blue-900 bg-blue-50/50 px-3 py-2 rounded-lg border border-blue-100 select-all">
                                <?= e($p['transaction_id']) ?>
                            </td>
                            <td class="p-3.5 font-bold text-base text-[#155B43]">
                                <?= formatTaka($p['amount']) ?>
                            </td>
                            <td class="p-3.5 text-gray-500">
                                <?= date('d M Y', strtotime($p['submitted_at'])) ?>
                                <span class="block text-[10px] text-gray-400"><?= date('h:i A', strtotime($p['submitted_at'])) ?></span>
                            </td>
                            <td class="p-3.5">
                                <?php if ($p['payment_status'] === 'approved'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-emerald-100 text-emerald-800 rounded-full font-bold text-[11px]">
                                        ✓ অনুমোদিত
                                    </span>
                                    <?php if ($p['reviewer_name']): ?>
                                        <p class="text-[10px] text-gray-500 mt-1">অনুমোদক: <?= e($p['reviewer_name']) ?></p>
                                    <?php endif; ?>
                                <?php elseif ($p['payment_status'] === 'rejected'): ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-red-100 text-red-800 rounded-full font-bold text-[11px]">
                                        ✕ বাতিল
                                    </span>
                                    <?php if ($p['reviewer_name']): ?>
                                        <p class="text-[10px] text-gray-500 mt-1">বাতিলকারী: <?= e($p['reviewer_name']) ?></p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 bg-amber-100 text-amber-800 rounded-full font-bold text-[11px] animate-pulse">
                                        ⏳ অপেক্ষমাণ
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-right space-x-1.5 whitespace-nowrap">
                                <?php if ($p['payment_status'] === 'pending'): ?>
                                    <?php if (hasPermission('payment.approve')): ?>
                                        <button onclick='openApproveModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)' class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg font-bold text-xs shadow-sm transition">
                                            ✓ অনুমোদন
                                        </button>
                                    <?php endif; ?>

                                    <?php if (hasPermission('payment.reject')): ?>
                                        <button onclick='openRejectModal(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)' class="px-2.5 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 border border-red-200 rounded-lg font-bold text-xs transition">
                                            ✕ বাতিল
                                        </button>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?php if ($p['payment_status'] === 'approved'): ?>
                                        <a href="<?= getBaseUrl() ?>/receipt.php?code=<?= urlencode($p['registration_code']) ?>" target="_blank" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg font-semibold text-xs transition inline-flex items-center gap-1">
                                            <i class="fa-solid fa-file-invoice text-emerald-600"></i> রশিদ
                                        </a>
                                    <?php else: ?>
                                        <span class="text-xs text-gray-400 italic">কারণ: <?= e($p['approval_remarks'] ?? $p['notes'] ?? 'অসঙ্গতি') ?></span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Approve Modal -->
<div id="approveModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-emerald-200">
        <h3 class="font-bold text-base text-emerald-800 mb-2">পেমেন্ট অনুমোদন নিশ্চিতকরণ</h3>
        <p class="text-xs text-gray-600 mb-4">পেমেন্ট অনুমোদন করলে শিক্ষার্থীর নিবন্ধন নিশ্চিত হবে এবং স্বয়ংক্রিয়ভাবে অফিসিয়াল কিউআর রশিদ তৈরি হবে।</p>
        
        <div id="approveModalDetails" class="bg-emerald-50/60 p-3.5 rounded-2xl border border-emerald-200 text-xs space-y-1 mb-4"></div>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="approve">
            <input type="hidden" name="payment_id" id="approvePaymentId">

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1">যাচাইয়ের মন্তব্য (ঐচ্ছিক)</label>
                <input type="text" name="remarks" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-emerald-500 focus:outline-none" placeholder="বিকাশ স্টেটমেন্টে ট্রানজেকশন মিলেছে">
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeApproveModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold">
                    বাতিল
                </button>
                <button type="submit" class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    হ্যাঁ, অনুমোদন করুন
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-red-200">
        <h3 class="font-bold text-base text-red-700 mb-2">পেমেন্ট বাতিল করুন</h3>
        <p class="text-xs text-gray-600 mb-4">পেমেন্ট তথ্যে অসঙ্গতি থাকলে বাতিলের সুনির্দিষ্ট কারণ উল্লেখ করুন।</p>
        
        <div id="rejectModalDetails" class="bg-red-50/60 p-3.5 rounded-2xl border border-red-200 text-xs space-y-1 mb-4"></div>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="payment_id" id="rejectPaymentId">

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-700 mb-1">বাতিলের সুনির্দিষ্ট কারণ *</label>
                <input type="text" name="reason" required class="w-full px-3 py-2 border border-red-300 rounded-xl text-xs focus:ring-2 focus:ring-red-500 focus:outline-none" placeholder="যেমন: ট্রানজেকশন আইডি স্টেটমেন্টে পাওয়া যায়নি / টাকার পরিমাণ কম">
            </div>

            <div class="flex gap-2 justify-end">
                <button type="button" onclick="closeRejectModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold">
                    বন্ধ করুন
                </button>
                <button type="submit" class="px-5 py-2 bg-red-600 hover:bg-red-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    পেমেন্ট বাতিল করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApproveModal(p) {
    document.getElementById('approvePaymentId').value = p.id;
    document.getElementById('approveModalDetails').innerHTML = `
        <p><strong>শিক্ষার্থী:</strong> ${p.student_name} (${p.registration_code})</p>
        <p><strong>মাধ্যম ও TrxID:</strong> <span class="font-mono font-bold">${p.payment_method.toUpperCase()} - ${p.transaction_id}</span></p>
        <p><strong>টাকা:</strong> ৳ ${p.amount}</p>
        <p><strong>প্রেরক নম্বর:</strong> ${p.sender_number}</p>
    `;
    document.getElementById('approveModal').classList.remove('hidden');
}

function closeApproveModal() {
    document.getElementById('approveModal').classList.add('hidden');
}

function openRejectModal(p) {
    document.getElementById('rejectPaymentId').value = p.id;
    document.getElementById('rejectModalDetails').innerHTML = `
        <p><strong>শিক্ষার্থী:</strong> ${p.student_name} (${p.registration_code})</p>
        <p><strong>Transaction ID:</strong> <span class="font-mono font-bold">${p.transaction_id}</span></p>
        <p><strong>টাকা:</strong> ৳ ${p.amount}</p>
    `;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

