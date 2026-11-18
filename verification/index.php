<?php
// verification/index.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/RegistrationService.php';

$token = trim($_GET['token'] ?? '');
$reg = null;
if (!empty($token)) {
    $service = new RegistrationService();
    $reg = $service->findByQrToken($token);
}

$title = "নিবন্ধন যাচাইকরণ | সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী ২০২৭";
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($title) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
    </style>
</head>
<body class="bg-[#FFF9ED] min-h-screen text-gray-800 flex flex-col justify-between">

    <!-- Top Simple Bar -->
    <header class="bg-[#7D2431] text-white py-3 px-4 shadow-md">
        <div class="max-w-4xl mx-auto flex items-center justify-between">
            <div class="flex items-center space-x-3 space-x-reverse">
                <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-10 h-10 rounded-full border border-yellow-300">
                <div>
                    <h1 class="font-bold text-base md:text-lg">বেরইল শামসুদ্দিন দাখিল মাদ্রাসা</h1>
                    <p class="text-xs text-yellow-200">সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭</p>
                </div>
            </div>
            <a href="<?= getBaseUrl() ?>" class="text-xs md:text-sm bg-[#C69A45] hover:bg-[#b0873a] text-white font-semibold py-1.5 px-3 rounded-full transition">
                <i class="fa-solid fa-house mr-1"></i> মূল ওয়েবসাইট
            </a>
        </div>
    </header>

    <!-- Verification Content -->
    <main class="max-w-xl mx-auto w-full p-4 my-8">
        <?php if ($reg): ?>
            <div class="bg-white rounded-3xl shadow-xl border border-amber-100 overflow-hidden text-center p-6 md:p-8">
                
                <?php if ($reg['payment_status'] === 'approved' && $reg['registration_status'] === 'confirmed'): ?>
                    <!-- Valid Verified Badge -->
                    <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-emerald-200 animate-bounce">
                        <i class="fa-solid fa-check text-4xl"></i>
                    </div>
                    <span class="inline-block px-4 py-1.5 bg-emerald-50 text-emerald-700 font-bold text-sm rounded-full border border-emerald-300 mb-2">
                        ✓ নিবন্ধিত ও অনুমোদিত (VERIFIED & CONFIRMED)
                    </span>
                    <h2 class="text-2xl font-bold text-[#7D2431] mt-2 mb-1"><?= e($reg['student_name']) ?></h2>
                    <p class="text-sm text-gray-500 mb-6">রেজিস্ট্রেশন কোড: <span class="font-mono font-bold text-gray-800"><?= e($reg['registration_code']) ?></span></p>

                    <!-- Details Card -->
                    <div class="bg-[#FFF9ED] rounded-2xl p-4 border border-amber-200 text-left text-sm space-y-2.5">
                        <div class="flex justify-between py-1 border-b border-amber-200/60">
                            <span class="text-gray-600">অনুষ্ঠানের নাম:</span>
                            <span class="font-semibold text-gray-800">সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী ২০২৭</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-amber-200/60">
                            <span class="text-gray-600">প্রতিষ্ঠানের নাম:</span>
                            <span class="font-semibold text-gray-800">বেরইল শামসুদ্দিন দাখিল মাদ্রাসা</span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-amber-200/60">
                            <span class="text-gray-600">শিক্ষার্থীর ধরন:</span>
                            <span class="font-semibold text-gray-800"><?= $reg['student_category'] === 'former' ? 'সাবেক শিক্ষার্থী' : 'বর্তমান শিক্ষার্থী' ?></span>
                        </div>
                        <div class="flex justify-between py-1 border-b border-amber-200/60">
                            <span class="text-gray-600">টি-শার্ট সাইজ:</span>
                            <span class="font-semibold text-[#7D2431]"><?= e($reg['tshirt_size']) ?></span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-gray-600">নিবন্ধিত অতিথি:</span>
                            <span class="font-semibold text-gray-800"><?= en2bn($reg['guest_count']) ?> জন</span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
                        <a href="<?= getBaseUrl() ?>/receipt.php?code=<?= urlencode($reg['registration_code']) ?>" target="_blank" class="px-6 py-2.5 bg-[#7D2431] hover:bg-[#621a24] text-white rounded-xl font-semibold shadow-md transition flex items-center justify-center gap-2">
                            <i class="fa-solid fa-file-invoice"></i> অফিশিয়াল রশিদ দেখুন
                        </a>
                    </div>

                <?php elseif ($reg['payment_status'] === 'pending'): ?>
                    <!-- Pending Badge -->
                    <div class="w-20 h-20 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-amber-200">
                        <i class="fa-solid fa-clock text-4xl"></i>
                    </div>
                    <span class="inline-block px-4 py-1.5 bg-amber-50 text-amber-700 font-bold text-sm rounded-full border border-amber-300 mb-2">
                        ⏳ পেমেন্ট যাচাইকরণ প্রক্রিয়াধীন (PENDING APPROVAL)
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800 mt-2 mb-1"><?= e($reg['student_name']) ?></h2>
                    <p class="text-sm text-gray-500 mb-4">রেজিস্ট্রেশন কোড: <span class="font-mono font-bold"><?= e($reg['registration_code']) ?></span></p>
                    <p class="text-gray-600 text-sm">আপনার পেমেন্ট তথ্য জমা হয়েছে। আয়োজক কমিটি যাচাই করে অনুমোদন দিলে আপনার অফিশিয়াল রশিদ সক্রিয় হবে।</p>

                <?php else: ?>
                    <!-- Rejected Badge -->
                    <div class="w-20 h-20 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-4 border-4 border-red-200">
                        <i class="fa-solid fa-circle-xmark text-4xl"></i>
                    </div>
                    <span class="inline-block px-4 py-1.5 bg-red-50 text-red-700 font-bold text-sm rounded-full border border-red-300 mb-2">
                        ❌ পেমেন্ট বাতিল বা স্থগিত (REJECTED)
                    </span>
                    <h2 class="text-2xl font-bold text-gray-800 mt-2 mb-1"><?= e($reg['student_name']) ?></h2>
                    <p class="text-sm text-gray-500 mb-4">রেজিস্ট্রেশন কোড: <span class="font-mono font-bold"><?= e($reg['registration_code']) ?></span></p>
                    <p class="text-gray-600 text-sm">পেমেন্ট তথ্যে অসঙ্গতি থাকায় এটি অনুমোদিত হয়নি। অনুগ্রহ করে আয়োজকদের সাথে যোগাযোগ করুন।</p>
                <?php endif; ?>

            </div>
        <?php else: ?>
            <div class="bg-white rounded-3xl shadow-xl border border-red-100 overflow-hidden text-center p-8">
                <div class="w-20 h-20 bg-red-100 text-red-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fa-solid fa-triangle-exclamation text-4xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 mb-2">অবৈধ বা মেয়াদোত্তীর্ণ QR কোড</h2>
                <p class="text-gray-600 text-sm mb-6">এই কিউআর কোডের বিপরীতে কোনো নিবন্ধনের তথ্য পাওয়া যায়নি। অনুগ্রহ করে সঠিক কিউআর কোড স্ক্যান করুন।</p>
                <a href="<?= getBaseUrl() ?>" class="inline-block px-6 py-2.5 bg-[#7D2431] text-white font-semibold rounded-xl hover:bg-[#601923] transition">
                    হোম পেজে ফিরুন
                </a>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-[#155B43] text-emerald-100 text-center py-4 text-xs">
        © ২০২৭ বেরইল শামসুদ্দিন দাখিল মাদ্রাসা। সর্বস্বত্ব সংরক্ষিত।
    </footer>

</body>
</html>

