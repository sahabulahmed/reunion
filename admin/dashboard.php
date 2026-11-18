<?php
// admin/dashboard.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

$pageTitle = 'ড্যাশবোর্ড ওভারভিউ';
require_once __DIR__ . '/includes/header.php';

$pdo = Database::getConnection();

// 1. Total Registrations
$stmtTotalReg = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID);
$totalRegistrations = (int)$stmtTotalReg->fetchColumn();

// 2. Approved Registrations
$stmtAppReg = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND registration_status = 'confirmed'");
$approvedRegistrations = (int)$stmtAppReg->fetchColumn();

// 3. Pending Registrations
$stmtPenReg = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND registration_status = 'pending'");
$pendingRegistrations = (int)$stmtPenReg->fetchColumn();

// 4. Rejected Registrations
$stmtRejReg = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND registration_status = 'rejected'");
$rejectedRegistrations = (int)$stmtRejReg->fetchColumn();

// 5. Total Approved Collection
$stmtAppCol = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND payment_status = 'approved'");
$totalApprovedCollection = (float)$stmtAppCol->fetchColumn();

// 6. Pending Payment Amount
$stmtPenCol = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND payment_status = 'pending'");
$totalPendingCollection = (float)$stmtPenCol->fetchColumn();

// 7. Total Former Students
$stmtFormer = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND student_category = 'former'");
$totalFormer = (int)$stmtFormer->fetchColumn();

// 8. Total Current Students
$stmtCurrent = $pdo->query("SELECT COUNT(*) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID . " AND student_category = 'current'");
$totalCurrent = (int)$stmtCurrent->fetchColumn();

// 9. Total Guests
$stmtGuests = $pdo->query("SELECT COALESCE(SUM(guest_count), 0) FROM registrations WHERE tenant_id = " . DEFAULT_TENANT_ID);
$totalGuests = (int)$stmtGuests->fetchColumn();

// 10. Active Moderators
$stmtMods = $pdo->query("SELECT COUNT(*) FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'moderator' AND u.status = 'active'");
$activeModerators = (int)$stmtMods->fetchColumn();

// Latest Pending Payments for verification
$pendingPayments = $pdo->query("
    SELECT p.*, r.registration_code, r.student_name, r.student_category, r.mobile_number
    FROM payments p
    JOIN registrations r ON p.registration_id = r.id
    WHERE p.payment_status = 'pending'
    ORDER BY p.id DESC
    LIMIT 5
")->fetchAll();

// Recent Registrations
$recentRegistrations = $pdo->query("
    SELECT * FROM registrations 
    ORDER BY id DESC 
    LIMIT 5
")->fetchAll();
?>

<!-- Statistics Grid -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4 mb-8">
    
    <!-- 1. Total Registrations -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">মোট নিবন্ধন</p>
            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= en2bn($totalRegistrations) ?></h3>
        </div>
        <div class="w-11 h-11 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-users"></i>
        </div>
    </div>

    <!-- 2. Approved Registrations -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">অনুমোদিত নিবন্ধন</p>
            <h3 class="text-2xl font-bold text-emerald-600 mt-1"><?= en2bn($approvedRegistrations) ?></h3>
        </div>
        <div class="w-11 h-11 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-circle-check"></i>
        </div>
    </div>

    <!-- 3. Pending Registrations -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">অপেক্ষমাণ নিবন্ধন</p>
            <h3 class="text-2xl font-bold text-amber-600 mt-1"><?= en2bn($pendingRegistrations) ?></h3>
        </div>
        <div class="w-11 h-11 bg-amber-50 text-amber-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-clock"></i>
        </div>
    </div>

    <!-- 4. Rejected Registrations -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">বাতিল নিবন্ধন</p>
            <h3 class="text-2xl font-bold text-red-600 mt-1"><?= en2bn($rejectedRegistrations) ?></h3>
        </div>
        <div class="w-11 h-11 bg-red-50 text-red-600 rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-circle-xmark"></i>
        </div>
    </div>

    <!-- 5. Active Moderators -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">সক্রিয় মডারেটর</p>
            <h3 class="text-2xl font-bold text-[#7D2431] mt-1"><?= en2bn($activeModerators) ?></h3>
        </div>
        <div class="w-11 h-11 bg-rose-50 text-[#7D2431] rounded-xl flex items-center justify-center text-lg">
            <i class="fa-solid fa-user-shield"></i>
        </div>
    </div>

    <!-- 6. Total Approved Collection -->
    <div class="bg-gradient-to-br from-[#155B43] to-[#0c3c2b] text-white p-5 rounded-2xl shadow-md sm:col-span-2 lg:col-span-2 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-emerald-200 uppercase tracking-wider">মোট আদায়কৃত তহবিল (APPROVED)</p>
            <h3 class="text-3xl font-black mt-1"><?= formatTaka($totalApprovedCollection) ?></h3>
            <p class="text-xs text-emerald-200 mt-1">শুধুমাত্র অনুমোদিত পেমেন্টের যোগফল</p>
        </div>
        <div class="w-14 h-14 bg-white/10 rounded-2xl flex items-center justify-center text-2xl text-emerald-300">
            <i class="fa-solid fa-hand-holding-dollar"></i>
        </div>
    </div>

    <!-- 7. Pending Collection -->
    <div class="bg-gradient-to-br from-[#C69A45] to-[#9e7629] text-white p-5 rounded-2xl shadow-md sm:col-span-1 lg:col-span-1 flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold text-amber-100 uppercase tracking-wider">যাচাইয়ের অপেক্ষায়</p>
            <h3 class="text-2xl font-black mt-1"><?= formatTaka($totalPendingCollection) ?></h3>
            <p class="text-[11px] text-amber-100 mt-1">অপেক্ষমাণ পেমেন্ট</p>
        </div>
        <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center text-xl text-amber-200">
            <i class="fa-solid fa-hourglass-half"></i>
        </div>
    </div>

    <!-- 8. Former Students -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">সাবেক শিক্ষার্থী</p>
            <h3 class="text-xl font-bold text-gray-800 mt-1"><?= en2bn($totalFormer) ?> জন</h3>
        </div>
        <div class="w-10 h-10 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center">
            <i class="fa-solid fa-graduation-cap"></i>
        </div>
    </div>

    <!-- 9. Current Students & Guests -->
    <div class="bg-white p-4 rounded-2xl border border-gray-200 shadow-sm flex items-center justify-between">
        <div>
            <p class="text-xs text-gray-500 font-medium">বর্তমান ও অতিথি</p>
            <h3 class="text-base font-bold text-gray-800 mt-1"><?= en2bn($totalCurrent) ?> + <?= en2bn($totalGuests) ?> জন</h3>
        </div>
        <div class="w-10 h-10 bg-gray-100 text-gray-600 rounded-xl flex items-center justify-center">
            <i class="fa-solid fa-people-roof"></i>
        </div>
    </div>

</div>

<!-- Tables Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Pending Payment Approvals -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between bg-amber-50/50">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-bell text-amber-600"></i>
                <h3 class="font-bold text-sm text-gray-900">যাচাইয়ের অপেক্ষমাণ পেমেন্ট</h3>
            </div>
            <a href="<?= getBaseUrl() ?>/admin/payments.php?status=pending" class="text-xs font-semibold text-[#7D2431] hover:underline">
                সকল পেমেন্ট (<?= en2bn(count($pendingPayments)) ?>) ➔
            </a>
        </div>

        <?php if (empty($pendingPayments)): ?>
            <div class="p-8 text-center text-gray-400 text-sm">
                <i class="fa-solid fa-check-double text-3xl mb-2 text-emerald-400"></i>
                <p>সব পেমেন্ট যাচাই সম্পন্ন হয়েছে!</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-500 font-semibold uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="p-3">শিক্ষার্থী</th>
                            <th class="p-3">মাধ্যম / TrxID</th>
                            <th class="p-3">পরিমাণ</th>
                            <th class="p-3 text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($pendingPayments as $p): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3">
                                    <p class="font-bold text-gray-900"><?= e($p['student_name']) ?></p>
                                    <p class="text-[11px] text-gray-500"><?= e($p['registration_code']) ?> • <?= e($p['mobile_number']) ?></p>
                                </td>
                                <td class="p-3 font-mono">
                                    <span class="font-bold uppercase text-[#7D2431]"><?= e($p['payment_method']) ?></span>
                                    <p class="text-[11px] text-gray-600"><?= e($p['transaction_id']) ?></p>
                                </td>
                                <td class="p-3 font-bold text-emerald-700">
                                    <?= formatTaka($p['amount']) ?>
                                </td>
                                <td class="p-3 text-right">
                                    <a href="<?= getBaseUrl() ?>/admin/payments.php?id=<?= $p['id'] ?>" class="px-2.5 py-1 bg-[#7D2431] text-white rounded-lg text-xs font-medium hover:bg-[#601923] transition">
                                        যাচাই করুন
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recent Registrations -->
    <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="p-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-blue-600"></i>
                <h3 class="font-bold text-sm text-gray-900">সাম্প্রতিক নিবন্ধনসমূহ</h3>
            </div>
            <a href="<?= getBaseUrl() ?>/admin/registrations.php" class="text-xs font-semibold text-[#7D2431] hover:underline">
                সকল নিবন্ধন ➔
            </a>
        </div>

        <?php if (empty($recentRegistrations)): ?>
            <div class="p-8 text-center text-gray-400 text-sm">
                <p>এখনো কোনো নিবন্ধন জমা পড়েনি।</p>
            </div>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="bg-gray-50 text-gray-500 font-semibold uppercase tracking-wider border-b border-gray-100">
                        <tr>
                            <th class="p-3">রেজিস্ট্রেশন কোড</th>
                            <th class="p-3">শিক্ষার্থী</th>
                            <th class="p-3">ক্যাটাগরি</th>
                            <th class="p-3 text-right">স্ট্যাটাস</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($recentRegistrations as $r): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="p-3 font-mono font-bold text-[#7D2431]">
                                    <?= e($r['registration_code']) ?>
                                </td>
                                <td class="p-3">
                                    <p class="font-bold text-gray-900"><?= e($r['student_name']) ?></p>
                                    <p class="text-[11px] text-gray-500"><?= e($r['mobile_number']) ?></p>
                                </td>
                                <td class="p-3">
                                    <span class="px-2 py-0.5 rounded text-[11px] font-medium <?= $r['student_category'] === 'former' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' ?>">
                                        <?= $r['student_category'] === 'former' ? 'সাবেক' : 'বর্তমান' ?>
                                    </span>
                                </td>
                                <td class="p-3 text-right">
                                    <?php if ($r['registration_status'] === 'confirmed'): ?>
                                        <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 rounded text-[10px] font-bold">অনুমোদিত</span>
                                    <?php elseif ($r['registration_status'] === 'rejected'): ?>
                                        <span class="px-2 py-0.5 bg-red-100 text-red-800 rounded text-[10px] font-bold">বাতিল</span>
                                    <?php else: ?>
                                        <span class="px-2 py-0.5 bg-amber-100 text-amber-800 rounded text-[10px] font-bold">প্রক্রিয়াধীন</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

