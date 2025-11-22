<?php
// FILE: /public/index.php

/**
 * SplashProjects - Multi-tenant SaaS Platform
 * Application Entry Point
 *
 * This is the main entry point for all requests.
 * It initializes the application and routes requests to appropriate controllers.
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/../config/config.php';

// Autoload core classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/../app/core/' . $class . '.php',
        __DIR__ . '/../app/models/' . $class . '.php',
        __DIR__ . '/../app/controllers/' . $class . '.php',
        __DIR__ . '/../app/controllers/api/' . $class . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Initialize router and handle request
try {
    $router = new Router();
} catch (Exception $e) {
    // Log error in production
    if (ENVIRONMENT === 'production') {
        error_log($e->getMessage());
        die("An error occurred. Please try again later.");
    } else {
        die("Error: " . $e->getMessage());
    }
}
