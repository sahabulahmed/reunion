<?php
require_once __DIR__ . '/../app/Services/AuthService.php';

$a = new AuthService();
if (!$a->hasAdmin()) {
    $res = $a->createAdmin('প্রধান অ্যাডমিন', 'admin', 'admin@reunion2027.com', '01712345678', 'Admin@2027');
    echo "Admin created: " . json_encode($res, JSON_UNESCAPED_UNICODE) . "\n";
} else {
    echo "Admin already exists.\n";
}

