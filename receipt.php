<?php
// receipt.php

declare(strict_types=1);

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/app/Services/RegistrationService.php';
require_once __DIR__ . '/app/Services/ReceiptService.php';

use Dompdf\Dompdf;
use Dompdf\Options;

$code = trim($_GET['code'] ?? '');
if (empty($code)) {
    die("রশিদ দেখার জন্য রেজিস্ট্রেশন কোড প্রয়োজন।");
}

$regService = new RegistrationService();
$reg = $regService->findByCodeOrPhone($code);

if (!$reg) {
    die("নিবন্ধন তথ্য পাওয়া যায়নি।");
}

if ($reg['payment_status'] !== PAY_APPROVED) {
    die("এই নিবন্ধনের পেমেন্ট এখনো অনুমোদিত হয়নি। অনুমোদন সম্পন্ন হওয়ার পর অফিশিয়াল রশিদ পাওয়া যাবে।");
}

$receiptService = new ReceiptService();
$receipt = $receiptService->generateReceipt((int)$reg['id']);
$qrCodeUri = $receiptService->getQrCodeDataUri($reg['qr_token']);

// Category text
$categoryText = ($reg['student_category'] === CAT_FORMER) ? 'সাবেক শিক্ষার্থী' : 'বর্তমান শিক্ষার্থী';
$academicInfo = ($reg['student_category'] === CAT_FORMER) 
    ? 'পাসের সাল: ' . en2bn($reg['passing_year'] ?? '') . ($reg['prev_class'] ? ' | শ্রেণি: ' . e($reg['prev_class']) : '')
    : 'বর্তমান শ্রেণি: ' . e($reg['current_class'] ?? '');

$eventTitle = getSetting('event_title', 'সুবর্ণ জয়ন্তী ও ঈদ পুনর্মিলনী - ২০২৭');
$madrasaName = getSetting('madrasa_name', 'বেরইল শামসুদ্দিন দাখিল মাদ্রাসা');
$eventDate = getSetting('event_date_text', '১১-১২ মার্চ ২০২৭');
$eventVenue = getSetting('event_venue', 'মাদ্রাসা প্রাঙ্গণ, বেরইল, মাগুরা');

// If PDF requested
if (isset($_GET['pdf']) && $_GET['pdf'] === '1') {
    $logoPath = realpath(__DIR__ . '/assets/images/logo.png');
    $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';

    $fontNotoPath = realpath(__DIR__ . '/assets/fonts/NotoSansBengali.ttf');
    $fontBase64 = file_exists($fontNotoPath) ? base64_encode(file_get_contents($fontNotoPath)) : '';

    // Search for Chromium binary (Chrome or Edge)
    $chromiumBin = null;
    $candidatePaths = [
        'C:\Program Files\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Google\Chrome\Application\chrome.exe',
        'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe',
        'C:\Program Files\Microsoft\Edge\Application\msedge.exe',
        (getenv('LOCALAPPDATA') ?: '') . '\Google\Chrome\Application\chrome.exe',
    ];
    foreach ($candidatePaths as $p) {
        if (!empty($p) && file_exists($p)) {
            $chromiumBin = $p;
            break;
        }
    }

    // Render HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html lang="bn">
    <head>
        <meta charset="UTF-8">
        <title>রশিদ - <?= e($reg['registration_code']) ?></title>
        <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            @font-face {
                font-family: 'LocalBengali';
                font-style: normal;
                font-weight: 400 700;
                src: url('data:font/truetype;charset=utf-8;base64,<?= $fontBase64 ?>') format('truetype');
            }
            @page {
                size: A4 portrait;
                margin: 0;
            }
            * { box-sizing: border-box; }
            html, body {
                margin: 0;
                padding: 0;
                background: #ffffff;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            body {
                font-family: 'Hind Siliguri', 'LocalBengali', sans-serif;
                padding: 8mm 10mm;
                color: #2D3748;
            }
            .receipt-container {
                border: 3px solid #C69A45;
                border-radius: 18px;
                padding: 18px 22px;
                background: #ffffff;
                page-break-inside: avoid;
                break-inside: avoid;
            }
            .header {
                text-align: center;
                border-bottom: 2px solid #C69A45;
                padding-bottom: 12px;
                margin-bottom: 14px;
            }
            .logo-box {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 12px;
                margin-bottom: 4px;
            }
            .logo-img {
                width: 52px;
                height: 52px;
                border-radius: 50%;
                border: 2px solid #7D2431;
                object-fit: contain;
            }
            .madrasa-name {
                font-size: 22px;
                font-weight: 800;
                color: #7D2431;
                margin: 0;
                line-height: 1.2;
            }
            .event-title {
                font-size: 14px;
                font-weight: 700;
                color: #C69A45;
                margin: 2px 0 0 0;
            }
            .meta-line {
                font-size: 11px;
                color: #4A5568;
                margin: 3px 0 0 0;
            }
            .receipt-pill {
                display: inline-block;
                background: #7D2431;
                color: #ffffff;
                font-weight: 700;
                font-size: 11.5px;
                padding: 3px 18px;
                border-radius: 9999px;
                margin-top: 6px;
            }
            .content-grid {
                display: table;
                width: 100%;
                margin-bottom: 14px;
            }
            .info-col {
                display: table-cell;
                width: 66%;
                vertical-align: top;
                padding-right: 16px;
            }
            .qr-col {
                display: table-cell;
                width: 34%;
                vertical-align: top;
            }
            .info-row {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                border-bottom: 1px solid #EDF2F7;
                font-size: 12.5px;
            }
            .info-label {
                color: #718096;
                font-weight: 500;
            }
            .info-val {
                color: #1A202C;
                font-weight: 600;
                text-align: right;
            }
            .code-val {
                color: #7D2431;
                font-family: monospace;
                font-weight: 700;
                font-size: 14px;
            }
            .tshirt-pill {
                background: #FFF5F6;
                color: #7D2431;
                border: 1px solid #FEB2B2;
                padding: 1px 7px;
                border-radius: 4px;
                font-weight: 700;
            }
            .qr-card {
                background: #FFF9ED;
                border: 2px dashed #C69A45;
                border-radius: 14px;
                padding: 10px 8px;
                text-align: center;
            }
            .qr-img {
                width: 120px;
                height: 120px;
                background: #ffffff;
                border-radius: 8px;
                padding: 3px;
                display: block;
                margin: 0 auto;
                border: 1px solid #E2E8F0;
            }
            .qr-title {
                font-size: 11px;
                font-weight: 700;
                color: #7D2431;
                margin-top: 5px;
            }
            .qr-sub {
                font-size: 9px;
                color: #718096;
                margin-top: 2px;
            }
            .payment-banner {
                background: linear-gradient(135deg, #155B43 0%, #0e3f2e 100%);
                color: #ffffff;
                border-radius: 12px;
                padding: 10px 18px;
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 18px;
            }
            .pay-label {
                font-size: 10.5px;
                color: #A7F3D0;
                text-transform: uppercase;
                font-weight: 600;
            }
            .pay-amount {
                font-size: 22px;
                font-weight: 800;
                color: #ffffff;
                line-height: 1.1;
            }
            .paid-tag {
                background: rgba(255,255,255,0.15);
                border: 1px solid #34D399;
                color: #D1FAE5;
                padding: 3px 10px;
                border-radius: 9999px;
                font-weight: 700;
                font-size: 11px;
                display: inline-block;
            }
            .pay-date {
                font-size: 10px;
                color: #A7F3D0;
                margin-top: 2px;
            }
            .signatures {
                display: flex;
                align-items: flex-end;
                justify-content: space-between;
                padding-top: 8px;
                font-size: 11.5px;
                color: #4A5568;
                text-align: center;
            }
            .sig-line {
                width: 110px;
                border-bottom: 1.5px solid #718096;
                margin: 0 auto 4px auto;
            }
        </style>
    </head>
    <body>
        <div class="receipt-container">
            <div class="header">
                <div class="logo-box">
                    <?php if ($logoBase64): ?>
                    <img src="data:image/png;base64,<?= $logoBase64 ?>" class="logo-img" alt="Logo">
                    <?php endif; ?>
                    <div>
                        <h1 class="madrasa-name"><?= e($madrasaName) ?></h1>
                        <p class="event-title"><?= e($eventTitle) ?></p>
                    </div>
                </div>
                <p class="meta-line"><?= e($eventDate) ?> &nbsp;|&nbsp; <?= e($eventVenue) ?></p>
                <div>
                    <span class="receipt-pill">অফিসিয়াল প্রবেশ ও অংশগ্রহণপত্র (Official Registration Receipt)</span>
                </div>
            </div>

            <div class="content-grid">
                <div class="info-col">
                    <div class="info-row">
                        <span class="info-label">রশিদ নম্বর:</span>
                        <span class="info-val" style="font-family: monospace;"><?= e($receipt['receipt_number'] ?? 'RCT-' . $reg['registration_code']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">রেজিস্ট্রেশন কোড:</span>
                        <span class="info-val code-val"><?= e($reg['registration_code']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">শিক্ষার্থীর নাম:</span>
                        <span class="info-val"><?= e($reg['student_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">পিতা ও মাতার নাম:</span>
                        <span class="info-val"><?= e($reg['father_name']) ?> ও <?= e($reg['mother_name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">মোবাইল নম্বর:</span>
                        <span class="info-val" style="font-family: monospace;"><?= e($reg['mobile_number']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">ক্যাটাগরি ও শিক্ষাবর্ষ:</span>
                        <span class="info-val"><?= e($categoryText) ?> (<?= e($academicInfo) ?>)</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">টি-শার্ট সাইজ:</span>
                        <span class="info-val"><span class="tshirt-pill"><?= e($reg['tshirt_size']) ?></span></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">নিবন্ধিত অতিথি:</span>
                        <span class="info-val"><?= en2bn($reg['guest_count']) ?> জন</span>
                    </div>
                    <div class="info-row" style="border-bottom: none;">
                        <span class="info-label">পেমেন্ট মাধ্যম ও TrxID:</span>
                        <span class="info-val" style="font-family: monospace; font-size: 11.5px; text-transform: uppercase;"><?= strtoupper(e($reg['payment_method'] ?? 'BKASH')) ?> / <?= e($reg['transaction_id'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div class="qr-col">
                    <div class="qr-card">
                        <img src="<?= $qrCodeUri ?>" class="qr-img" alt="QR Code">
                        <div class="qr-title">নিবন্ধন যাচাই কিউআর কোড</div>
                        <div class="qr-sub">ইভেন্টে প্রবেশের সময় এটি প্রদর্শন করুন</div>
                    </div>
                </div>
            </div>

            <div class="payment-banner">
                <div>
                    <div class="pay-label">পরিশোধিত মোট ফি</div>
                    <div class="pay-amount"><?= formatTaka($reg['total_fee']) ?></div>
                </div>
                <div style="text-align: right;">
                    <span class="paid-tag">&#10004; সম্পূর্ণ পরিশোধিত (PAID)</span>
                    <div class="pay-date">অনুমোদন তারিখ: <?= date('d M Y, h:i A', strtotime($receipt['generated_at'] ?? 'now')) ?></div>
                </div>
            </div>

            <div class="signatures">
                <div style="width: 28%;">
                    <div class="sig-line"></div>
                    <div>শিক্ষার্থীর স্বাক্ষর</div>
                </div>
                <div style="width: 44%; font-size: 10px; color: #718096; font-style: italic;">
                    * এটি একটি কম্পিউটার জেনারেটেড ডিজিটাল রশিদ।
                </div>
                <div style="width: 28%;">
                    <div class="sig-line"></div>
                    <div style="font-weight: 700; color: #7D2431;">আহ্বায়ক / সাধারণ সম্পাদক</div>
                    <div style="font-size: 10px;">পুনর্মিলনী উদযাপন কমিটি</div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    $receiptHtml = ob_get_clean();

    // 1. If Chromium (Chrome / Edge) is available, use it for 100% perfect Bengali ligatures & font rendering
    if ($chromiumBin) {
        $tempDir = sys_get_temp_dir();
        $randomHash = bin2hex(random_bytes(6));
        $tempHtml = $tempDir . DIRECTORY_SEPARATOR . 'receipt_' . $randomHash . '.html';
        $tempPdf = $tempDir . DIRECTORY_SEPARATOR . 'receipt_' . $randomHash . '.pdf';

        file_put_contents($tempHtml, $receiptHtml);

        $cmd = "\"$chromiumBin\" --headless=new --disable-gpu --no-pdf-header-footer --print-to-pdf=\"$tempPdf\" \"$tempHtml\" 2>&1";
        exec($cmd, $cmdOutput, $cmdCode);

        if (file_exists($tempPdf) && filesize($tempPdf) > 1000) {
            $pdfBytes = file_get_contents($tempPdf);
            @unlink($tempHtml);
            @unlink($tempPdf);

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="Reunion_Receipt_' . $reg['registration_code'] . '.pdf"');
            header('Content-Length: ' . strlen($pdfBytes));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            echo $pdfBytes;
            exit;
        }
        @unlink($tempHtml);
        @unlink($tempPdf);
    }

    // 2. Fallback to Dompdf if Chromium execution is unavailable
    $fontPath = str_replace('\\', '/', realpath(__DIR__ . '/assets/fonts/kalpurush.ttf'));
    $fontBase64 = file_exists($fontPath) ? base64_encode(file_get_contents($fontPath)) : '';

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isFontSubsettingEnabled', true);
    $options->set('defaultFont', 'Kalpurush');
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($receiptHtml);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream("Reunion_Receipt_{$reg['registration_code']}.pdf", ["Attachment" => true]);
    exit;
}

// Default HTML View (Printable + Download option)
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>অফিসিয়াল রশিদ | <?= e($reg['registration_code']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body { font-family: 'Hind Siliguri', sans-serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff !important; }
            .receipt-container { box-shadow: none !important; border: 2px solid #7D2431 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-[#FFF9ED] min-h-screen py-8 px-4 flex flex-col items-center justify-center">

    <!-- Action Toolbar (No Print) -->
    <div class="max-w-2xl w-full mb-4 flex flex-wrap items-center justify-between gap-3 no-print">
        <a href="<?= getBaseUrl() ?>" class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg text-sm font-semibold transition flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left"></i> হোম পেজে ফিরুন
        </a>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-5 py-2 bg-[#7D2431] hover:bg-[#601923] text-white rounded-lg text-sm font-semibold shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-print"></i> প্রিন্ট করুন
            </button>
            <a href="?code=<?= urlencode($reg['registration_code']) ?>&pdf=1" class="px-5 py-2 bg-[#C69A45] hover:bg-[#b0873a] text-white rounded-lg text-sm font-semibold shadow transition flex items-center gap-1.5">
                <i class="fa-solid fa-file-pdf"></i> PDF ডাউনলোড
            </a>
        </div>
    </div>

    <!-- Official Printable Receipt Card -->
    <div class="receipt-container max-w-2xl w-full bg-white rounded-3xl shadow-2xl border-4 border-[#C69A45] p-6 md:p-10 relative overflow-hidden">
        
        <!-- Subtle Watermark -->
        <div class="absolute inset-0 flex items-center justify-center pointer-events-none opacity-[0.03]">
            <img src="<?= getBaseUrl() ?>/assets/images/logo.png" class="w-96 h-96">
        </div>

        <!-- Receipt Header -->
        <div class="text-center border-b-2 border-[#C69A45] pb-6 mb-6">
            <div class="flex items-center justify-center gap-3 mb-2">
                <img src="<?= getBaseUrl() ?>/assets/images/logo.png" alt="Logo" class="w-16 h-16 rounded-full border-2 border-[#7D2431] shadow-sm">
                <div>
                    <h1 class="text-2xl md:text-3xl font-extrabold text-[#7D2431]"><?= e($madrasaName) ?></h1>
                    <p class="text-sm md:text-base font-semibold text-[#C69A45]"><?= e($eventTitle) ?></p>
                </div>
            </div>
            <p class="text-xs md:text-sm text-gray-600">
                <i class="fa-regular fa-calendar-days mr-1 text-[#7D2431]"></i> <?= e($eventDate) ?> &nbsp;|&nbsp; 
                <i class="fa-solid fa-location-dot mr-1 text-[#7D2431]"></i> <?= e($eventVenue) ?>
            </p>
            <div class="mt-4 inline-block bg-[#7D2431] text-white font-bold px-6 py-1.5 rounded-full text-sm shadow">
                অফিসিয়াল প্রবেশ ও অংশগ্রহণপত্র (Official Registration Receipt)
            </div>
        </div>

        <!-- Receipt Main Body -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 items-center mb-6">
            <div class="md:col-span-2 space-y-2.5 text-sm">
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">রশিদ নম্বর:</span>
                    <span class="font-mono font-bold text-gray-800"><?= e($receipt['receipt_number'] ?? 'RCT-' . $reg['registration_code']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">রেজিস্ট্রেশন কোড:</span>
                    <span class="font-mono font-bold text-[#7D2431] text-base"><?= e($reg['registration_code']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">শিক্ষার্থীর নাম:</span>
                    <span class="font-bold text-gray-900"><?= e($reg['student_name']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">পিতা ও মাতার নাম:</span>
                    <span class="text-gray-800"><?= e($reg['father_name']) ?> ও <?= e($reg['mother_name']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">মোবাইল নম্বর:</span>
                    <span class="font-mono text-gray-800"><?= e($reg['mobile_number']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">ক্যাটাগরি ও শিক্ষাবর্ষ:</span>
                    <span class="text-gray-800 font-medium"><?= e($categoryText) ?> (<?= e($academicInfo) ?>)</span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">টি-শার্ট সাইজ:</span>
                    <span class="font-bold text-[#7D2431] px-2 py-0.5 bg-rose-50 rounded border border-rose-200"><?= e($reg['tshirt_size']) ?></span>
                </div>
                <div class="flex justify-between border-b border-gray-100 pb-1">
                    <span class="text-gray-500 font-medium">নিবন্ধিত অতিথি:</span>
                    <span class="font-semibold text-gray-800"><?= en2bn($reg['guest_count']) ?> জন</span>
                </div>
                <div class="flex justify-between pt-1">
                    <span class="text-gray-500 font-medium">পেমেন্ট মাধ্যম ও TrxID:</span>
                    <span class="font-mono text-xs uppercase font-semibold text-gray-700"><?= e($reg['payment_method']) ?> / <?= e($reg['transaction_id']) ?></span>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="flex flex-col items-center justify-center p-4 bg-[#FFF9ED] rounded-2xl border-2 border-dashed border-[#C69A45]/60 text-center">
                <div class="w-32 h-32 bg-white p-2 rounded-xl shadow-inner flex items-center justify-center">
                    <img src="<?= $qrCodeUri ?>" alt="Verification QR Code" class="w-full h-full object-contain">
                </div>
                <p class="text-[11px] font-bold text-[#7D2431] mt-2">নিবন্ধন যাচাই কিউআর কোড</p>
                <p class="text-[9px] text-gray-500">ইভেন্টে প্রবেশের সময় এটি প্রদর্শন করুন</p>
            </div>
        </div>

        <!-- Payment Amount Banner -->
        <div class="bg-gradient-to-r from-[#155B43] to-[#0e3f2e] text-white p-4 rounded-2xl flex items-center justify-between shadow-md mb-6">
            <div>
                <span class="text-xs text-emerald-200 block uppercase font-semibold tracking-wider">পরিশোধিত মোট ফি</span>
                <span class="text-2xl font-black text-white"><?= formatTaka($reg['total_fee']) ?></span>
            </div>
            <div class="text-right">
                <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-emerald-400/20 text-emerald-200 text-xs font-bold rounded-full border border-emerald-400/40">
                    <i class="fa-solid fa-circle-check"></i> সম্পূর্ণ পরিশোধিত (PAID)
                </span>
                <span class="block text-[11px] text-emerald-200 mt-1">অনুমোদন তারিখ: <?= date('d M Y, h:i A', strtotime($receipt['generated_at'] ?? 'now')) ?></span>
            </div>
        </div>

        <!-- Receipt Footer Signatures -->
        <div class="pt-8 flex justify-between items-end text-center text-xs text-gray-600">
            <div>
                <div class="w-28 border-b border-gray-400 mb-1 mx-auto"></div>
                <p>শিক্ষার্থীর স্বাক্ষর</p>
            </div>
            <div class="text-[10px] text-gray-400 italic">
                * এটি একটি কম্পিউটার জেনারেটেড ডিজিটাল রশিদ।
            </div>
            <div>
                <div class="w-28 border-b border-gray-400 mb-1 mx-auto"></div>
                <p class="font-bold text-[#7D2431]">আহ্বায়ক / সাধারণ সম্পাদক</p>
                <p class="text-[10px]">পুনর্মিলনী উদযাপন কমিটি</p>
            </div>
        </div>

    </div>

</body>
</html>

