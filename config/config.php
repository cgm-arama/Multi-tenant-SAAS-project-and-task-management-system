<?php
// FILE: /config/config.php

/**
 * Main Configuration File
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * This file contains application-wide configuration settings.
 */

// Load environment variables from .env file if it exists
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
        }
    }
}

// Environment
define('ENVIRONMENT', getenv('ENVIRONMENT') ?: 'development');

// Base URL
define('BASE_URL', getenv('BASE_URL') ?: 'http://localhost/SplashProjects/public');

// Application Settings
define('APP_NAME', 'SplashProjects');
define('APP_VERSION', '1.0.0');

// Timezone
date_default_timezone_set('UTC');

// Error Reporting
if (ENVIRONMENT === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../storage/logs/error.log');
}

// File Upload Settings
define('MAX_UPLOAD_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'gif', 'zip']);
define('UPLOAD_PATH', __DIR__ . '/../storage/uploads/');

// Pagination
define('ITEMS_PER_PAGE', 20);

// Session Settings
define('SESSION_LIFETIME', 86400); // 24 hours
