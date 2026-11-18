<?php
// admin/moderators.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Only Admin can manage moderators
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    require_once APP_ROOT . '/auth/unauthorized.php';
    exit;
}

$pageTitle = 'মডারেটর ও পারমিশন ব্যবস্থাপনা';
$pdo = Database::getConnection();

// Fetch all permissions for checkboxes
$allPerms = $pdo->query("SELECT * FROM permissions ORDER BY id ASC")->fetchAll();

// Handle Actions (Create, Update Status, Edit Perms, Reset Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        setFlash('danger', 'নিরাপত্তা টোকেন মেয়াদোত্তীর্ণ হয়েছে।');
        header('Location: moderators.php');
        exit;
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create_moderator') {
        $name     = trim($_POST['name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $selectedPerms = $_POST['permissions'] ?? [];

        if (empty($name) || empty($username) || empty($password)) {
            setFlash('danger', 'নাম, ইউজারনেম ও পাসওয়ার্ড পূরণ করা আবশ্যক।');
        } else {
            // Check username duplicate
            $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $chk->execute([$username, $email ?: $username]);
            if ((int)$chk->fetchColumn() > 0) {
                setFlash('danger', 'এই ইউজারনেম বা ইমেইল ইতিমধ্যে ব্যবহৃত হয়েছে।');
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $ins = $pdo->prepare("
                    INSERT INTO users (tenant_id, role_id, name, username, email, phone, password_hash, status)
                    VALUES (?, 2, ?, ?, ?, ?, ?, 'active')
                ");
                $ins->execute([DEFAULT_TENANT_ID, $name, $username, $email, $phone, $hash]);
                $modId = (int)$pdo->lastInsertId();

                // Assign permissions
                if (!empty($selectedPerms)) {
                    $inPerm = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
                    foreach ($selectedPerms as $pId) {
                        $inPerm->execute([$modId, (int)$pId, $_SESSION['user_id']]);
                    }
                }

                logAudit('moderator.created', 'user', (string)$modId, null, ['username' => $username, 'permissions' => $selectedPerms]);
                setFlash('success', "মডারেটর '{$name}' সফলভাবে তৈরি করা হয়েছে।");
            }
        }
    } elseif ($action === 'toggle_status') {
        $modId = (int)$_POST['mod_id'];
        $newStatus = $_POST['new_status'] === 'active' ? 'active' : 'inactive';
        $up = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role_id = 2");
        $up->execute([$newStatus, $modId]);
        logAudit('moderator.status_changed', 'user', (string)$modId, null, ['status' => $newStatus]);
        setFlash('success', "মডারেটরের স্ট্যাটাস পরিবর্তন করা হয়েছে।");
    } elseif ($action === 'update_permissions') {
        $modId = (int)$_POST['mod_id'];
        $selectedPerms = $_POST['permissions'] ?? [];

        // Delete old permissions
        $del = $pdo->prepare("DELETE FROM user_permissions WHERE user_id = ?");
        $del->execute([$modId]);

        // Insert new permissions
        if (!empty($selectedPerms)) {
            $inPerm = $pdo->prepare("INSERT INTO user_permissions (user_id, permission_id, granted_by) VALUES (?, ?, ?)");
            foreach ($selectedPerms as $pId) {
                $inPerm->execute([$modId, (int)$pId, $_SESSION['user_id']]);
            }
        }

        logAudit('moderator.permissions_updated', 'user', (string)$modId, null, ['permissions' => $selectedPerms]);
        setFlash('success', "পারমিশন সফলভাবে হালনাগাদ করা হয়েছে।");
    } elseif ($action === 'reset_password') {
        $modId = (int)$_POST['mod_id'];
        $newPass = $_POST['new_password'] ?? '';
        if (strlen($newPass) >= 6) {
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $up = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND role_id = 2");
            $up->execute([$hash, $modId]);
            logAudit('moderator.password_reset', 'user', (string)$modId);
            setFlash('success', "পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।");
        } else {
            setFlash('danger', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।');
        }
    }

    header('Location: moderators.php');
    exit;
}

// Fetch all moderators with their permissions and approval counts
$sql = "
    SELECT u.*,
           COUNT(DISTINCT pa.id) as approval_count,
           GROUP_CONCAT(DISTINCT up.permission_id) as assigned_permission_ids
    FROM users u
    LEFT JOIN payment_approvals pa ON u.id = pa.reviewed_by
    LEFT JOIN user_permissions up ON u.id = up.user_id
    WHERE u.role_id = 2
    GROUP BY u.id
    ORDER BY u.id DESC
";
$moderators = $pdo->query($sql)->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<!-- Header / Add Button -->
<div class="bg-white p-4 md:p-6 rounded-2xl border border-gray-200 shadow-sm mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
        <h2 class="text-lg font-bold text-gray-900">মডারেটর ও পারমিশন কন্ট্রোল</h2>
        <p class="text-xs text-gray-500">প্রতিটি মডারেটরকে সুনির্দিষ্ট কাজের দায়িত্ব ও পারমিশন প্রদান করুন</p>
    </div>
    <button onclick="openCreateModal()" class="px-4 py-2.5 bg-[#7D2431] hover:bg-[#601923] text-white rounded-xl text-xs font-bold shadow-md transition flex items-center gap-1.5 self-start sm:self-auto">
        <i class="fa-solid fa-user-plus"></i> নতুন মডারেটর তৈরি করুন
    </button>
</div>

<!-- Moderators Table -->
<div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-[#FFF9ED] text-[#7D2431] font-bold border-b border-amber-200">
                <tr>
                    <th class="p-3.5">মডারেটরের নাম</th>
                    <th class="p-3.5">ইউজারনেম ও যোগাযোগ</th>
                    <th class="p-3.5">অনুমোদন সংখ্যা</th>
                    <th class="p-3.5">কার্যকর পারমিশন</th>
                    <th class="p-3.5">স্ট্যাটাস</th>
                    <th class="p-3.5 text-right">অ্যাকশন</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <?php if (empty($moderators)): ?>
                    <tr>
                        <td colspan="6" class="p-8 text-center text-gray-400">
                            কোনো মডারেটর তৈরি করা হয়নি। উপরের বাটন ক্লিক করে মডারেটর যোগ করুন।
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($moderators as $m): ?>
                        <?php 
                        $permIds = !empty($m['assigned_permission_ids']) ? explode(',', $m['assigned_permission_ids']) : [];
                        ?>
                        <tr class="hover:bg-amber-50/40 transition">
                            <td class="p-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full bg-[#C69A45] text-white font-bold flex items-center justify-center text-xs">
                                        <?= mb_substr($m['name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900"><?= e($m['name']) ?></p>
                                        <span class="text-[10px] text-gray-400">যোগদান: <?= date('d M Y', strtotime($m['created_at'])) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td class="p-3.5">
                                <p class="font-mono font-bold text-[#7D2431]"><?= e($m['username']) ?></p>
                                <p class="text-gray-500 text-[11px]"><?= e($m['email'] ?: $m['phone'] ?: 'তথ্য নেই') ?></p>
                            </td>
                            <td class="p-3.5">
                                <span class="px-2.5 py-1 bg-emerald-50 text-emerald-700 font-bold rounded-lg border border-emerald-200">
                                    <?= en2bn($m['approval_count']) ?> টি পেমেন্ট
                                </span>
                            </td>
                            <td class="p-3.5">
                                <button onclick='openPermModal(<?= $m['id'] ?>, <?= json_encode($m['name']) ?>, <?= json_encode($permIds) ?>)' class="px-2.5 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 rounded-lg font-semibold text-[11px] transition flex items-center gap-1">
                                    <i class="fa-solid fa-key"></i> <?= count($permIds) ?> টি পারমিশন (সম্পাদনা)
                                </button>
                            </td>
                            <td class="p-3.5">
                                <form method="POST" action="" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="mod_id" value="<?= $m['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $m['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" class="px-2 py-0.5 rounded text-[10px] font-bold transition <?= $m['status'] === 'active' ? 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
                                        <?= $m['status'] === 'active' ? '● সক্রিয় (Active)' : '○ নিষ্ক্রিয় (Inactive)' ?>
                                    </button>
                                </form>
                            </td>
                            <td class="p-3.5 text-right space-x-1">
                                <button onclick='openResetPassModal(<?= $m['id'] ?>, <?= json_encode($m['name']) ?>)' class="px-2.5 py-1 bg-gray-100 text-gray-700 hover:bg-gray-200 rounded-lg text-xs" title="পাসওয়ার্ড পরিবর্তন">
                                    <i class="fa-solid fa-lock"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div id="createModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-lg w-full p-6 shadow-2xl border border-amber-200 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between border-b pb-3 mb-4">
            <h3 class="font-bold text-base text-[#7D2431]">নতুন মডারেটর তৈরি করুন</h3>
            <button onclick="closeCreateModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
        </div>

        <form method="POST" action="" class="space-y-4">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create_moderator">

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">মডারেটরের নাম *</label>
                <input type="text" name="name" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="যেমন: রাহাত আহমেদ">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ইউজারনেম (Username) *</label>
                    <input type="text" name="username" required class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="rahat">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">পাসওয়ার্ড *</label>
                    <input type="password" name="password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="••••••••">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ইমেইল</label>
                    <input type="email" name="email" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="mod@reunion.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">মোবাইল নম্বর</label>
                    <input type="text" name="phone" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="017XXXXXXXX">
                </div>
            </div>

            <!-- Permission Checkboxes -->
            <div class="border-t pt-3">
                <label class="block text-xs font-bold text-[#7D2431] mb-2">অনুমতি ও পারমিশন নির্ধারণ করুন:</label>
                <div class="space-y-2 max-h-48 overflow-y-auto p-2 bg-gray-50 rounded-xl border border-gray-200">
                    <?php foreach ($allPerms as $p): ?>
                        <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer hover:text-black">
                            <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" class="rounded text-[#7D2431] focus:ring-[#7D2431]" <?= in_array($p['name'], ['registration.view', 'payment.view', 'payment.approve', 'payment.reject']) ? 'checked' : '' ?>>
                            <span><strong>[<?= e($p['name']) ?>]</strong> - <?= e($p['display_name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="flex gap-2 justify-end pt-3">
                <button type="button" onclick="closeCreateModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold">
                    বাতিল
                </button>
                <button type="submit" class="px-5 py-2 bg-[#7D2431] hover:bg-[#601923] text-white rounded-xl text-xs font-bold shadow-md transition">
                    মডারেটর যুক্ত করুন
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Permissions Modal -->
<div id="permModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-blue-200">
        <h3 class="font-bold text-base text-blue-900 mb-1">পারমিশন সম্পাদনা</h3>
        <p class="text-xs text-gray-500 mb-4" id="permModalSub"></p>

        <form method="POST" action="" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_permissions">
            <input type="hidden" name="mod_id" id="permModId">

            <div class="space-y-2 max-h-60 overflow-y-auto p-3 bg-blue-50/50 rounded-xl border border-blue-100" id="permList">
                <?php foreach ($allPerms as $p): ?>
                    <label class="flex items-center gap-2 text-xs text-gray-700 cursor-pointer">
                        <input type="checkbox" name="permissions[]" value="<?= $p['id'] ?>" id="perm_chk_<?= $p['id'] ?>" class="rounded text-[#7D2431] focus:ring-[#7D2431]">
                        <span><strong><?= e($p['name']) ?></strong> - <?= e($p['display_name']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <div class="flex gap-2 justify-end pt-3">
                <button type="button" onclick="closePermModal()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-xl text-xs font-semibold">
                    বাতিল
                </button>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold shadow-md transition">
                    পারমিশন সংরক্ষণ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reset Password Modal -->
<div id="resetPassModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl max-w-sm w-full p-6 shadow-2xl border border-gray-200">
        <h3 class="font-bold text-base text-gray-900 mb-2">পাসওয়ার্ড রিসেট করুন</h3>
        <p class="text-xs text-gray-500 mb-4" id="resetPassSub"></p>

        <form method="POST" action="" class="space-y-3">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset_password">
            <input type="hidden" name="mod_id" id="resetPassModId">

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">নতুন পাসওয়ার্ড *</label>
                <input type="password" name="new_password" required minlength="6" class="w-full px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none" placeholder="••••••••">
            </div>

            <div class="flex gap-2 justify-end pt-2">
                <button type="button" onclick="closeResetPassModal()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-xl text-xs font-semibold">
                    বাতিল
                </button>
                <button type="submit" class="px-5 py-2 bg-[#7D2431] text-white rounded-xl text-xs font-bold shadow-md transition">
                    পাসওয়ার্ড পরিবর্তন করুন
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() { document.getElementById('createModal').classList.remove('hidden'); }
function closeCreateModal() { document.getElementById('createModal').classList.add('hidden'); }

function openPermModal(id, name, assignedPerms) {
    document.getElementById('permModId').value = id;
    document.getElementById('permModalSub').innerText = 'মডারেটর: ' + name;
    
    // reset checkboxes
    document.querySelectorAll('#permList input[type="checkbox"]').forEach(cb => cb.checked = false);
    assignedPerms.forEach(pid => {
        const el = document.getElementById('perm_chk_' + pid);
        if (el) el.checked = true;
    });

    document.getElementById('permModal').classList.remove('hidden');
}
function closePermModal() { document.getElementById('permModal').classList.add('hidden'); }

function openResetPassModal(id, name) {
    document.getElementById('resetPassModId').value = id;
    document.getElementById('resetPassSub').innerText = 'মডারেটর: ' + name;
    document.getElementById('resetPassModal').classList.remove('hidden');
}
function closeResetPassModal() { document.getElementById('resetPassModal').classList.add('hidden'); }
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

