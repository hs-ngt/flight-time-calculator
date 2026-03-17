<?php

declare(strict_types=1);

// /home/アカウント名/
// ├─ public_html/services/flight-time-calculator/
// └─ service_private/flight-time-calculator/

if (!defined('FTC_ACCOUNT_ROOT')) {
    define('FTC_ACCOUNT_ROOT', dirname(__DIR__, 3));
}

if (!defined('FTC_PRIVATE_DIR')) {
    define('FTC_PRIVATE_DIR', FTC_ACCOUNT_ROOT . '/service_private/flight-time-calculator');
}

if (!defined('FTC_DATA_DIR')) {
    define('FTC_DATA_DIR', FTC_PRIVATE_DIR . '/data');
}

if (!defined('FTC_LIB_DIR')) {
    define('FTC_LIB_DIR', FTC_PRIVATE_DIR . '/lib');
}
