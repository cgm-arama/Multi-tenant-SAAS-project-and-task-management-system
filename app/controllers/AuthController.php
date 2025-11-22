<?php
// FILE: /app/controllers/AuthController.php

/**
 * Auth Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles user authentication: login, logout, and registration.
 */
class AuthController extends Controller
{
    /**
     * Show login form
     */
    public function login()
    {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $data = [
            'csrf_token' => $this->getCSRF(),
            'error' => $_GET['error'] ?? null,
            'success' => Session::getFlash('success')
        ];

        $this->view->render('auth/login', $data, 'auth');
    }

    /**
     * Process login
     */
    public function doLogin()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/login');
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/auth/login?error=invalid_request');
        }

        // Validate input
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = $this->validate(['email' => $email, 'password' => $password], [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            $this->redirect('/auth/login?error=invalid_credentials');
        }

        // Attempt authentication
        $userModel = $this->model('User');
        $user = $userModel->verifyCredentials($email, $password);

        if (!$user) {
            $this->redirect('/auth/login?error=invalid_credentials');
        }

        // Check if user account is active
        if ($user['status'] !== 'active') {
            $this->redirect('/auth/login?error=account_inactive');
        }

        // Check tenant status
        if ($user['tenant_id']) {
            $tenantModel = $this->model('Tenant');
            $tenant = $tenantModel->find($user['tenant_id'], false);

            if ($tenant && $tenant['status'] !== 'active') {
                $this->redirect('/auth/login?error=workspace_suspended');
            }
        }

        // Log in user
        Auth::login($user);

        // Update last login
        $userModel->updateLastLogin($user['id']);

        // Redirect to intended page or dashboard
        $redirect = $_SESSION['redirect_after_login'] ?? '/dashboard';
        unset($_SESSION['redirect_after_login']);
        $this->redirect($redirect);
    }

    /**
     * Logout user
     */
    public function logout()
    {
        Auth::logout();
        $this->redirect('/auth/login?success=logged_out');
    }

    /**
     * Show registration form
     */
    public function register()
    {
        // Redirect if already authenticated
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        $data = [
            'csrf_token' => $this->getCSRF(),
            'error' => $_GET['error'] ?? null
        ];

        $this->view->render('auth/register', $data, 'auth');
    }

    /**
     * Process registration
     */
    public function doRegister()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/auth/register');
        }

        // Validate CSRF
        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/auth/register?error=invalid_request');
        }

        // Get input
        $name = $this->sanitize($_POST['name'] ?? '');
        $email = $this->sanitize($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $workspaceName = $this->sanitize($_POST['workspace_name'] ?? '');

        // Validate input
        $errors = $this->validate([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'workspace_name' => $workspaceName
        ], [
            'name' => 'required|min:2',
            'email' => 'required|email',
            'password' => 'required|min:6',
            'workspace_name' => 'required|min:2'
        ]);

        if (!empty($errors)) {
            $this->redirect('/auth/register?error=validation_failed');
        }

        // Check if email already exists
        $userModel = $this->model('User');
        if ($userModel->findByEmail($email)) {
            $this->redirect('/auth/register?error=email_exists');
        }

        try {
            // Create tenant
            $tenantModel = $this->model('Tenant');
            $tenantId = $tenantModel->createTenant([
                'name' => $workspaceName,
                'status' => 'active'
            ]);

            // Create user as tenant admin
            $userId = $userModel->createUser([
                'tenant_id' => $tenantId,
                'email' => $email,
                'password' => $password,
                'name' => $name,
                'role' => 'tenant_admin',
                'status' => 'active'
            ]);

            // Create free plan subscription
            $planModel = $this->model('Plan');
            $freePlan = $planModel->findBySlug('free');

            if ($freePlan) {
                $subscriptionModel = $this->model('Subscription');
                $subscriptionModel->create([
                    'tenant_id' => $tenantId,
                    'plan_id' => $freePlan['id'],
                    'status' => 'active',
                    'current_period_start' => date('Y-m-d H:i:s'),
                    'current_period_end' => date('Y-m-d H:i:s', strtotime('+1 month'))
                ]);
            }

            // Initialize usage tracking
            $usageModel = $this->model('Usage');
            $usageModel->create([
                'tenant_id' => $tenantId,
                'projects_count' => 0,
                'users_count' => 1,
                'tasks_count' => 0,
                'storage_used_mb' => 0
            ]);

            // Log in the new user
            $user = $userModel->find($userId, false);
            Auth::login($user);

            $this->redirect('/dashboard?welcome=1');

        } catch (Exception $e) {
            $this->redirect('/auth/register?error=registration_failed');
        }
    }
}
