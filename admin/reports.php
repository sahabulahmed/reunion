<?php
// admin/reports.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/PaymentService.php';

requirePermission('reports.view');

$pageTitle = 'মোট কালেকশন ও আর্থিক রিপোর্ট';
$pdo = Database::getConnection();
$paymentService = new PaymentService();

$startDate = trim($_GET['start_date'] ?? '');
$endDate   = trim($_GET['end_date'] ?? '');

// Financial stats
$stats = $paymentService->getFinancialStats($startDate ?: null, $endDate ?: null);

// Moderator-wise approval metrics
$modParams = [DEFAULT_TENANT_ID];
$modDate = "";
if ($startDate && $endDate) {
    $modDate = " AND DATE(pa.created_at) BETWEEN ? AND ?";
    $modParams[] = $startDate;
    $modParams[] = $endDate;
}

$modSql = "
    SELECT u.name as moderator_name, u.username,
           SUM(CASE WHEN pa.action = 'approved' THEN 1 ELSE 0 END) as approved_count,
           SUM(CASE WHEN pa.action = 'approved' THEN p.amount ELSE 0 END) as approved_amount,
           SUM(CASE WHEN pa.action = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM users u
    JOIN payment_approvals pa ON u.id = pa.reviewed_by
    JOIN payments p ON pa.payment_id = p.id
    WHERE u.tenant_id = ? $modDate
    GROUP BY u.id
    ORDER BY approved_amount DESC
";
$modStmt = $pdo->prepare($modSql);
$modStmt->execute($modParams);
$moderatorReports = $modStmt->fetchAll();

// Daily collection trend
$dailySql = "
    SELECT DATE(submitted_at) as pay_date,
           COUNT(*) as total_payments,
           SUM(CASE WHEN payment_status = 'approved' THEN amount ELSE 0 END) as daily_approved,
           SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END) as daily_pending
    FROM payments
    WHERE tenant_id = ? " . ($startDate && $endDate ? " AND DATE(submitted_at) BETWEEN ? AND ?" : "") . "
    GROUP BY DATE(submitted_at)
    ORDER BY pay_date DESC
    LIMIT 15
";
$dailyStmt = $pdo->prepare($dailySql);
$dailyStmt->execute($modParams);
$dailyTrend = $dailyStmt->fetchAll();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Financial_Report_' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['তারিখ', 'মোট পেমেন্ট সংখ্যা', 'অনুমোদিত তহবিল (টাকা)', 'অপেক্ষমাণ তহবিল (টাকা)']);
    foreach ($dailyTrend as $d) {
        fputcsv($out, [$d['pay_date'], $d['total_payments'], $d['daily_approved'], $d['daily_pending']]);
    }
    fclose($out);
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header & Date Filter -->
<div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200 shadow-sm mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-gray-900">আর্থিক কালেকশন ও রিপোর্ট</h2>
        <p class="text-xs text-gray-500">অনুমোদিত পেমেন্ট ও তহবিলের বিশদ পরিসংখ্যান</p>
    </div>

    <!-- Date Range Filter -->
    <form method="GET" action="" class="flex flex-wrap items-center gap-2 text-xs">
        <div>
            <input type="date" name="start_date" value="<?= e($startDate) ?>" class="px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
        </div>
        <span class="text-gray-400">থেকে</span>
        <div>
            <input type="date" name="end_date" value="<?= e($endDate) ?>" class="px-3 py-2 border border-gray-300 rounded-xl focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
        </div>
        <button type="submit" class="px-4 py-2 bg-[#7D2431] text-white rounded-xl font-bold hover:bg-[#601923] transition">
            ফিল্টার
        </button>
        <?php if ($startDate || $endDate): ?>
            <a href="reports.php" class="px-3 py-2 bg-gray-100 text-gray-600 rounded-xl hover:bg-gray-200">রিসেট</a>
        <?php endif; ?>
        <a href="?export=csv<?= $startDate ? '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) : '' ?>" class="px-4 py-2 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition flex items-center gap-1">
            <i class="fa-solid fa-file-csv"></i> CSV
        </a>
    </form>
</div>

<!-- Financial Summary Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
    
    <!-- Net Collection / Approved -->
    <div class="bg-gradient-to-br from-[#155B43] to-[#0c3c2b] text-white p-5 rounded-2xl shadow-md">
        <span class="text-xs text-emerald-200 font-bold uppercase tracking-wider block">মোট নেট কালেকশন (APPROVED)</span>
        <h3 class="text-3xl font-black mt-2"><?= formatTaka($stats['net_collection']) ?></h3>
        <p class="text-[11px] text-emerald-200 mt-2">সম্পূর্ণ নিশ্চিত তহবিল</p>
    </div>

    <!-- Pending Amount -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <span class="text-xs text-amber-600 font-bold uppercase tracking-wider block">অপেক্ষমাণ পেমেন্ট (PENDING)</span>
        <h3 class="text-2xl font-black text-gray-900 mt-2"><?= formatTaka($stats['total_pending']) ?></h3>
        <p class="text-[11px] text-gray-400 mt-2">যাচাইয়ের পর যুক্ত হবে</p>
    </div>

    <!-- Rejected Amount -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <span class="text-xs text-red-600 font-bold uppercase tracking-wider block">বাতিলকৃত পেমেন্ট (REJECTED)</span>
        <h3 class="text-2xl font-black text-gray-900 mt-2"><?= formatTaka($stats['total_rejected']) ?></h3>
        <p class="text-[11px] text-gray-400 mt-2">অসঙ্গতিপূর্ণ আবেদন</p>
    </div>

    <!-- Payment Methods Breakdown -->
    <div class="bg-white p-5 rounded-2xl border border-gray-200 shadow-sm">
        <span class="text-xs text-gray-500 font-bold uppercase tracking-wider block">পেমেন্ট মাধ্যম অনুপাত</span>
        <div class="mt-2 space-y-1.5 text-xs">
            <?php foreach ($stats['by_method'] as $m): ?>
                <div class="flex justify-between items-center">
                    <span class="font-bold uppercase text-gray-700"><?= e($m['payment_method']) ?> (<?= en2bn($m['count']) ?>টি):</span>
                    <span class="font-bold text-[#155B43]"><?= formatTaka($m['total']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<!-- Breakdown Sections -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Moderator-wise Approval History -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 bg-[#FFF9ED] flex items-center justify-between">
            <h3 class="font-bold text-sm text-[#7D2431]">মডারেটর-ভিত্তিক অনুমোদন রিপোর্ট</h3>
            <span class="text-[11px] text-gray-500">কে কতটি পেমেন্ট অনুমোদন করেছেন</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 font-semibold border-b">
                    <tr>
                        <th class="p-3">মডারেটরের নাম</th>
                        <th class="p-3">অনুমোদিত সংখ্যা</th>
                        <th class="p-3">অনুমোদিত টাকা</th>
                        <th class="p-3">বাতিল সংখ্যা</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($moderatorReports)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-gray-400">এখনো কোনো অনুমোদনের তথ্য নেই।</td></tr>
                    <?php else: ?>
                        <?php foreach ($moderatorReports as $mr): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-bold text-gray-800">
                                    <?= e($mr['moderator_name']) ?>
                                    <span class="text-[10px] text-gray-400 font-mono block">@<?= e($mr['username']) ?></span>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded">
                                        <?= en2bn($mr['approved_count']) ?> টি
                                    </span>
                                </td>
                                <td class="p-3 font-bold text-[#155B43]">
                                    <?= formatTaka($mr['approved_amount']) ?>
                                </td>
                                <td class="p-3 text-red-600 font-semibold">
                                    <?= en2bn($mr['rejected_count']) ?> টি
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Daily Collection Trend -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-bold text-sm text-gray-900">তারিখ অনুযায়ী আদায়কৃত তহবিল</h3>
            <span class="text-[11px] text-gray-400">সর্বশেষ দিনগুলোর হিসাব</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-500 font-semibold border-b">
                    <tr>
                        <th class="p-3">তারিখ</th>
                        <th class="p-3">পেমেন্ট সংখ্যা</th>
                        <th class="p-3">অনুমোদিত টাকা</th>
                        <th class="p-3">অপেক্ষমাণ টাকা</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($dailyTrend)): ?>
                        <tr><td colspan="4" class="p-6 text-center text-gray-400">কোনো তথ্য নেই।</td></tr>
                    <?php else: ?>
                        <?php foreach ($dailyTrend as $dt): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-semibold text-gray-800">
                                    <?= date('d M Y', strtotime($dt['pay_date'])) ?>
                                </td>
                                <td class="p-3 font-medium">
                                    <?= en2bn($dt['total_payments']) ?> টি
                                </td>
                                <td class="p-3 font-bold text-emerald-700">
                                    <?= formatTaka($dt['daily_approved']) ?>
                                </td>
                                <td class="p-3 text-amber-700 font-medium">
                                    <?= formatTaka($dt['daily_pending']) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

