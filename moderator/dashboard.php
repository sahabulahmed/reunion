<?php
// moderator/dashboard.php

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../app/Helpers/functions.php';

// Moderator Dashboard redirects to main dashboard which adapts automatically to moderator role & permissions
header('Location: ' . getBaseUrl() . '/admin/dashboard.php');
exit;

