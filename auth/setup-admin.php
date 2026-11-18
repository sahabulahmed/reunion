<?php
// auth/setup-admin.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

$authService = new AuthService();

// If admin already exists, disallow setup
if ($authService->hasAdmin()) {
    header('Location: ' . getBaseUrl() . '/auth/login.php');
    exit;
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = 'অনুগ্রহ করে সকল আবশ্যক তথ্য পূরণ করুন।';
    } elseif ($password !== $confirm) {
        $error = 'পাসওয়ার্ড দুটি মেলেনি!';
    } else {
        $result = $authService->createAdmin($name, $username, $email, $phone, $password);
        if ($result['success']) {
            setFlash('success', 'প্রধান অ্যাডমিন অ্যাকাউন্ট সফলভাবে তৈরি হয়েছে! এখন লগইন করুন।');
            header('Location: ' . getBaseUrl() . '/auth/login.php');
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>প্রধান অ্যাডমিন সেটআপ | সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী ২০২৭</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Hind Siliguri', sans-serif; }</style>
</head>
<body class="bg-[#FFF9ED] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-3xl shadow-2xl border border-amber-200 p-8">
        <div class="text-center mb-6">
            <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-16 h-16 mx-auto rounded-full border-2 border-[#7D2431] shadow-sm mb-2">
            <h1 class="text-2xl font-bold text-[#7D2431]">বেরইল শামসুদ্দিন দাখিল মাদ্রাসা</h1>
            <p class="text-sm font-semibold text-[#C69A45]">সুবর্ণ জয়ন্তী ২০২৭ - প্রাথমিক অ্যাডমিন সেটআপ</p>
            <p class="text-xs text-gray-500 mt-1">প্রথমবারের মতো প্রধান অ্যাডমিন (Super Admin) অ্যাকাউন্ট তৈরি করুন</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 rounded text-sm mb-4">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">অ্যাডমিনের পূর্ণ নাম *</label>
                <input type="text" name="name" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="যেমন: মাও. আব্দুর রহমান">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">ইউজারনেম (Username) *</label>
                <input type="text" name="username" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="admin">
            </div>

            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">ইমেইল ঠিকানা *</label>
                    <input type="email" name="email" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="admin@madrasa.com">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">মোবাইল নম্বর</label>
                    <input type="text" name="phone" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="017XXXXXXXX">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">পাসওয়ার্ড (কমপক্ষে ৮ অক্ষর) *</label>
                <input type="password" name="password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="••••••••">
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 mb-1">পাসওয়ার্ড নিশ্চিত করুন *</label>
                <input type="password" name="confirm_password" required minlength="8" class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-[#7D2431] text-sm" placeholder="••••••••">
            </div>

            <button type="submit" class="w-full py-3 bg-[#7D2431] hover:bg-[#631c26] text-white font-bold rounded-xl shadow-lg transition duration-200">
                প্রধান অ্যাডমিন তৈরি করুন
            </button>
        </form>
    </div>
</body>
</html>

