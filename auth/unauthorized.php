<?php
// auth/unauthorized.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অননুমোদিত প্রবেশাধিকার | ৪০৩</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Hind Siliguri', sans-serif; }</style>
</head>
<body class="bg-[#FFF9ED] min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white p-8 rounded-2xl shadow-xl border border-red-200 text-center">
        <div class="w-16 h-16 bg-red-100 text-[#7D2431] rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">
            ⚠️
        </div>
        <h1 class="text-2xl font-bold text-[#7D2431] mb-2">অননুমোদিত প্রবেশাধিকার</h1>
        <p class="text-gray-600 mb-6">দুঃখিত, এই পেজটি বা মডিউলটি দেখার জন্য আপনার পর্যাপ্ত অনুমতি নেই। কোনো ভুল মনে হলে প্রধান অ্যাডমিনের সাথে যোগাযোগ করুন।</p>
        <div class="flex gap-4 justify-center">
            <a href="<?= getBaseUrl() ?>/admin/dashboard.php" class="px-5 py-2.5 bg-[#7D2431] text-white rounded-lg hover:bg-[#631c26] transition font-semibold">
                ড্যাশবোর্ডে ফিরুন
            </a>
            <a href="<?= getBaseUrl() ?>" class="px-5 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                মূল ওয়েবসাইট
            </a>
        </div>
    </div>
</body>
</html>

