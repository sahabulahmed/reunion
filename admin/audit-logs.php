<?php
// admin/audit-logs.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

requirePermission('audit_logs.view');

$pageTitle = 'অডিট লগ ও সিস্টেম অ্যাক্টিভিটি';
$pdo = Database::getConnection();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->query("SELECT COUNT(*) FROM audit_logs WHERE tenant_id = " . DEFAULT_TENANT_ID);
$totalRows = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalRows / $perPage);

$sql = "
    SELECT a.*, u.name as user_name, u.username
    FROM audit_logs a
    LEFT JOIN users u ON a.user_id = u.id
    WHERE a.tenant_id = ?
    ORDER BY a.id DESC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute([DEFAULT_TENANT_ID]);
$logs = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200 shadow-sm mb-6 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-bold text-gray-900">সিস্টেম অডিট লগ (Audit Trail)</h2>
        <p class="text-xs text-gray-500">সকল অ্যাডমিন ও মডারেটরের কার্যকলাপের স্থায়ী অপরিবর্তনীয় রেকর্ড</p>
    </div>
    <span class="px-3 py-1 bg-gray-100 text-gray-700 text-xs font-bold rounded-lg">
        মোট রেকর্ড: <?= en2bn($totalRows) ?>
    </span>
</div>

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#FFF9ED] text-[#7D2431] font-bold border-b border-amber-200">
                <tr>
                    <th class="p-3">সময়</th>
                    <th class="p-3">ব্যবহারকারী</th>
                    <th class="p-3">অ্যাকশন</th>
                    <th class="p-3">মডিউল / Entity</th>
                    <th class="p-3">আইপি ঠিকানা</th>
                    <th class="p-3">পরিবর্তন / বিবরণ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 font-mono">
                <?php if (empty($logs)): ?>
                    <tr><td colspan="6" class="p-8 text-center text-gray-400 font-sans">কোনো লগ রেকর্ড পাওয়া যায়নি।</td></tr>
                <?php else: ?>
                    <?php foreach ($logs as $l): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="p-3 text-gray-500 whitespace-nowrap">
                                <?= date('d M Y, h:i:s A', strtotime($l['created_at'])) ?>
                            </td>
                            <td class="p-3 font-sans">
                                <?php if ($l['user_name']): ?>
                                    <span class="font-bold text-gray-800"><?= e($l['user_name']) ?></span>
                                    <span class="text-[10px] text-gray-400 font-mono block">@<?= e($l['username']) ?></span>
                                <?php else: ?>
                                    <span class="text-gray-400 italic">পাবলিক / সিস্টেম</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3 font-bold text-[#7D2431]">
                                <?= e($l['action']) ?>
                            </td>
                            <td class="p-3 text-gray-700">
                                <?= e($l['entity_type']) ?> #<?= e($l['entity_id'] ?? '') ?>
                            </td>
                            <td class="p-3 text-gray-500">
                                <?= e($l['ip_address']) ?>
                            </td>
                            <td class="p-3 text-[11px] text-gray-600 max-w-xs truncate" title="<?= e($l['new_values'] ?? '') ?>">
                                <?= e($l['new_values'] ?? $l['old_values'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500 font-sans">
            <span>পৃষ্ঠা <?= en2bn($page) ?> এর <?= en2bn($totalPages) ?></span>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= min($totalPages, 10); $i++): ?>
                    <a href="?page=<?= $i ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-[#7D2431] text-white font-bold' : 'bg-gray-100 text-gray-700' ?>">
                        <?= en2bn($i) ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

