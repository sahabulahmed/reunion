<?php
// auth/login.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

$authService = new AuthService();

// If no admin exists, redirect to setup
if (!$authService->hasAdmin()) {
    header('Location: ' . getBaseUrl() . '/auth/setup-admin.php');
    exit;
}

// If already logged in
if (isLoggedIn()) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: ' . getBaseUrl() . '/admin/dashboard.php');
    } else {
        header('Location: ' . getBaseUrl() . '/moderator/dashboard.php');
    }
    exit;
}

$error = null;
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        $error = 'নিরাপত্তা টোকেন মেয়াদোত্তীর্ণ হয়েছে। আবার চেষ্টা করুন।';
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error = 'ইউজারনেম ও পাসওয়ার্ড পূরণ করুন।';
        } else {
            $result = $authService->login($username, $password);
            if ($result['success']) {
                if ($_SESSION['user_role'] === 'admin') {
                    header('Location: ' . getBaseUrl() . '/admin/dashboard.php');
                } else {
                    header('Location: ' . getBaseUrl() . '/moderator/dashboard.php');
                }
                exit;
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অ্যাডমিন ও মডারেটর লগইন | সুবর্ণ জয়ন্তী ২০২৭</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>body { font-family: 'Hind Siliguri', sans-serif; }</style>
</head>
<body class="bg-[#FFF9ED] min-h-screen flex items-center justify-center p-4">

    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl border border-amber-200 p-8 relative overflow-hidden">
        
        <!-- Header -->
        <div class="text-center mb-6">
            <a href="<?= getBaseUrl() ?>" class="inline-block">
                <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-16 h-16 mx-auto rounded-full border-2 border-[#7D2431] shadow-sm mb-2 hover:scale-105 transition">
            </a>
            <h1 class="text-2xl font-bold text-[#7D2431]">বেরইল শামসুদ্দিন দাখিল মাদ্রাসা</h1>
            <p class="text-sm font-semibold text-[#C69A45]">সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭</p>
            <div class="inline-block mt-2 px-3 py-1 bg-amber-50 text-[#7D2431] text-xs font-bold rounded-full border border-amber-200">
                অ্যাডমিন ও মডারেটর পোর্টাল
            </div>
        </div>

        <?php if ($flash): ?>
            <div class="p-3 mb-4 rounded-xl text-sm font-medium <?= $flash['type'] === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-red-50 text-red-800 border border-red-200' ?>">
                <?= e($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="p-3 mb-4 rounded-xl text-sm font-medium bg-red-50 text-red-800 border border-red-200 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-500"></i>
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <?= csrf_field() ?>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">ইউজারনেম অথবা ইমেইল</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-user"></i>
                    </span>
                    <input type="text" name="username" required autocomplete="username" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="আপনার ইউজারনেম দিন">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-gray-700 mb-1">গোপন পাসওয়ার্ড</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-gray-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" required autocomplete="current-password" class="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="••••••••">
                </div>
            </div>

            <button type="submit" class="w-full py-3 bg-[#7D2431] hover:bg-[#601923] text-white font-bold rounded-xl shadow-lg transition duration-200 flex items-center justify-center gap-2">
                <i class="fa-solid fa-right-to-bracket"></i> প্রবেশ করুন
            </button>
        </form>

        <div class="mt-6 text-center border-t border-gray-100 pt-4">
            <a href="<?= getBaseUrl() ?>" class="text-xs text-gray-500 hover:text-[#7D2431] transition">
                <i class="fa-solid fa-arrow-left mr-1"></i> মূল ওয়েবসাইটে ফিরে যান
            </a>
        </div>
    </div>

</body>
</html>

