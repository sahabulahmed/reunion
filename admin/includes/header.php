<?php
// admin/includes/header.php

declare(strict_types=1);

require_once __DIR__ . '/../../config/app.php';
require_once __DIR__ . '/../../app/Helpers/functions.php';

if (!isLoggedIn()) {
    header('Location: ' . getBaseUrl() . '/auth/login.php');
    exit;
}

$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'অ্যাডমিন ড্যাশবোর্ড';

$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> | সুবর্ণ জয়ন্তী ২০২৭</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
        .sidebar-active {
            background-color: #7D2431;
            color: #ffffff !important;
            font-weight: 600;
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen text-gray-800 flex">

    <!-- Mobile Sidebar Backdrop -->
    <div id="sidebar-backdrop" onclick="toggleSidebar()" class="fixed inset-0 bg-black/50 z-30 hidden lg:hidden"></div>

    <!-- Sidebar -->
    <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 w-64 bg-white border-r border-gray-200 z-40 transform -translate-x-full lg:translate-x-0 transition-transform duration-200 flex flex-col justify-between">
        <div>
            <!-- Brand -->
            <div class="p-5 border-b border-gray-100 flex items-center gap-3 bg-[#FFF9ED]">
                <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-10 h-10 rounded-full border border-[#7D2431]">
                <div class="overflow-hidden">
                    <h2 class="font-bold text-sm text-[#7D2431] truncate">বেরইল শামসুদ্দিন দাখিল মাদ্রাসা</h2>
                    <p class="text-[11px] text-[#C69A45] font-semibold">সুবর্ণ জয়ন্তী ড্যাশবোর্ড</p>
                </div>
            </div>

            <!-- Navigation Links -->
            <nav class="p-3 space-y-1 text-sm font-medium text-gray-700">
                <a href="<?= getBaseUrl() ?>/admin/dashboard.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'dashboard.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-gauge-high w-5 text-center"></i>
                    <span>ড্যাশবোর্ড</span>
                </a>

                <?php if (hasPermission('registration.view')): ?>
                <a href="<?= getBaseUrl() ?>/admin/registrations.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'registrations.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-users w-5 text-center"></i>
                    <span>সকল নিবন্ধন</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('payment.view')): ?>
                <a href="<?= getBaseUrl() ?>/admin/payments.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'payments.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-money-check-dollar w-5 text-center"></i>
                    <span>পেমেন্ট ভেরিফিকেশন</span>
                </a>
                <?php endif; ?>

                <?php if (hasPermission('reports.view')): ?>
                <a href="<?= getBaseUrl() ?>/admin/reports.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'reports.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    <span>মোট কালেকশন ও রিপোর্ট</span>
                </a>
                <?php endif; ?>

                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= getBaseUrl() ?>/admin/moderators.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'moderators.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-user-shield w-5 text-center"></i>
                    <span>মডারেটর ও পারমিশন</span>
                </a>

                <a href="<?= getBaseUrl() ?>/admin/settings.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'settings.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-sliders w-5 text-center"></i>
                    <span>ইভেন্ট ও ফি সেটিংস</span>
                </a>

                <a href="<?= getBaseUrl() ?>/admin/audit-logs.php" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition <?= $currentPage === 'audit-logs.php' ? 'sidebar-active' : 'hover:bg-amber-50 hover:text-[#7D2431]' ?>">
                    <i class="fa-solid fa-list-check w-5 text-center"></i>
                    <span>অডিট লগ ও হিস্ট্রি</span>
                </a>
                <?php endif; ?>
            </nav>
        </div>

        <!-- User Info / Logout -->
        <div class="p-4 border-t border-gray-100 bg-gray-50">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-9 h-9 rounded-full bg-[#7D2431] text-white flex items-center justify-center font-bold text-sm">
                    <?= mb_substr($user['name'], 0, 1) ?>
                </div>
                <div class="overflow-hidden">
                    <p class="text-xs font-bold text-gray-900 truncate"><?= e($user['name']) ?></p>
                    <p class="text-[10px] text-gray-500 uppercase tracking-wider font-semibold"><?= e($user['role'] === 'admin' ? 'মেইন অ্যাডমিন' : 'মডারেটর') ?></p>
                </div>
            </div>
            <div class="flex gap-2">
                <a href="<?= getBaseUrl() ?>" target="_blank" class="flex-1 py-1.5 px-2 bg-white hover:bg-gray-100 border border-gray-300 rounded-lg text-xs font-medium text-center text-gray-700 transition">
                    <i class="fa-solid fa-globe mr-1"></i> ওয়েবসাইট
                </a>
                <a href="<?= getBaseUrl() ?>/auth/logout.php" class="py-1.5 px-3 bg-red-50 hover:bg-red-100 border border-red-200 rounded-lg text-xs font-medium text-center text-red-600 transition" title="লগআউট">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content Area -->
    <div class="flex-1 flex flex-col min-h-screen overflow-x-hidden">
        
        <!-- Topbar -->
        <header class="bg-white border-b border-gray-200 py-3.5 px-4 md:px-8 flex items-center justify-between sticky top-0 z-20 shadow-sm">
            <div class="flex items-center gap-3">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100">
                    <i class="fa-solid fa-bars text-lg"></i>
                </button>
                <h1 class="text-base md:text-lg font-bold text-gray-800"><?= e($pageTitle) ?></h1>
            </div>

            <div class="flex items-center gap-3">
                <span class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1 bg-amber-50 text-[#7D2431] text-xs font-bold rounded-full border border-amber-200">
                    <i class="fa-regular fa-calendar-check"></i> ১১ মার্চ ২০২৭
                </span>
                <a href="<?= getBaseUrl() ?>" target="_blank" class="text-xs font-semibold text-[#7D2431] hover:underline flex items-center gap-1">
                    <i class="fa-solid fa-external-link text-[11px]"></i> পাবলিক সাইট
                </a>
            </div>
        </header>

        <!-- Flash message -->
        <div class="px-4 md:px-8 pt-4">
            <?php if ($flash): ?>
                <div class="p-3 rounded-xl text-sm font-medium <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?> flex items-center justify-between">
                    <span><?= e($flash['message']) ?></span>
                    <button onclick="this.parentElement.remove()" class="text-xs font-bold text-gray-500 hover:text-gray-700">&times;</button>
                </div>
            <?php endif; ?>
        </div>

        <!-- Body Slot Begins -->
        <main class="flex-1 p-4 md:p-8">

