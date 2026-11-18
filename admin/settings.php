<?php
// admin/settings.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Only Admin can manage settings
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    http_response_code(403);
    require_once APP_ROOT . '/auth/unauthorized.php';
    exit;
}

$pageTitle = 'ইভেন্ট ও ফি সেটিংস';
$pdo = Database::getConnection();

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrf)) {
        setFlash('danger', 'নিরাপত্তা টোকেন মেয়াদোত্তীর্ণ হয়েছে।');
    } else {
        $keys = [
            'event_title', 'madrasa_name', 'event_date_text', 'event_venue',
            'former_student_fee', 'current_student_fee', 'guest_fee',
            'bkash_number', 'nagad_number', 'contact_phone', 'contact_email',
            'contact_facebook', 'registration_enabled', 'event_date_start'
        ];

        $oldSettings = [];
        $newSettings = [];

        foreach ($keys as $k) {
            $oldVal = getSetting($k);
            $newVal = trim($_POST[$k] ?? ($k === 'registration_enabled' ? '0' : ''));
            if ($k === 'registration_enabled') {
                $newVal = isset($_POST['registration_enabled']) ? '1' : '0';
            }
            updateSetting($k, $newVal);
            $oldSettings[$k] = $oldVal;
            $newSettings[$k] = $newVal;
        }

        logAudit('settings.updated', 'event_settings', '1', $oldSettings, $newSettings);
        setFlash('success', 'সকল সেটিংস সফলভাবে সংরক্ষণ করা হয়েছে।');
    }
    header('Location: settings.php');
    exit;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="bg-white p-6 rounded-3xl border border-gray-200 shadow-sm mb-6">
        <div class="border-b pb-4 mb-6">
            <h2 class="text-xl font-bold text-gray-900">ইভেন্ট ও ফি কনফিগারেশন</h2>
            <p class="text-xs text-gray-500 mt-1">এই পেজ থেকে পুনর্মিলনী অনুষ্ঠানের তথ্য, রেজিস্ট্রেশন ফি এবং পেমেন্ট নম্বর পরিবর্তন করতে পারবেন।</p>
        </div>

        <form method="POST" action="" class="space-y-6">
            <?= csrf_field() ?>

            <!-- System Toggle -->
            <div class="bg-amber-50 p-4 rounded-2xl border border-amber-200 flex items-center justify-between">
                <div>
                    <h4 class="font-bold text-sm text-[#7D2431]">অনলাইন নিবন্ধন চালু / বন্ধ</h4>
                    <p class="text-xs text-gray-600">এটি বন্ধ করলে ওয়েবসাইটে নিবন্ধন ফরম জমা দেওয়া যাবে না।</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="registration_enabled" value="1" <?= getSetting('registration_enabled', '1') === '1' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-300 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-[#155B43]"></div>
                </label>
            </div>

            <!-- Event Basic Info -->
            <div>
                <h3 class="text-sm font-bold text-[#7D2431] uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-regular fa-calendar-check text-[#C69A45]"></i> অনুষ্ঠানের তথ্য
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">অনুষ্ঠানের শিরোনাম</label>
                        <input type="text" name="event_title" value="<?= e(getSetting('event_title')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">মাদ্রাসার পূর্ণ নাম</label>
                        <input type="text" name="madrasa_name" value="<?= e(getSetting('madrasa_name')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">তারিখ (প্রদর্শনের জন্য)</label>
                        <input type="text" name="event_date_text" value="<?= e(getSetting('event_date_text')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">কাউন্টডাউন টার্গেট সময় (YYYY-MM-DD HH:MM:SS)</label>
                        <input type="text" name="event_date_start" value="<?= e(getSetting('event_date_start', '2027-03-11 09:00:00')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">অনুষ্ঠানের স্থান</label>
                        <input type="text" name="event_venue" value="<?= e(getSetting('event_venue')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Fee Configuration -->
            <div class="border-t pt-5">
                <h3 class="text-sm font-bold text-[#7D2431] uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-coins text-[#C69A45]"></i> নিবন্ধন ফি কাঠামো (টাকায়)
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">সাবেক শিক্ষার্থী ফি (৳)</label>
                        <input type="number" name="former_student_fee" value="<?= e(getSetting('former_student_fee', '1000')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">বর্তমান শিক্ষার্থী ফি (৳)</label>
                        <input type="number" name="current_student_fee" value="<?= e(getSetting('current_student_fee', '500')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">অতিথি ফি জনপ্রতি (৳)</label>
                        <input type="number" name="guest_fee" value="<?= e(getSetting('guest_fee', '500')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Payment Numbers -->
            <div class="border-t pt-5">
                <h3 class="text-sm font-bold text-[#7D2431] uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-mobile-screen text-[#C69A45]"></i> ম্যানুয়াল পেমেন্ট গ্রহণ নম্বর
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">বিকাশ পার্সোনাল / সেন্ড মানি নম্বর</label>
                        <input type="text" name="bkash_number" value="<?= e(getSetting('bkash_number')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">নগদ পার্সোনাল / সেন্ড মানি নম্বর</label>
                        <input type="text" name="nagad_number" value="<?= e(getSetting('nagad_number')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                </div>
            </div>

            <!-- Contact Information -->
            <div class="border-t pt-5">
                <h3 class="text-sm font-bold text-[#7D2431] uppercase tracking-wider mb-3 flex items-center gap-2">
                    <i class="fa-solid fa-phone-volume text-[#C69A45]"></i> হেল্পলাইন ও যোগাযোগ
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">হেল্পলাইন মোবাইল নম্বর</label>
                        <input type="text" name="contact_phone" value="<?= e(getSetting('contact_phone')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">অফিসিয়াল ইমেইল</label>
                        <input type="email" name="contact_email" value="<?= e(getSetting('contact_email')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">ফেসবুক পেজ / আইডি</label>
                        <input type="text" name="contact_facebook" value="<?= e(getSetting('contact_facebook')) ?>" required class="w-full px-3.5 py-2.5 rounded-xl border border-gray-300 text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                    </div>
                </div>
            </div>

            <div class="border-t pt-4 text-right">
                <button type="submit" class="px-6 py-2.5 bg-[#7D2431] hover:bg-[#601923] text-white font-bold rounded-xl shadow-lg transition duration-200">
                    সেটিংস সংরক্ষণ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

