<?php
// index.php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Helpers/functions.php';

// Fetch dynamic event settings
$eventTitle        = getSetting('event_title', 'সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭');
$madrasaName       = getSetting('madrasa_name', 'বেরইল শামসুদ্দিন দাখিল মাদ্রাসা');
$eventDateText     = getSetting('event_date_text', '১১-১২ মার্চ ২০২৭');
$eventDateStart    = getSetting('event_date_start', '2027-03-11 09:00:00');
$eventVenue        = getSetting('event_venue', 'মাদ্রাসা প্রাঙ্গণ, বেরইল, মাগুরা');
$formerFee         = (float)getSetting('former_student_fee', '1000');
$currentFee        = (float)getSetting('current_student_fee', '500');
$guestFee          = (float)getSetting('guest_fee', '500');
$bkashNumber       = getSetting('bkash_number', '01712-345678');
$nagadNumber       = getSetting('nagad_number', '01912-345678');
$contactPhone      = getSetting('contact_phone', '01712-345678');
$contactEmail      = getSetting('contact_email', 'reunion2027@gmail.com');
$contactFacebook   = getSetting('contact_facebook', 'reunion2027');
$isRegEnabled      = getSetting('registration_enabled', '1') === '1';

// Database stats for counters
$pdo = Database::getConnection();
$dbFormer = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE student_category = 'former'")->fetchColumn();
$dbCurrent = (int)$pdo->query("SELECT COUNT(*) FROM registrations WHERE student_category = 'current'")->fetchColumn();
$dbTotal = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();

// Base counts matching mockup baseline + dynamic DB registrations
$statFormer = 1250 + $dbFormer;
$statCurrent = 980 + $dbCurrent;
$statTotal = 2230 + $dbTotal;
?>
<!DOCTYPE html>
<html lang="bn" class="scroll-smooth scroll-pt-14 md:scroll-pt-16">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($madrasaName) ?> - <?= e($eventTitle) ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: {
                            800: '#7D2431',
                            900: '#641B26',
                        },
                        gold: {
                            500: '#C69A45',
                            600: '#A88035',
                        },
                        cream: '#FFF9ED',
                        heritageGreen: '#155B43'
                    }
                }
            }
        }
    </script>

    <!-- Google Fonts: Hind Siliguri & Noto Sans Bengali -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Noto+Sans+Bengali:wght@500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
            background-color: #FFF9ED;
            color: #2D3748;
            overflow-x: hidden;
        }

        .font-bengali-clear {
            font-family: 'Noto Sans Bengali', 'Hind Siliguri', sans-serif;
            font-feature-settings: normal;
        }

        /* Subtle Islamic arch / damask watermark pattern */
        .heritage-bg {
            background-color: #FFF9ED;
            background-image: radial-gradient(#C69A45 0.5px, transparent 0.5px), radial-gradient(#7D2431 0.5px, #FFF9ED 0.5px);
            background-size: 24px 24px;
            background-position: 0 0, 12px 12px;
            background-opacity: 0.05;
        }

        .hero-banner-curve {
            border-bottom-left-radius: 50% 30px;
            border-bottom-right-radius: 50% 30px;
        }

        .input-icon-group {
            position: relative;
        }
        .input-icon-group input, .input-icon-group select {
            padding-left: 2.75rem;
        }
        .input-icon-badge {
            position: absolute;
            left: 0.35rem;
            top: 50%;
            transform: translateY(-50%);
            width: 2rem;
            height: 2rem;
            background-color: #7D2431;
            color: #ffffff;
            border-radius: 9999px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }
    </style>
</head>
<body class="bg-[#FFF9ED] text-gray-800 pb-16 md:pb-0">

    <!-- ==================== 1. TOP NAVBAR ==================== -->
    <nav class="sticky top-0 z-50 bg-[#7D2431] text-white shadow-lg border-b border-[#C69A45]/30">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-14 md:h-16">
                
                <!-- Left: Logo & Madrasa Title -->
                <div class="flex items-center gap-2.5 sm:gap-3">
                    <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-9 h-9 md:w-11 md:h-11 rounded-full border-2 border-yellow-300 shadow-md object-contain bg-white shrink-0">
                    <div>
                        <a href="#home" class="font-bold text-sm md:text-base leading-tight block hover:text-yellow-200 transition">
                            <?= e($madrasaName) ?>
                        </a>
                        <span class="text-[10px] md:text-xs text-yellow-200 font-medium tracking-wide">
                            <?= e($eventTitle) ?>
                        </span>
                    </div>
                </div>

                <!-- Center: Navigation Links (Desktop) -->
                <div class="hidden lg:flex items-center gap-1.5 xl:gap-2.5 text-xs xl:text-sm font-medium">
                    <a href="#home" class="px-3 py-1.5 rounded-full hover:bg-white/10 text-yellow-200 hover:text-yellow-300 transition flex items-center gap-1.5">
                        <i class="fa-solid fa-house text-xs"></i>
                        <span>হোম</span>
                    </a>
                    <a href="#about" class="px-3 py-1.5 rounded-full hover:bg-white/10 hover:text-yellow-300 transition">
                        <span>আমাদের আয়োজন</span>
                    </a>
                    <a href="#schedule" class="px-3 py-1.5 rounded-full hover:bg-white/10 hover:text-yellow-300 transition">
                        <span>অনুষ্ঠানের তথ্য</span>
                    </a>
                    <a href="#registration" class="px-3 py-1.5 rounded-full hover:bg-white/10 hover:text-yellow-300 transition">
                        <span>নিবন্ধন</span>
                    </a>
                    <a href="#gallery" class="px-3 py-1.5 rounded-full hover:bg-white/10 hover:text-yellow-300 transition">
                        <span>স্মৃতির অ্যালবাম</span>
                    </a>
                    <a href="#contact" class="px-3 py-1.5 rounded-full hover:bg-white/10 hover:text-yellow-300 transition">
                        <span>যোগাযোগ</span>
                    </a>
                    <button onclick="openStatusModal()" class="px-3 py-1.5 rounded-full bg-black/20 hover:bg-black/30 text-yellow-200 hover:text-yellow-300 transition flex items-center gap-1.5 border border-yellow-400/25">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                        <span>স্ট্যাটাস চেক</span>
                    </button>
                </div>

                <!-- Right: Desktop Action Buttons / Mobile Hamburger -->
                <div class="flex items-center gap-2">
                    <div class="hidden sm:flex items-center gap-2">
                        <a href="#registration" class="bg-[#C69A45] hover:bg-[#b58a38] text-white font-bold px-4 py-1.5 md:py-2 rounded-full text-xs md:text-sm shadow-md transition duration-200 flex items-center gap-1.5 border border-yellow-200/40">
                            <i class="fa-solid fa-pen-to-square"></i> নিবন্ধন করুন
                        </a>
                        <a href="<?= getBaseUrl() ?>/auth/login.php" class="text-xs bg-black/20 hover:bg-black/30 text-yellow-200 px-2.5 py-1.5 rounded-full transition" title="অ্যাডমিন লগইন">
                            <i class="fa-solid fa-lock"></i>
                        </a>
                    </div>

                    <!-- Mobile Hamburger Button (Exact as mockup) -->
                    <button onclick="toggleMobileNav()" class="lg:hidden p-2 text-white hover:text-yellow-300 focus:outline-none transition">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                </div>

            </div>
        </div>
    </nav>

    <!-- Mobile Slide-Out Drawer Navigation Menu -->
    <div id="mobileDrawerBackdrop" onclick="toggleMobileNav()" class="fixed inset-0 bg-black/60 z-50 hidden transition-opacity"></div>
    <div id="mobileDrawer" class="fixed inset-y-0 right-0 w-72 bg-[#7D2431] text-white z-50 transform translate-x-full transition-transform duration-300 shadow-2xl flex flex-col justify-between">
        <div class="p-5">
            <div class="flex items-center justify-between border-b border-white/20 pb-4 mb-4">
                <div class="flex items-center gap-2">
                    <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-8 h-8 rounded-full bg-white">
                    <span class="font-bold text-xs"><?= e($madrasaName) ?></span>
                </div>
                <button onclick="toggleMobileNav()" class="text-white hover:text-yellow-300 text-2xl font-bold">&times;</button>
            </div>
            <nav class="space-y-3 text-sm font-semibold">
                <a href="#home" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-house w-6"></i> হোম</a>
                <a href="#about" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-mosque w-6"></i> আমাদের আয়োজন</a>
                <a href="#schedule" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-calendar-check w-6"></i> অনুষ্ঠানের তথ্য</a>
                <a href="#registration" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-pen-nib w-6"></i> নিবন্ধন</a>
                <a href="#gallery" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-camera-retro w-6"></i> স্মৃতির অ্যালবাম</a>
                <a href="#contact" onclick="toggleMobileNav()" class="block py-2 px-3 rounded-xl hover:bg-white/10 transition"><i class="fa-solid fa-phone-volume w-6"></i> যোগাযোগ</a>
                <button onclick="toggleMobileNav(); openStatusModal();" class="w-full text-left py-2 px-3 rounded-xl hover:bg-white/10 text-yellow-300 transition"><i class="fa-solid fa-magnifying-glass w-6"></i> স্ট্যাটাস চেক</button>
            </nav>
        </div>
        <div class="p-5 border-t border-white/20">
            <a href="<?= getBaseUrl() ?>/auth/login.php" class="block w-full py-2.5 text-center bg-black/30 hover:bg-black/50 text-yellow-200 rounded-xl text-xs font-bold transition">
                <i class="fa-solid fa-lock mr-1.5"></i> অ্যাডমিন লগইন
            </a>
        </div>
    </div>

    <!-- ==================== 2. HERO SECTION ==================== -->
    <section id="home" class="scroll-mt-14 md:scroll-mt-16 relative bg-[#FFF9ED] overflow-hidden pt-0 pb-4 md:py-5 border-b border-[#C69A45]/30">
        
        <!-- Desktop Hero Banner (Wide & Perfectly Fitted Edge-to-Edge) -->
        <div class="hidden md:block max-w-6xl xl:max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <a href="#registration" class="block relative group cursor-pointer" title="নিবন্ধন করতে ক্লিক করুন">
                <div class="rounded-2xl md:rounded-3xl overflow-hidden shadow-2xl border-2 border-[#C69A45]/40">
                    <img src="<?= getBaseUrl() ?>/assets/images/hero_banner.jpg" 
                         alt="সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭" 
                         class="w-full h-auto block transition duration-300 group-hover:scale-[1.01]">
                </div>
            </a>
        </div>

        <!-- Mobile Hero Banner (New Symmetrical Artwork Banner) -->
        <div class="block md:hidden w-full px-0 mx-0 relative">
            <a href="#registration" class="block relative w-full cursor-pointer" title="নিবন্ধন করতে ক্লিক করুন">
                <img src="<?= getBaseUrl() ?>/assets/images/mobile_hero_banner.jpg?v=<?= time() ?>" alt="সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭" class="w-full h-auto block shadow-sm">
            </a>
        </div>

        <!-- Live Countdown Timer & Quick Date Bar (Mobile & Desktop Workable) -->
        <div class="block max-w-6xl xl:max-w-7xl mx-auto px-3 sm:px-4 md:px-6 lg:px-8 mt-2.5 md:mt-3">
            <div class="bg-gradient-to-r from-[#7D2431] via-[#631c26] to-[#7D2431] text-white py-2.5 px-3.5 sm:px-5 md:px-6 rounded-2xl shadow-lg border border-yellow-400/35 flex flex-col md:flex-row items-center justify-between gap-2.5 md:gap-3 text-center md:text-left">
                
                <!-- Event Date & Venue -->
                <div class="flex items-center gap-2.5 sm:gap-3 text-center md:text-left">
                    <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-white/10 flex items-center justify-center text-yellow-300 text-base md:text-lg shrink-0 shadow-inner border border-white/15">
                        <i class="fa-regular fa-calendar-check"></i>
                    </div>
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-1.5 justify-center md:justify-start">
                            <span class="inline-flex items-center px-2.5 py-0.5 bg-yellow-400/20 text-yellow-300 font-bold text-xs sm:text-sm rounded-lg border border-yellow-300/35 tracking-wider font-bengali-clear shadow-sm">
                                <?= e($eventDateText) ?>
                            </span>
                            <span class="text-[11px] sm:text-xs text-yellow-100 font-medium">
                                • <?= e($eventVenue) ?>
                            </span>
                        </div>
                        <h4 class="text-xs sm:text-sm font-bold text-white leading-tight">অনুষ্ঠান শুরু হতে বাকি আর মাত্র:</h4>
                    </div>
                </div>

                <!-- Live Countdown Timer Boxes -->
                <div class="flex items-center justify-center gap-1.5 sm:gap-2 my-0.5" id="countdownTimer">
                    <div class="bg-black/40 border border-yellow-400/50 text-white w-11 sm:w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow">
                        <span id="cd-days" class="text-sm sm:text-base font-black text-yellow-300 leading-none">--</span>
                        <span class="text-[9px] text-gray-200 mt-0.5">দিন</span>
                    </div>
                    <div class="bg-black/40 border border-yellow-400/50 text-white w-11 sm:w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow">
                        <span id="cd-hours" class="text-sm sm:text-base font-black text-yellow-300 leading-none">--</span>
                        <span class="text-[9px] text-gray-200 mt-0.5">ঘণ্টা</span>
                    </div>
                    <div class="bg-black/40 border border-yellow-400/50 text-white w-11 sm:w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow">
                        <span id="cd-mins" class="text-sm sm:text-base font-black text-yellow-300 leading-none">--</span>
                        <span class="text-[9px] text-gray-200 mt-0.5">মিনিট</span>
                    </div>
                    <div class="bg-black/40 border border-yellow-400/50 text-white w-11 sm:w-12 h-12 rounded-xl flex flex-col items-center justify-center shadow">
                        <span id="cd-secs" class="text-sm sm:text-base font-black text-yellow-300 leading-none">--</span>
                        <span class="text-[9px] text-gray-200 mt-0.5">সেকেন্ড</span>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="w-full sm:w-auto">
                    <a href="#registration" class="w-full sm:w-auto px-4 py-1.5 md:py-2 bg-[#C69A45] hover:bg-[#b0873a] text-white font-bold rounded-xl text-xs sm:text-sm shadow-md transition flex items-center justify-center gap-1.5 whitespace-nowrap">
                        <i class="fa-solid fa-pen-to-square"></i> এখনই নিবন্ধন করুন
                    </a>
                </div>

            </div>
        </div>

    </section>

    <!-- ==================== 3. আমাদের আয়োজন ও সময়সূচি ==================== -->
    <section id="about" class="py-8 md:py-14 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Pill Badge (Matching Mockup) -->
        <div class="text-center mb-6">
            <span class="inline-flex items-center gap-2 px-6 py-1.5 bg-[#7D2431] text-white font-bold text-xs sm:text-sm rounded-full shadow border border-yellow-400/40">
                <i class="fa-solid fa-mosque"></i> আমাদের আয়োজন
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
            
            <!-- Left Card: আমাদের আয়োজন -->
            <div class="md:col-span-4 bg-white p-5 sm:p-6 rounded-3xl shadow-md border border-amber-100 flex flex-col justify-between h-full">
                <div>
                    <h3 class="font-bold text-base sm:text-lg text-[#7D2431] mb-2">আমাদের আয়োজন সম্পর্কে</h3>
                    <p class="text-xs md:text-sm text-gray-700 leading-relaxed text-justify">
                        বেরইল শামসুদ্দিন দাখিল মাদ্রাসার সুবর্ণ জয়ন্তী উপলক্ষে এবং পবিত্র ঈদ পুনর্মিলনী উপলক্ষে সকল প্রাক্তন ও বর্তমান শিক্ষার্থীদের নিয়ে এই পুনর্মিলনী আয়োজন। দীর্ঘ ৫০ বছরের শিক্ষা-দীক্ষার স্মৃতি, ভালোবাসা ও বন্ধনের উৎসবই হলো এই মিলনমেলা।
                    </p>
                </div>
                <div class="mt-4">
                    <a href="#schedule" class="inline-block px-5 py-2 bg-[#FFF0D4] hover:bg-[#ffe5bc] text-[#7D2431] border border-amber-300 text-xs font-bold rounded-full shadow-sm transition">
                        বিস্তারিত জানুন ➔
                    </a>
                </div>
            </div>

            <!-- Center Card: Gate Photo with Leaf Framing (exact as in image) -->
            <div class="md:col-span-4 flex justify-center">
                <div class="relative rounded-2xl overflow-hidden shadow-xl border-4 border-white p-1 bg-white transform -rotate-1 hover:rotate-0 transition">
                    <img src="<?= getBaseUrl() ?>/assets/images/gate_photo.png" alt="মাদ্রাসা গেট" class="w-full h-auto object-cover rounded-xl">
                </div>
            </div>

            <!-- Right Card: অনুষ্ঠানের সময়সূচি -->
            <div id="schedule" class="md:col-span-4 bg-white p-5 sm:p-6 rounded-3xl shadow-md border border-amber-100 flex flex-col justify-between h-full">
                <div>
                    <div class="flex items-center gap-2 mb-3">
                        <div class="w-8 h-8 rounded-full bg-amber-100 text-[#7D2431] flex items-center justify-center">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>
                        <h3 class="font-bold text-base sm:text-lg text-[#7D2431]">অনুষ্ঠানের সময়সূচি</h3>
                    </div>
                    <div class="space-y-3 text-xs md:text-sm">
                        <!-- Item 1: 11 March -->
                        <div class="flex items-start gap-2.5">
                            <div class="bg-[#7D2431] text-white px-2.5 py-1 rounded-lg text-center font-bold text-xs leading-tight whitespace-nowrap shadow-sm font-bengali-clear">
                                ১১<br>মার্চ
                            </div>
                            <div class="text-gray-700 leading-snug">
                                <p><strong class="text-gray-900">সকাল :</strong> বরণ ও আলোচনা সভা</p>
                                <p><strong class="text-gray-900">বিকাল :</strong> সাংস্কৃতিক অনুষ্ঠান</p>
                            </div>
                        </div>

                        <!-- Item 2: 12 March -->
                        <div class="flex items-start gap-2.5">
                            <div class="bg-[#7D2431] text-white px-2.5 py-1 rounded-lg text-center font-bold text-xs leading-tight whitespace-nowrap shadow-sm font-bengali-clear">
                                ১২<br>মার্চ
                            </div>
                            <div class="text-gray-700 leading-snug">
                                <p><strong class="text-gray-900">সকাল :</strong> মিলনমেলা ও দোয়া মাহফিল</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="#schedule" class="inline-block px-4 py-1.5 bg-[#7D2431] hover:bg-[#631c26] text-white text-xs font-semibold rounded-full shadow-sm transition">
                        সম্পূর্ণ সময়সূচি দেখুন ➔
                    </a>
                </div>
            </div>

        </div>
    </section>

    <!-- ==================== 4. নিবন্ধন ফি ও গুরুত্বপূর্ণ নির্দেশনা ==================== -->
    <section class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Pill Badge -->
        <div class="text-center mb-5">
            <span class="inline-flex items-center gap-2 px-6 py-1.5 bg-[#7D2431] text-white font-bold text-xs sm:text-sm rounded-full shadow border border-yellow-400/40">
                ★ নিবন্ধন ফি
            </span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: নিবন্ধন ফি (3 Cards) -->
            <div class="lg:col-span-7 space-y-3">
                <div class="grid grid-cols-3 gap-2.5 sm:gap-3">
                    
                    <!-- Card 1: সাবেক শিক্ষার্থী -->
                    <div class="bg-[#FFF5F6] border border-rose-200/80 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 mx-auto bg-rose-100 text-[#7D2431] rounded-full flex items-center justify-center mb-1 text-xs sm:text-sm">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h4 class="font-bold text-[11px] sm:text-xs md:text-sm text-gray-800">সাবেক শিক্ষার্থী</h4>
                        <p class="text-sm sm:text-base md:text-lg font-black text-[#7D2431] mt-1"><?= formatTaka($formerFee) ?></p>
                    </div>

                    <!-- Card 2: বর্তমান শিক্ষার্থী -->
                    <div class="bg-[#FFF5F6] border border-rose-200/80 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 mx-auto bg-rose-100 text-[#7D2431] rounded-full flex items-center justify-center mb-1 text-xs sm:text-sm">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <h4 class="font-bold text-[11px] sm:text-xs md:text-sm text-gray-800">বর্তমান শিক্ষার্থী</h4>
                        <p class="text-sm sm:text-base md:text-lg font-black text-[#7D2431] mt-1"><?= formatTaka($currentFee) ?></p>
                    </div>

                    <!-- Card 3: অতিথি ফি -->
                    <div class="bg-[#F0FDF4] border border-emerald-200/80 rounded-2xl p-3 sm:p-4 text-center shadow-sm">
                        <div class="w-7 h-7 sm:w-8 sm:h-8 mx-auto bg-emerald-100 text-[#155B43] rounded-full flex items-center justify-center mb-1 text-xs sm:text-sm">
                            <i class="fa-solid fa-users"></i>
                        </div>
                        <h4 class="font-bold text-[11px] sm:text-xs md:text-sm text-gray-800">অতিথি ফি</h4>
                        <p class="text-[9px] sm:text-[10px] text-gray-500">(প্রতি জন)</p>
                        <p class="text-sm sm:text-base md:text-lg font-black text-[#155B43] mt-0.5"><?= formatTaka($guestFee) ?></p>
                    </div>

                </div>

                <!-- Info Note (Matching Mobile Mockup) -->
                <p class="text-xs text-gray-700 text-center font-medium pt-1">
                    <i class="fa-solid fa-circle-info text-blue-600 mr-1"></i> অতিথি ফি পাঁচ বছরের বেশি বয়সীদের জন্য প্রযোজ্য।
                </p>
            </div>

            <!-- Right: গুরুত্বপূর্ণ নির্দেশনা -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-4 md:p-5 shadow-sm border border-amber-100">
                <div class="flex items-center gap-2 mb-3">
                    <span class="text-amber-600 text-base">📢</span>
                    <h3 class="font-bold text-xs sm:text-sm md:text-base text-gray-900">গুরুত্বপূর্ণ নির্দেশনা</h3>
                </div>
                <ul class="space-y-2 text-xs md:text-sm text-gray-700">
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-600 mt-1"></i>
                        <span>শিক্ষার্থীর সঙ্গে নিজের পরিবারের সদস্য ছাড়া অন্য কাউকে গ্রহণ করা যাবে না।</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-600 mt-1"></i>
                        <span>প্রতিষ্ঠানের কোনো ছাত্রছাত্রীকে অতিথি হিসেবে গণ্য করা হবে না।</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fa-solid fa-check text-emerald-600 mt-1"></i>
                        <span>অতিথিদের টি-শার্ট প্রদান করা হবে না।</span>
                    </li>
                </ul>
            </div>

        </div>
    </section>

    <!-- ==================== 5. নিবন্ধন ফরম ==================== -->
    <section id="registration" class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Header Pill Badge -->
        <div class="text-center mb-5">
            <span class="inline-flex items-center gap-2 px-6 py-2 bg-[#7D2431] text-white font-bold text-xs sm:text-sm md:text-base rounded-full shadow-lg border border-yellow-400/40">
                <i class="fa-solid fa-file-lines"></i> নিবন্ধন ফরম
            </span>
        </div>

        <!-- Main Form Container -->
        <div class="bg-white rounded-3xl shadow-xl border border-amber-200 p-5 md:p-8">
            
            <?php if (!$isRegEnabled): ?>
                <div class="p-6 bg-red-50 text-red-700 rounded-2xl text-center border border-red-200 font-bold">
                    অনলাইন নিবন্ধন সাময়িকভাবে বন্ধ আছে। বিস্তারিত তথ্যের জন্য আয়োজক কমিটির সাথে যোগাযোগ করুন।
                </div>
            <?php else: ?>

                <!-- Subheading (Matching Mobile Mockup) -->
                <p class="text-xs sm:text-sm font-bold text-gray-700 text-center mb-3">আপনার শিক্ষার্থীর ধরন নির্বাচন করুন:</p>

                <!-- Student Category Toggle Tabs -->
                <div class="flex items-center justify-center gap-3 mb-6">
                    <button type="button" id="tabFormer" onclick="setStudentCategory('former')" class="px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-[#7D2431] text-white flex items-center gap-1.5">
                        <i class="fa-solid fa-check text-xs" id="tabFormerCheck"></i> সাবেক শিক্ষার্থী
                    </button>
                    <button type="button" id="tabCurrent" onclick="setStudentCategory('current')" class="px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5">
                        <i class="fa-solid fa-check text-xs hidden" id="tabCurrentCheck"></i> বর্তমান শিক্ষার্থী
                    </button>
                </div>

                <!-- Registration Form Submission -->
                <form id="regForm" onsubmit="handleRegistrationSubmit(event)" enctype="multipart/form-data">
                    <?= csrf_field() ?>
                    <input type="hidden" name="student_category" id="studentCategoryInput" value="former">

                    <div class="grid grid-cols-2 lg:grid-cols-12 gap-2.5 sm:gap-4 lg:gap-6">
                        
                        <!-- Column 1 (Left Form Inputs) -->
                        <div class="col-span-1 lg:col-span-4 space-y-2.5 sm:space-y-3.5">
                            
                            <!-- Category Dynamic: Former Class / Current Class -->
                            <div id="formerClassField" class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-graduation-cap"></i></span>
                                <select name="prev_class" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431] bg-white">
                                    <option value="">শ্রেণি নির্বাচন করুন</option>
                                    <option value="দাখিল">দাখিল</option>
                                    <option value="আলিম">আলিম</option>
                                    <option value="ফাজিল">ফাজিল</option>
                                    <option value="অন্যান্য">অন্যান্য</option>
                                </select>
                            </div>

                            <div id="currentClassField" class="input-icon-group hidden">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-school"></i></span>
                                <select name="current_class" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431] bg-white">
                                    <option value="">বর্তমান শ্রেণি নির্বাচন</option>
                                    <option value="৬ষ্ঠ শ্রেণি">৬ষ্ঠ শ্রেণি</option>
                                    <option value="৭ম শ্রেণি">৭ম শ্রেণি</option>
                                    <option value="৮ম শ্রেণি">৮ম শ্রেণি</option>
                                    <option value="৯ম শ্রেণি">৯ম শ্রেণি</option>
                                    <option value="১০ম শ্রেণি (দাখিল)">১০ম শ্রেণি (দাখিল)</option>
                                </select>
                            </div>

                            <!-- Passing / Academic Year -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-regular fa-calendar-days"></i></span>
                                <input type="text" name="passing_year" placeholder="পাশের সাল" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                            <!-- Name -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-user"></i></span>
                                <input type="text" name="student_name" required placeholder="নাম*" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                            <!-- Mobile -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-phone"></i></span>
                                <input type="tel" name="mobile_number" required placeholder="মোবাইল নম্বর*" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                        </div>

                        <!-- Column 2 (Right Form Inputs) -->
                        <div class="col-span-1 lg:col-span-4 space-y-2.5 sm:space-y-3.5">
                            
                            <!-- Father's Name -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-user-tie"></i></span>
                                <input type="text" name="father_name" required placeholder="পিতার নাম*" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                            <!-- Mother's Name -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-users"></i></span>
                                <input type="text" name="mother_name" required placeholder="মাতার নাম*" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                            <!-- Current Address -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-location-dot"></i></span>
                                <input type="text" name="current_address" required placeholder="বর্তমান ঠিকানা*" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                            </div>

                            <!-- T-Shirt Size -->
                            <div class="input-icon-group">
                                <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-shirt"></i></span>
                                <select name="tshirt_size" required class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431] bg-white">
                                    <option value="">টি-শার্টের সাইজ</option>
                                    <option value="XS">XS</option>
                                    <option value="S">S</option>
                                    <option value="M">M</option>
                                    <option value="L" selected>L</option>
                                    <option value="XL">XL</option>
                                    <option value="XXL">XXL</option>
                                </select>
                            </div>

                        </div>

                        <!-- Column 3 (Additional info, Payment & Fee Calculation) -->
                        <div class="col-span-2 lg:col-span-4 flex flex-col justify-between space-y-3">
                            
                            <!-- Occupation & Guest Fields -->
                            <div class="grid grid-cols-2 gap-2">
                                <div class="input-icon-group">
                                    <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-briefcase"></i></span>
                                    <input type="text" name="occupation" placeholder="পেশা (ঐচ্ছিক)" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs md:text-sm pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                                </div>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <select name="has_guest" id="hasGuestSelect" onchange="toggleGuestField()" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[10px] sm:text-xs focus:outline-none focus:ring-2 focus:ring-[#7D2431] bg-white px-1">
                                        <option value="0">অতিথি নেই</option>
                                        <option value="1">অতিথি আছে</option>
                                    </select>
                                    <div id="guestCountContainer" class="opacity-60">
                                        <input type="number" name="guest_count" id="guestCountInput" min="0" max="10" value="0" disabled oninput="calculateTotalFee()" placeholder="সংখ্যা" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[10px] sm:text-xs px-2 focus:outline-none focus:ring-2 focus:ring-[#7D2431]">
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method & TrxID -->
                            <div class="space-y-2 bg-[#FFF9ED] p-2.5 sm:p-3.5 rounded-2xl border border-amber-200">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-gray-700 text-[11px] sm:text-xs">পেমেন্ট মাধ্যম:</span>
                                    <div class="flex items-center gap-3">
                                        <label class="flex items-center gap-1 font-bold text-pink-700 cursor-pointer text-[11px] sm:text-xs">
                                            <input type="radio" name="payment_method" value="bkash" checked class="text-pink-600 focus:ring-pink-500">
                                            বিকাশ
                                        </label>
                                        <label class="flex items-center gap-1 font-bold text-amber-700 cursor-pointer text-[11px] sm:text-xs">
                                            <input type="radio" name="payment_method" value="nagad" class="text-amber-600 focus:ring-amber-500">
                                            নগদ
                                        </label>
                                    </div>
                                </div>

                                <div class="input-icon-group">
                                    <span class="input-icon-badge hidden sm:flex"><i class="fa-solid fa-barcode"></i></span>
                                    <input type="text" name="transaction_id" required placeholder="Transaction ID *" class="w-full h-10 sm:h-11 border border-gray-300 rounded-xl text-[11px] sm:text-xs pl-2.5 sm:pl-11 focus:outline-none focus:ring-2 focus:ring-[#7D2431] uppercase bg-white">
                                </div>

                                <p class="text-[9px] sm:text-[10px] text-gray-500 leading-tight">
                                    * বিকাশ/নগদ <strong class="text-[#7D2431]"><?= e($bkashNumber) ?></strong> নম্বরে সেন্ড মানি করে প্রাপ্ত TrxID দিন।
                                </p>
                            </div>

                            <!-- Fee Pill Bar (Exact as Mobile Mockup) -->
                            <div class="w-full py-2.5 sm:py-3 bg-[#7D2431] text-white font-bold rounded-2xl text-center text-xs sm:text-sm shadow flex items-center justify-center gap-2">
                                <i class="fa-solid fa-calculator text-yellow-300"></i>
                                <span>মোট ফি:</span>
                                <span id="displayTotalFee" class="text-sm sm:text-base text-yellow-200 font-black">৳ ১,০০০</span>
                                <span id="displayBaseFee" class="hidden">৳ ১,০০০</span>
                                <span id="displayGuestFee" class="hidden">৳ ০</span>
                            </div>

                            <!-- Submit Button (Exact as Mobile Mockup) -->
                            <button type="submit" id="submitBtn" class="w-full py-3 sm:py-3.5 bg-[#7D2431] hover:bg-[#601923] text-white font-bold rounded-2xl shadow-xl transition duration-200 flex items-center justify-center gap-2 text-xs sm:text-sm md:text-base border border-yellow-300/30">
                                <i class="fa-solid fa-paper-plane"></i> জমা দিন
                            </button>

                        </div>

                    </div>
                </form>

            <?php endif; ?>

        </div>
    </section>

    <!-- ==================== 6. পরিসংখ্যান ও স্মৃতির অ্যালবাম ==================== -->
    <section class="py-8 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            
            <!-- Left: নিবন্ধন পরিসংখ্যান -->
            <div class="lg:col-span-5 space-y-3">
                <div class="flex items-center gap-2">
                    <span class="text-[#7D2431] text-base"><i class="fa-solid fa-chart-simple"></i></span>
                    <h3 class="font-bold text-sm md:text-base text-gray-900">নিবন্ধন পরিসংখ্যান</h3>
                </div>

                <div class="grid grid-cols-3 gap-3">
                    
                    <div class="bg-white border border-amber-200/70 rounded-2xl p-3 text-center shadow-sm">
                        <div class="w-7 h-7 mx-auto bg-amber-50 text-amber-700 rounded-full flex items-center justify-center mb-1 text-xs">
                            <i class="fa-solid fa-user-group"></i>
                        </div>
                        <p class="text-[11px] font-semibold text-gray-600">সাবেক শিক্ষার্থী</p>
                        <p class="text-base font-black text-gray-900 mt-0.5"><?= en2bn(number_format($statFormer)) ?>+</p>
                    </div>

                    <div class="bg-white border border-amber-200/70 rounded-2xl p-3 text-center shadow-sm">
                        <div class="w-7 h-7 mx-auto bg-blue-50 text-blue-700 rounded-full flex items-center justify-center mb-1 text-xs">
                            <i class="fa-solid fa-graduation-cap"></i>
                        </div>
                        <p class="text-[11px] font-semibold text-gray-600">বর্তমান শিক্ষার্থী</p>
                        <p class="text-base font-black text-gray-900 mt-0.5"><?= en2bn(number_format($statCurrent)) ?>+</p>
                    </div>

                    <div class="bg-white border border-emerald-200/70 rounded-2xl p-3 text-center shadow-sm">
                        <div class="w-7 h-7 mx-auto bg-emerald-50 text-emerald-700 rounded-full flex items-center justify-center mb-1 text-xs">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <p class="text-[11px] font-semibold text-gray-600">মোট নিবন্ধন</p>
                        <p class="text-base font-black text-[#155B43] mt-0.5"><?= en2bn(number_format($statTotal)) ?>+</p>
                    </div>

                </div>
            </div>

            <!-- Right: স্মৃতির অ্যালবাম (4 Photos row) -->
            <div id="gallery" class="lg:col-span-7 space-y-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-[#7D2431] text-base"><i class="fa-solid fa-camera-retro"></i></span>
                        <h3 class="font-bold text-sm md:text-base text-gray-900">স্মৃতির অ্যালবাম</h3>
                    </div>
                    <a href="#gallery" class="text-xs font-semibold text-[#7D2431] hover:underline flex items-center gap-1">
                        সব ছবি দেখুন ➔
                    </a>
                </div>

                <div class="grid grid-cols-4 gap-2.5">
                    <div class="rounded-xl overflow-hidden shadow border-2 border-white hover:scale-105 transition">
                        <img src="<?= getBaseUrl() ?>/assets/images/album_1.png" alt="স্মৃতি ১" class="w-full h-16 md:h-20 object-cover">
                    </div>
                    <div class="rounded-xl overflow-hidden shadow border-2 border-white hover:scale-105 transition">
                        <img src="<?= getBaseUrl() ?>/assets/images/album_2.png" alt="স্মৃতি ২" class="w-full h-16 md:h-20 object-cover">
                    </div>
                    <div class="rounded-xl overflow-hidden shadow border-2 border-white hover:scale-105 transition">
                        <img src="<?= getBaseUrl() ?>/assets/images/album_3.png" alt="স্মৃতি ৩" class="w-full h-16 md:h-20 object-cover">
                    </div>
                    <div class="rounded-xl overflow-hidden shadow border-2 border-white hover:scale-105 transition">
                        <img src="<?= getBaseUrl() ?>/assets/images/album_4.png" alt="স্মৃতি ৪" class="w-full h-16 md:h-20 object-cover">
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ==================== 7. FOOTER ==================== -->
    <footer id="contact" class="bg-[#7D2431] text-white pt-10 pb-6 border-t-4 border-[#C69A45] relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-center pb-8 border-b border-white/10 text-center md:text-left">
                
                <!-- Left Madrasa Info -->
                <div class="flex items-center justify-center md:justify-start gap-3">
                    <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-14 h-14 rounded-full border-2 border-yellow-300 shadow bg-white object-contain">
                    <div>
                        <h4 class="font-bold text-base md:text-lg"><?= e($madrasaName) ?></h4>
                        <p class="text-xs text-yellow-200"><?= e($eventTitle) ?></p>
                    </div>
                </div>

                <!-- Center: Contact Info -->
                <div class="text-xs md:text-sm space-y-1.5 text-gray-200">
                    <h5 class="font-bold text-yellow-300 mb-2 text-sm">যোগাযোগ করুন</h5>
                    <p><i class="fa-solid fa-phone mr-2 text-yellow-300"></i> <?= e($contactPhone) ?></p>
                    <p><i class="fa-solid fa-envelope mr-2 text-yellow-300"></i> <?= e($contactEmail) ?></p>
                    <p><i class="fa-brands fa-facebook mr-2 text-yellow-300"></i> /<?= e($contactFacebook) ?></p>
                </div>

                <!-- Right: Emotional Quote -->
                <div class="text-center md:text-right italic text-yellow-100 text-xs md:text-sm">
                    <p>"পুরনো দিনের সেই সহপাঠী, সেই বন্ধন...</p>
                    <p>আবার মিলি, গড়ি নতুন স্মৃতি..."</p>
                </div>

            </div>

            <!-- Bottom Copyright Bar -->
            <div class="pt-6 flex flex-col sm:flex-row items-center justify-between text-[11px] text-gray-300 gap-2">
                <p>© ২০২৭ <?= e($madrasaName) ?>। সর্বস্বত্ব সংরক্ষিত।</p>
                <p class="flex items-center gap-1.5 flex-wrap justify-center sm:justify-end">
                    <span>Design & Developed by</span>
                    <strong class="text-yellow-300 font-bold">Badhon Islam</strong>
                    <span class="text-white/40">•</span>
                    <a href="https://wa.me/8801902562000" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 text-emerald-400 hover:text-emerald-300 transition font-semibold" title="WhatsApp-এ যোগাযোগ করুন">
                        <i class="fa-brands fa-whatsapp text-sm text-emerald-400"></i>
                        <span>01902562000</span>
                    </a>
                </p>
            </div>
        </div>
    </footer>

    <!-- ==================== SUCCESS MODAL ==================== -->
    <div id="successModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 text-center shadow-2xl border-4 border-[#C69A45] animate-scale-up">
            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-3">
                <i class="fa-solid fa-circle-check text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-[#7D2431] mb-1">নিবন্ধন সফলভাবে জমা হয়েছে!</h3>
            <p class="text-xs text-gray-600 mb-4">আপনার পেমেন্ট ও নিবন্ধন তথ্য আয়োজক কমিটির কাছে জমা হয়েছে।</p>

            <div class="bg-[#FFF9ED] p-4 rounded-2xl border border-amber-200 text-left text-xs space-y-2 mb-4">
                <div class="flex justify-between">
                    <span class="text-gray-600">রেজিস্ট্রেশন ট্র্যাকিং কোড:</span>
                    <strong class="font-mono text-base text-[#7D2431]" id="successCode">REU27-XXXXXX</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">শিক্ষার্থীর নাম:</span>
                    <strong id="successName">মুহাম্মদ</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">মোট ফি:</span>
                    <strong class="text-emerald-700 font-bold" id="successAmount">৳ ১,০০০</strong>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600">স্ট্যাটাস:</span>
                    <span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded">যাচাইয়ের অপেক্ষায়</span>
                </div>
            </div>

            <p class="text-[11px] text-gray-500 mb-4">
                * আয়োজক কমিটি আপনার বিকাশ/নগদ পেমেন্ট অনুমোদন করার পর আপনি অফিশিয়াল কিউআর রশিদ ডাউনলোড করতে পারবেন।
            </p>

            <div class="flex gap-2">
                <button onclick="closeSuccessModal()" class="w-full py-2.5 bg-[#7D2431] text-white font-bold rounded-xl text-xs hover:bg-[#631c26] transition">
                    ঠিক আছে
                </button>
            </div>
        </div>
    </div>

    <!-- ==================== STATUS CHECK MODAL ==================== -->
    <div id="statusModal" class="fixed inset-0 bg-black/60 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-md w-full p-6 shadow-2xl border border-amber-200">
            <div class="flex items-center justify-between border-b pb-3 mb-4">
                <h3 class="font-bold text-base text-[#7D2431]">নিবন্ধন স্ট্যাটাস যাচাই</h3>
                <button onclick="closeStatusModal()" class="text-gray-400 hover:text-gray-600 text-lg">&times;</button>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-1">রেজিস্ট্রেশন কোড অথবা মোবাইল নম্বর লিখুন:</label>
                    <div class="flex gap-2">
                        <input type="text" id="statusQueryInput" placeholder="যেমন: REU27-XXXXXX অথবা 017XXXXXXXX" class="flex-1 px-3 py-2 border border-gray-300 rounded-xl text-xs focus:ring-2 focus:ring-[#7D2431] focus:outline-none">
                        <button onclick="checkStatusAction()" class="px-4 py-2 bg-[#7D2431] hover:bg-[#601923] text-white rounded-xl text-xs font-bold transition">
                            যাচাই করুন
                        </button>
                    </div>
                </div>

                <div id="statusResultArea" class="hidden text-xs pt-2">
                    <!-- Dynamic injected -->
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== SCRIPTS ==================== -->
    <script>
        const formerFee = <?= $formerFee ?>;
        const currentFee = <?= $currentFee ?>;
        const guestFee = <?= $guestFee ?>;

        let currentCategory = 'former';

        function en2bn(num) {
            const en = ['0','1','2','3','4','5','6','7','8','9'];
            const bn = ['০','১','২','৩','৪','৫','৬','৭','৮','৯'];
            return num.toString().replace(/\d/g, d => bn[d]);
        }

        function setStudentCategory(cat) {
            currentCategory = cat;
            document.getElementById('studentCategoryInput').value = cat;

            const tabFormer = document.getElementById('tabFormer');
            const tabCurrent = document.getElementById('tabCurrent');
            const formerClassField = document.getElementById('formerClassField');
            const currentClassField = document.getElementById('currentClassField');
            const tabFormerCheck = document.getElementById('tabFormerCheck');
            const tabCurrentCheck = document.getElementById('tabCurrentCheck');

            if (cat === 'former') {
                tabFormer.className = 'px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-[#7D2431] text-white flex items-center gap-1.5';
                tabCurrent.className = 'px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5';
                formerClassField.classList.remove('hidden');
                currentClassField.classList.add('hidden');
                if (tabFormerCheck) tabFormerCheck.classList.remove('hidden');
                if (tabCurrentCheck) tabCurrentCheck.classList.add('hidden');
            } else {
                tabCurrent.className = 'px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-[#7D2431] text-white flex items-center gap-1.5';
                tabFormer.className = 'px-5 py-2 rounded-full font-bold text-xs md:text-sm shadow-sm transition duration-200 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 flex items-center gap-1.5';
                formerClassField.classList.add('hidden');
                currentClassField.classList.remove('hidden');
                if (tabFormerCheck) tabFormerCheck.classList.add('hidden');
                if (tabCurrentCheck) tabCurrentCheck.classList.remove('hidden');
            }

            calculateTotalFee();
        }

        function toggleGuestField() {
            const select = document.getElementById('hasGuestSelect');
            const input = document.getElementById('guestCountInput');
            const container = document.getElementById('guestCountContainer');

            if (select.value === '1') {
                input.disabled = false;
                if (parseInt(input.value) === 0) input.value = 1;
                container.classList.remove('opacity-60');
            } else {
                input.disabled = true;
                input.value = 0;
                container.classList.add('opacity-60');
            }
            calculateTotalFee();
        }

        function calculateTotalFee() {
            const base = (currentCategory === 'former') ? formerFee : currentFee;
            const hasGuest = document.getElementById('hasGuestSelect').value === '1';
            const guestCount = hasGuest ? (parseInt(document.getElementById('guestCountInput').value) || 0) : 0;
            const guestTotal = guestCount * guestFee;
            const total = base + guestTotal;

            document.getElementById('displayBaseFee').innerText = '৳ ' + en2bn(base.toLocaleString());
            document.getElementById('displayGuestFee').innerText = '৳ ' + en2bn(guestTotal.toLocaleString());
            document.getElementById('displayTotalFee').innerText = '৳ ' + en2bn(total.toLocaleString());
        }

        // Live Countdown to Event Date
        function updateCountdown() {
            const target = new Date("<?= $eventDateStart ?>").getTime();
            const now = new Date().getTime();
            const diff = target - now;

            if (diff > 0) {
                const days = Math.floor(diff / (1000 * 60 * 60 * 24));
                const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const mins = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
                const secs = Math.floor((diff % (1000 * 60)) / 1000);

                const elDays = document.getElementById('cd-days');
                const elHours = document.getElementById('cd-hours');
                const elMins = document.getElementById('cd-mins');
                const elSecs = document.getElementById('cd-secs');

                if (elDays) elDays.innerText = en2bn(days);
                if (elHours) elHours.innerText = en2bn(hours);
                if (elMins) elMins.innerText = en2bn(mins);
                if (elSecs) elSecs.innerText = en2bn(secs);
            }
        }
        setInterval(updateCountdown, 1000);
        updateCountdown();

        // AJAX Form Submit
        async function handleRegistrationSubmit(e) {
            e.preventDefault();
            const form = document.getElementById('regForm');
            const submitBtn = document.getElementById('submitBtn');
            const originalBtnText = submitBtn.innerHTML;

            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> প্রক্রিয়াধীন...';

            const formData = new FormData(form);

            try {
                const res = await fetch('<?= getBaseUrl() ?>/api/register.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (data.success) {
                    document.getElementById('successCode').innerText = data.registration_code;
                    document.getElementById('successName').innerText = data.student_name;
                    document.getElementById('successAmount').innerText = '৳ ' + en2bn(data.total_fee);
                    document.getElementById('successModal').classList.remove('hidden');
                    form.reset();
                    setStudentCategory('former');
                } else {
                    alert(data.message || 'নিবন্ধনে সমস্যা হয়েছে!');
                }
            } catch (err) {
                alert('সার্ভারের সাথে সংযোগে ত্রুটি হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            }
        }

        function closeSuccessModal() {
            document.getElementById('successModal').classList.add('hidden');
        }

        // Status Modal Functions
        function openStatusModal() {
            document.getElementById('statusModal').classList.remove('hidden');
            document.getElementById('statusQueryInput').focus();
        }
        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        async function checkStatusAction() {
            const query = document.getElementById('statusQueryInput').value.trim();
            const resArea = document.getElementById('statusResultArea');

            if (!query) {
                alert('অনুগ্রহ করে রেজিস্ট্রেশন কোড বা মোবাইল নম্বর দিন।');
                return;
            }

            resArea.innerHTML = '<p class="text-gray-500 text-center"><i class="fa-solid fa-spinner fa-spin"></i> খোঁজা হচ্ছে...</p>';
            resArea.classList.remove('hidden');

            try {
                const res = await fetch('<?= getBaseUrl() ?>/api/check-status.php?q=' + encodeURIComponent(query));
                const json = await res.json();

                if (json.success) {
                    const d = json.data;
                    let statusBadge = '';
                    let actionHtml = '';

                    if (d.payment_status === 'approved') {
                        statusBadge = '<span class="px-2 py-0.5 bg-emerald-100 text-emerald-800 font-bold rounded">অনুমোদিত ও নিশ্চিত (CONFIRMED)</span>';
                        actionHtml = `<div class="mt-3"><a href="${d.receipt_url}" target="_blank" class="w-full inline-block py-2 bg-emerald-600 text-white rounded-xl font-bold text-center hover:bg-emerald-700 transition">অফিসিয়াল রশিদ ডাউনলোড করুন</a></div>`;
                    } else if (d.payment_status === 'rejected') {
                        statusBadge = '<span class="px-2 py-0.5 bg-red-100 text-red-800 font-bold rounded">বাতিল (REJECTED)</span>';
                    } else {
                        statusBadge = '<span class="px-2 py-0.5 bg-amber-100 text-amber-800 font-bold rounded">যাচাইকরণ প্রক্রিয়াধীন (PENDING)</span>';
                    }

                    resArea.innerHTML = `
                        <div class="bg-gray-50 p-3.5 rounded-2xl border border-gray-200 space-y-1.5">
                            <p><strong>শিক্ষার্থীর নাম:</strong> ${d.student_name}</p>
                            <p><strong>রেজিস্ট্রেশন কোড:</strong> <span class="font-mono font-bold text-[#7D2431]">${d.registration_code}</span></p>
                            <p><strong>শিক্ষার্থীর ধরন:</strong> ${d.student_category}</p>
                            <p><strong>পরিশোধিত ফি:</strong> ${d.total_fee}</p>
                            <p><strong>পেমেন্ট মাধ্যম / TrxID:</strong> ${d.payment_method} (${d.transaction_id})</p>
                            <div class="pt-1 flex items-center justify-between">
                                <strong>বর্তমান অবস্থা:</strong>
                                ${statusBadge}
                            </div>
                            ${actionHtml}
                        </div>
                    `;
                } else {
                    resArea.innerHTML = `<p class="p-3 bg-red-50 text-red-700 rounded-xl text-center">${json.message}</p>`;
                }
            } catch (e) {
                resArea.innerHTML = '<p class="p-3 bg-red-50 text-red-700 rounded-xl text-center">তথ্য সংগ্রহে সমস্যা হয়েছে।</p>';
            }
        }
        // Mobile Drawer Nav Toggle
        function toggleMobileNav() {
            const drawer = document.getElementById('mobileDrawer');
            const backdrop = document.getElementById('mobileDrawerBackdrop');
            if (drawer && backdrop) {
                drawer.classList.toggle('translate-x-full');
                backdrop.classList.toggle('hidden');
            }
        }
    </script>

    <!-- MOBILE FIXED BOTTOM NAVIGATION BAR (Exact as Mockup) -->
    <div class="fixed bottom-0 inset-x-0 z-40 bg-[#7D2431] text-white border-t border-[#C69A45]/40 py-2 px-3 shadow-2xl flex items-center justify-around md:hidden">
        <a href="#home" class="flex flex-col items-center gap-1 text-[11px] font-semibold text-yellow-300 hover:text-white transition">
            <i class="fa-solid fa-house text-base"></i>
            <span>হোম</span>
        </a>
        <a href="#schedule" class="flex flex-col items-center gap-1 text-[11px] font-semibold text-gray-200 hover:text-yellow-300 transition">
            <i class="fa-solid fa-calendar-days text-base"></i>
            <span>অনুষ্ঠানসূচি</span>
        </a>
        <a href="#gallery" class="flex flex-col items-center gap-1 text-[11px] font-semibold text-gray-200 hover:text-yellow-300 transition">
            <i class="fa-solid fa-images text-base"></i>
            <span>গ্যালারি</span>
        </a>
        <a href="#contact" class="flex flex-col items-center gap-1 text-[11px] font-semibold text-gray-200 hover:text-yellow-300 transition">
            <i class="fa-solid fa-phone text-base"></i>
            <span>যোগাযোগ</span>
        </a>
    </div>

</body>
</html>

