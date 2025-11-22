<?php
// FILE: /app/core/Session.php

/**
 * Session Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages session handling with security best practices.
 */
class Session
{
    /**
     * Start session with secure settings
     */
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Set secure session parameters
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);
            ini_set('session.cookie_samesite', 'Strict');

            // Enable secure cookie only in production (HTTPS)
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                ini_set('session.cookie_secure', 1);
            }

            session_start();

            // Regenerate session ID periodically to prevent fixation attacks
            if (!isset($_SESSION['created'])) {
                $_SESSION['created'] = time();
            } else if (time() - $_SESSION['created'] > 1800) {
                // Regenerate session every 30 minutes
                session_regenerate_id(true);
                $_SESSION['created'] = time();
            }
        }
    }

    /**
     * Set session variable
     *
     * @param string $key
     * @param mixed $value
     */
    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Get session variable
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    /**
     * Check if session variable exists
     *
     * @param string $key
     * @return bool
     */
    public static function has($key)
    {
        return isset($_SESSION[$key]);
    }

    /**
     * Remove session variable
     *
     * @param string $key
     */
    public static function remove($key)
    {
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Destroy session completely
     */
    public static function destroy()
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];

            // Delete session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }

            session_destroy();
        }
    }

    /**
     * Set flash message
     *
     * @param string $key
     * @param string $message
     */
    public static function flash($key, $message)
    {
        $_SESSION['flash'][$key] = $message;
    }

    /**
     * Get and remove flash message
     *
     * @param string $key
     * @return string|null
     */
    public static function getFlash($key)
    {
        if (isset($_SESSION['flash'][$key])) {
            $message = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $message;
        }

        return null;
    }

    /**
     * Check if flash message exists
     *
     * @param string $key
     * @return bool
     */
    public static function hasFlash($key)
    {
        return isset($_SESSION['flash'][$key]);
    }
}
