<?php
// FILE: /app/core/Auth.php

/**
 * Auth Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles user authentication and authorization.
 */
class Auth
{
    /**
     * Authenticate user and create session
     *
     * @param array $user User data from database
     */
    public static function login($user)
    {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Store user information in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['tenant_id'] = $user['tenant_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['created'] = time();
    }

    /**
     * Log out user and destroy session
     */
    public static function logout()
    {
        Session::destroy();
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    public static function check()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Get authenticated user ID
     *
     * @return int|null
     */
    public static function id()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get authenticated user's tenant ID
     *
     * @return int|null
     */
    public static function tenantId()
    {
        return $_SESSION['tenant_id'] ?? null;
    }

    /**
     * Get authenticated user's role
     *
     * @return string|null
     */
    public static function role()
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Check if user has specific role
     *
     * @param string|array $roles
     * @return bool
     */
    public static function hasRole($roles)
    {
        if (!self::check()) {
            return false;
        }

        $userRole = self::role();

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Check if user is platform admin
     *
     * @return bool
     */
    public static function isPlatformAdmin()
    {
        return self::hasRole('platform_admin');
    }

    /**
     * Check if user is tenant admin
     *
     * @return bool
     */
    public static function isTenantAdmin()
    {
        return self::hasRole('tenant_admin');
    }

    /**
     * Check if user is member
     *
     * @return bool
     */
    public static function isMember()
    {
        return self::hasRole('member');
    }

    /**
     * Get authenticated user data
     *
     * @return array|null
     */
    public static function user()
    {
        if (!self::check()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'tenant_id' => $_SESSION['tenant_id'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'name' => $_SESSION['name'] ?? null,
            'role' => $_SESSION['role'] ?? null
        ];
    }
}
