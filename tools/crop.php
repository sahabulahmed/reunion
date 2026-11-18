<?php
$srcPath = 'C:/Users/BADHON/.gemini/antigravity/brain/5fc9fe62-99d1-487e-a7da-4bc9c63ce4b1/.user_uploaded/media_1794851258105_503481dd.jpg';
$src = imagecreatefromjpeg($srcPath);

function cropAndSave($src, $rect, $destPath) {
    $crop = imagecrop($src, $rect);
    if ($crop !== false) {
        imagepng($crop, $destPath, 9);
        imagedestroy($crop);
        echo "Saved: $destPath\n";
    }
}

// 1. Logo (from footer where it is crisp circular badge)
cropAndSave($src, ['x' => 25, 'y' => 882, 'width' => 42, 'height' => 42], __DIR__ . '/../assets/images/logo.png');

// 2. 50 years Golden Emblem
cropAndSave($src, ['x' => 205, 'y' => 68, 'width' => 125, 'height' => 95], __DIR__ . '/../assets/images/jubilee_badge.png');

// 3. Madrasa Building in Hero (left card)
cropAndSave($src, ['x' => 0, 'y' => 95, 'width' => 350, 'height' => 135], __DIR__ . '/../assets/images/hero_building.png');

// 4. Madrasa Gate photo in "আমাদের আয়োজন"
cropAndSave($src, ['x' => 264, 'y' => 285, 'width' => 182, 'height' => 122], __DIR__ . '/../assets/images/gate_photo.png');

// 5. Lanterns from top right
cropAndSave($src, ['x' => 620, 'y' => 38, 'width' => 55, 'height' => 110], __DIR__ . '/../assets/images/lanterns.png');

// 6. Album photos
cropAndSave($src, ['x' => 354, 'y' => 794, 'width' => 76, 'height' => 58], __DIR__ . '/../assets/images/album_1.png');
cropAndSave($src, ['x' => 434, 'y' => 794, 'width' => 74, 'height' => 58], __DIR__ . '/../assets/images/album_2.png');
cropAndSave($src, ['x' => 511, 'y' => 794, 'width' => 74, 'height' => 58], __DIR__ . '/../assets/images/album_3.png');
cropAndSave($src, ['x' => 588, 'y' => 794, 'width' => 73, 'height' => 58], __DIR__ . '/../assets/images/album_4.png');

imagedestroy($src);
echo "Precise crop completed.\n";
