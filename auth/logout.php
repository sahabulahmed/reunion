<?php
// auth/logout.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';
require_once __DIR__ . '/../app/Services/AuthService.php';

$auth = new AuthService();
$auth->logout();

setFlash('success', 'আপনি সফলভাবে লগআউট হয়েছেন।');
header('Location: ' . getBaseUrl() . '/auth/login.php');
exit;

