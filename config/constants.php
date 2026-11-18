<?php
// config/constants.php

declare(strict_types=1);

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

if (!defined('STORAGE_PATH')) {
    define('STORAGE_PATH', APP_ROOT . '/storage');
}

if (!defined('RECEIPTS_PATH')) {
    define('RECEIPTS_PATH', STORAGE_PATH . '/receipts');
}

if (!defined('PROOFS_PATH')) {
    define('PROOFS_PATH', STORAGE_PATH . '/payment-proofs');
}

if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', STORAGE_PATH . '/logs');
}

if (!defined('DEFAULT_TENANT_ID')) {
    define('DEFAULT_TENANT_ID', 1);
}

// Student categories
define('CAT_FORMER', 'former');
define('CAT_CURRENT', 'current');

// Payment status
define('PAY_PENDING', 'pending');
define('PAY_APPROVED', 'approved');
define('PAY_REJECTED', 'rejected');

// Registration status
define('REG_PENDING', 'pending');
define('REG_CONFIRMED', 'confirmed');
define('REG_REJECTED', 'rejected');
define('REG_CANCELLED', 'cancelled');

