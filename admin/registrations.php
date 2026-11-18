<?php
// admin/registrations.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

requirePermission('registration.view');

$pageTitle = 'সকল নিবন্ধন তালিকা';
$pdo = Database::getConnection();

// Handle Export to CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Registrations_' . date('Y-m-d') . '.csv"');
    
    // UTF-8 BOM for Bengali in Excel
    echo "\xEF\xBB\xBF";
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'রেজিস্ট্রেশন কোড', 'শিক্ষার্থীর নাম', 'ক্যাটাগরি', 'পিতার নাম', 'মাতার নাম', 'মোবাইল নম্বর', 'পাসের সাল/শ্রেণি', 'ঠিকানা', 'অতিথি সংখ্যা', 'টি-শার্ট', 'মোট ফি', 'পেমেন্ট স্ট্যাটাস', 'নিবন্ধন স্ট্যাটাস', 'তারিখ']);

    $stmtExp = $pdo->query("SELECT * FROM registrations ORDER BY id DESC");
    while ($row = $stmtExp->fetch()) {
        fputcsv($output, [
            $row['id'],
            $row['registration_code'],
            $row['student_name'],
            $row['student_category'] === 'former' ? 'সাবেক' : 'বর্তমান',
            $row['father_name'],
            $row['mother_name'],
            $row['mobile_number'],
            $row['student_category'] === 'former' ? $row['passing_year'] : $row['current_class'],
            $row['current_address'],
            $row['guest_count'],
            $row['tshirt_size'],
            $row['total_fee'],
            $row['payment_status'],
            $row['registration_status'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}

// Handle Edit Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_registration') {
    requirePermission('registration.edit');
    $csrf = $_POST['csrf_token'] ?? '';
    if (verify_csrf_token($csrf)) {
        $regId = (int)$_POST['reg_id'];
        $name = trim($_POST['student_name']);
        $father = trim($_POST['father_name']);
        $mother = trim($_POST['mother_name']);
        $mobile = trim($_POST['mobile_number']);
        $address = trim($_POST['current_address']);
        $tshirt = trim($_POST['tshirt_size']);

        $stmtUp = $pdo->prepare("
            UPDATE registrations 
            SET student_name = ?, father_name = ?, mother_name = ?, mobile_number = ?, current_address = ?, tshirt_size = ?
            WHERE id = ?
        ");
        $stmtUp->execute([$name, $father, $mother, $mobile, $address, $tshirt, $regId]);
        logAudit('registration.edited', 'registration', (string)$regId, null, ['name' => $name, 'mobile' => $mobile]);
        setFlash('success', 'নিবন্ধন তথ্য সফলভাবে হালনাগাদ করা হয়েছে।');
    }
    header('Location: ' . getBaseUrl() . '/admin/registrations.php');
    exit;
}

// Filters
$search   = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$status   = trim($_GET['status'] ?? '');
$tshirt   = trim($_GET['tshirt'] ?? '');

$where = ["tenant_id = " . DEFAULT_TENANT_ID];
$params = [];

if (!empty($search)) {
    $where[] = "(registration_code LIKE ? OR student_name LIKE ? OR mobile_number LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($category)) {
    $where[] = "student_category = ?";
    $params[] = $category;
}

if (!empty($status)) {
    $where[] = "registration_status = ?";
    $params[] = $status;
}

if (!empty($tshirt)) {
    $where[] = "tshirt_size = ?";
    $params[] = $tshirt;
}

$whereSql = implode(' AND ', $where);

// Count
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM registrations WHERE $whereSql");
$countStmt->execute($params);
$totalRows = (int)$countStmt->fetchColumn();

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;
$totalPages = ceil($totalRows / $perPage);

// Fetch
$sql = "SELECT * FROM registrations WHERE $whereSql ORDER BY id DESC LIMIT $perPage OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$registrations = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Action Bar & Filters -->
<div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200 shadow-sm mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h2 class="text-lg font-bold text-gray-900">নিবন্ধন ব্যবস্থাপনা</h2>
            <p class="text-xs text-gray-500">মোট নিবন্ধিত: <strong class="text-gray-800"><?= en2bn($totalRows) ?></strong> জন</p>
        </div>
        <div class="flex gap-2">
            <a href="?export=csv" class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-excel"></i> এক্সেল / CSV ডাউনলোড
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <form method="GET" action="" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3">
        <div>
            <input type="text" name="search" value="<?= e($search) ?>" placeholder="নাম / কোড / মোবাইল..." class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
        </div>

        <div>
            <select name="category" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                <option value="">সকল ক্যাটাগরি</option>
                <option value="former" <?= $category === 'former' ? 'selected' : '' ?>>সাবেক শিক্ষার্থী</option>
                <option value="current" <?= $category === 'current' ? 'selected' : '' ?>>বর্তমান শিক্ষার্থী</option>
            </select>
        </div>

        <div>
            <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                <option value="">সকল স্ট্যাটাস</option>
                <option value="confirmed" <?= $status === 'confirmed' ? 'selected' : '' ?>>অনুমোদিত (Confirmed)</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>অপেক্ষমাণ (Pending)</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>বাতিল (Rejected)</option>
            </select>
        </div>

        <div>
            <select name="tshirt" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                <option value="">সকল সাইজ</option>
                <?php foreach (['XS', 'S', 'M', 'L', 'XL', 'XXL'] as $sz): ?>
                    <option value="<?= $sz ?>" <?= $tshirt === $sz ? 'selected' : '' ?>><?= $sz ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 py-2 bg-[#7D2431] hover:bg-[#601923] text-white rounded-xl text-xs font-semibold transition">
                ফিল্টার করুন
            </button>
            <a href="registrations.php" class="px-3 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs flex items-center justify-center">
                রিসেট
            </a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#FFF9ED] text-[#7D2431] font-bold border-b border-amber-200">
                <tr>
                    <th class="p-3.5">কোড</th>
                    <th class="p-3.5">শিক্ষার্থীর নাম</th>
                    <th class="p-3.5">মোবাইল নম্বর</th>
                    <th class="p-3.5">ক্যাটাগরি</th>
                    <th class="p-3.5">টি-শার্ট</th>
                    <th class="p-3.5">অতিথি</th>
                    <th class="p-3.5">মোট ফি</th>
                    <th class="p-3.5">স্ট্যাটাস</th>
                    <th class="p-3.5 text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($registrations)): ?>
                    <tr>
                        <td colspan="9" class="p-8 text-center text-gray-400">
                            কোনো নিবন্ধন পাওয়া যায়নি।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($registrations as $r): ?>
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="p-3.5 font-mono font-bold text-[#7D2431]">
                                <?= e($r['registration_code']) ?>
                            </td>
                            <td class="p-3.5">
                                <p class="font-bold text-gray-900"><?= e($r['student_name']) ?></p>
                                <p class="text-[11px] text-gray-500">পিতা: <?= e($r['father_name']) ?></p>
                            </td>
                            <td class="p-3.5 font-mono text-gray-700">
                                <?= e($r['mobile_number']) ?>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold <?= $r['student_category'] === 'former' ? 'bg-amber-100 text-amber-900' : 'bg-emerald-100 text-emerald-900' ?>">
                                    <?= $r['student_category'] === 'former' ? 'সাবেক' : 'বর্তমান' ?>
                                    <?php if ($r['student_category'] === 'former' && $r['passing_year']): ?>
                                        (<?= en2bn($r['passing_year']) ?>)
                                    <?php elseif ($r['current_class']): ?>
                                        (<?= e($r['current_class']) ?>)
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="p-3.5 font-bold text-gray-800">
                                <span class="px-2 py-0.5 bg-gray-100 rounded"><?= e($r['tshirt_size']) ?></span>
                            </td>
                            <td class="p-3.5 font-medium">
                                <?= $r['guest_count'] > 0 ? en2bn($r['guest_count']) . ' জন' : 'নেই' ?>
                            </td>
                            <td class="p-3.5 font-bold text-[#155B43]">
                                <?= formatTaka($r['total_fee']) ?>
                            </td>
                            <td class="p-3.5">
                                <?php if ($r['registration_status'] === 'confirmed'): ?>
                                    <span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded text-[10px]">অনুমোদিত</span>
                                <?php elseif ($r['registration_status'] === 'rejected'): ?>
                                    <span class="px-2 py-0.5 bg-red-100 text-red-800 font-bold rounded text-[10px]">বাতিল</span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded text-[10px]">অপেক্ষমাণ</span>
                                <?php endif; ?>
                            </td>
                            <td class="p-3.5 text-right space-x-1">
                                <button onclick='viewDetails(<?= json_encode($r, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)' class="p-1.5 bg-blue-50 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="বিস্তারিত দেখুন">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if ($r['registration_status'] === 'confirmed'): ?>
                                    <a href="<?= getBaseUrl() ?>/receipt.php?code=<?= urlencode($r['registration_code']) ?>" target="_blank" class="p-1.5 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 rounded-lg transition" title="রশিদ দেখুন">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="p-4 border-t border-gray-100 flex items-center justify-between text-xs text-gray-500">
            <span>পৃষ্ঠা <?= en2bn($page) ?> এর <?= en2bn($totalPages) ?></span>
            <div class="flex gap-1">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>&status=<?= urlencode($status) ?>&tshirt=<?= urlencode($tshirt) ?>" class="px-3 py-1 rounded <?= $i === $page ? 'bg-[#7D2431] text-white font-bold' : 'bg-gray-100 hover:bg-gray-200 text-gray-700' ?>">
                        <?= en2bn($i) ?>
                    </a>
                <?php endfor; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Details Modal -->
<div id="detailsModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-amber-200">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
            <h3 class="font-bold text-base text-[#7D2431]" id="modalTitle">শিক্ষার্থীর বিস্তারিত তথ্য</h3>
            <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
        </div>
        <div id="modalBody" class="text-xs space-y-2 text-gray-700">
            <!-- Dynamic injected -->
        </div>
        <div class="mt-6 text-right">
            <button onclick="closeModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-medium">
                বন্ধ করুন
            </button>
        </div>
    </div>
</div>

<script>
function viewDetails(data) {
    document.getElementById('modalTitle').innerText = data.student_name + ' (' + data.registration_code + ')';
    const body = `
        <div class="bg-[#FFF9ED] p-3 rounded-xl border border-amber-200 mb-3 space-y-1">
            <p><strong>রেজিস্ট্রেশন কোড:</strong> <span class="font-mono text-[#7D2431] font-bold">${data.registration_code}</span></p>
            <p><strong>শিক্ষার্থীর ধরন:</strong> ${data.student_category === 'former' ? 'সাবেক শিক্ষার্থী' : 'বর্তমান শিক্ষার্থী'}</p>
            <p><strong>শিক্ষাবর্ষ / শ্রেণি:</strong> ${data.student_category === 'former' ? (data.passing_year || 'তথ্য নেই') : (data.current_class || 'তথ্য নেই')}</p>
        </div>
        <p><strong>পিতার নাম:</strong> ${data.father_name}</p>
        <p><strong>মাতার নাম:</strong> ${data.mother_name}</p>
        <p><strong>মোবাইল নম্বর:</strong> ${data.mobile_number}</p>
        <p><strong>পেশা:</strong> ${data.occupation || 'তথ্য নেই'}</p>
        <p><strong>বর্তমান ঠিকানা:</strong> ${data.current_address}</p>
        <p><strong>টি-শার্ট সাইজ:</strong> <span class="font-bold text-[#7D2431]">${data.tshirt_size}</span></p>
        <p><strong>অতিথি সংখ্যা:</strong> ${data.guest_count} জন</p>
        <div class="border-t pt-2 mt-2">
            <p><strong>মোট ফি:</strong> <span class="text-emerald-700 font-bold text-sm">৳ ${data.total_fee}</span></p>
            <p><strong>পেমেন্ট স্ট্যাটাস:</strong> <span class="uppercase font-bold">${data.payment_status}</span></p>
            <p><strong>নিবন্ধন স্ট্যাটাস:</strong> <span class="uppercase font-bold">${data.registration_status}</span></p>
            <p class="text-[10px] text-gray-400 mt-2">নিবন্ধন সময়: ${data.created_at}</p>
        </div>
    `;
    document.getElementById('modalBody').innerHTML = body;
    document.getElementById('detailsModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('detailsModal').classList.add('hidden');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

