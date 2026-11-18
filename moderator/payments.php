<?php
// moderator/payments.php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

header('Location: ' . getBaseUrl() . '/admin/payments.php');
exit;

