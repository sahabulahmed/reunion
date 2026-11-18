<?php
// tools/fix_utf8_data.php

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';

$pdo = Database::getConnection();

echo "Repairing UTF-8 data in MySQL...\n";

// 1. Tenants
$pdo->exec("UPDATE tenants SET name = 'বেরইল শামসুদ্দিন দাখিল মাদ্রাসা' WHERE id = 1");

// 2. Roles
$pdo->exec("UPDATE roles SET display_name = 'প্রধান অ্যাডমিন (Main Admin)', description = 'সম্পূর্ণ সিস্টেমের নিয়ন্ত্রণ ও পরিচালনাকারী' WHERE id = 1");
$pdo->exec("UPDATE roles SET display_name = 'মডারেটর (Moderator)', description = 'নিবন্ধন ও পেমেন্ট যাচাইকারী দল' WHERE id = 2");

// 3. Permissions
$perms = [
    1 => ['নিবন্ধন তালিকা দেখা', 'সকল নিবন্ধন দেখতে পারবেন'],
    2 => ['নিবন্ধন তথ্য সম্পাদনা', 'শিক্ষার্থীর নিবন্ধন তথ্য পরিবর্তন করতে পারবেন'],
    3 => ['নিবন্ধন বাতিল করা', 'অনাকাঙ্ক্ষিত নিবন্ধন মুছে বা বাতিল করতে পারবেন'],
    4 => ['পেমেন্ট তালিকা দেখা', 'সকল পেমেন্ট ও ট্রানজেকশন দেখতে পারবেন'],
    5 => ['পেমেন্ট অনুমোদন (Approve)', 'পেমেন্ট যাচাই করে অনুমোদন দিতে পারবেন'],
    6 => ['পেমেন্ট বাতিল (Reject)', 'ভুল বা অসত্য পেমেন্ট বাতিল করতে পারবেন'],
    7 => ['মডারেটর পরিচালনা', 'নতুন মডারেটর তৈরি ও পারমিশন নির্ধারণ'],
    8 => ['আর্থিক ও সার্বিক রিপোর্ট দেখা', 'মোট কালেকশন ও আর্থিক পরিসংখ্যান দেখা'],
    9 => ['অডিট লগ দেখা', 'সিস্টেমের সকল কার্যক্রমের হিস্ট্রি দেখা'],
    10 => ['ইভেন্ট ও ফি সেটিংস পরিবর্তন', 'নিবন্ধন ফি, বিকাশ/নগদ নম্বর ও ইভেন্ট সেটিংস পরিচালনা'],
];

$upPerm = $pdo->prepare("UPDATE permissions SET display_name = ?, description = ? WHERE id = ?");
foreach ($perms as $id => $data) {
    $upPerm->execute([$data[0], $data[1], $id]);
}

// 4. Event Settings
$settings = [
    'event_title'          => ['সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭', 'অনুষ্ঠানের নাম'],
    'madrasa_name'         => ['বেরইল শামসুদ্দিন দাখিল মাদ্রাসা', 'মাদ্রাসার নাম'],
    'event_date_text'      => ['১১-১২ মার্চ ২০২৭', 'অনুষ্ঠানের তারিখ টেক্সট'],
    'event_date_start'     => ['2027-03-11 09:00:00', 'কাউন্টডাউন টার্গেট সময়'],
    'event_venue'          => ['মাদ্রাসা প্রাঙ্গণ, বেরইল, মাগুরা', 'অনুষ্ঠানের স্থান'],
    'former_student_fee'   => ['1000', 'সাবেক শিক্ষার্থী নিবন্ধন ফি (টাকা)'],
    'current_student_fee'  => ['500', 'বর্তমান শিক্ষার্থী নিবন্ধন ফি (টাকা)'],
    'guest_fee'            => ['500', 'অতিথি ফি জনপ্রতি (টাকা)'],
    'bkash_number'         => ['01712-345678', 'বিকাশ নম্বর (Personal / Send Money)'],
    'nagad_number'         => ['01912-345678', 'নগদ নম্বর (Personal / Send Money)'],
    'contact_phone'        => ['01712-345678', 'হেল্পলাইন মোবাইল নম্বর'],
    'contact_email'        => ['reunion2027@gmail.com', 'অফিসিয়াল ইমেইল'],
    'contact_facebook'     => ['https://facebook.com/reunion2027', 'ফেসবুক পেজ / গ্রুপ'],
    'registration_enabled' => ['1', 'অনলাইন নিবন্ধন চালু (1) বা বন্ধ (0)'],
];

$upSet = $pdo->prepare("UPDATE event_settings SET setting_value = ?, description = ? WHERE setting_key = ?");
foreach ($settings as $key => $vals) {
    $upSet->execute([$vals[0], $vals[1], $key]);
}

// 5. Update initial admin name
$pdo->exec("UPDATE users SET name = 'প্রধান অ্যাডমিন' WHERE username = 'admin'");

echo "UTF-8 data repair completed successfully!\n";

