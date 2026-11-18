<?php
$srcPath = 'C:/Users/BADHON/.gemini/antigravity/brain/5fc9fe62-99d1-487e-a7da-4bc9c63ce4b1/.user_uploaded/media_1794853937738_78863d36.jpg';
$src = imagecreatefromjpeg($srcPath);

$w = imagesx($src);
$h = imagesy($src);
echo "Mobile mockup size: {$w}x{$h}\n";

// The hero area in mobile image is from y = 80 to y = 350
// Let's crop the mobile hero background
$heroCrop = imagecrop($src, ['x' => 0, 'y' => 70, 'width' => $w, 'height' => 280]);
if ($heroCrop) {
    imagepng($heroCrop, __DIR__ . '/../assets/images/mobile_hero_banner.png', 9);
    imagedestroy($heroCrop);
    echo "Saved mobile_hero_banner.png\n";
}

imagedestroy($src);

