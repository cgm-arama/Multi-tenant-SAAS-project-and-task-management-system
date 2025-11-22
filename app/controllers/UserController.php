<?php
// FILE: /app/controllers/UserController.php

/**
 * User Controller
 * SplashProjects - Multi-tenant SaaS Platform
 */
class UserController extends Controller
{
    public function index()
    {
        $this->requireRole(['tenant_admin', 'platform_admin']);

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = ITEMS_PER_PAGE;

        $userModel = $this->model('User');
        $users = $userModel->getUsersByTenant(null, $perPage, ($page - 1) * $perPage);
        $total = $userModel->countByTenant();
        $pagination = $this->paginate($total, $page, $perPage);

        $data = ['users' => $users, 'pagination' => $pagination];
        $this->view->render('users/index', $data);
    }

    public function invite()
    {
        $this->requireRole(['tenant_admin', 'platform_admin']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $data = ['csrf_token' => $this->getCSRF()];
            $this->view->render('users/invite', $data);
            return;
        }

        if (!$this->validateCSRF($_POST['csrf_token'] ?? '')) {
            $this->redirect('/users?error=invalid_request');
        }

        // Check user limit
        $subscriptionModel = $this->model('Subscription');
        $limitCheck = $subscriptionModel->checkLimit('users');

        if (!$limitCheck['allowed']) {
            $this->redirect('/users?error=user_limit_reached');
        }

        $email = $this->sanitize($_POST['email'] ?? '');
        $role = $_POST['role'] ?? 'member';

        $errors = $this->validate(['email' => $email], ['email' => 'required|email']);

        if (!empty($errors)) {
            $this->redirect('/users/invite?error=validation_failed');
        }

        // Check if user already exists
        $userModel = $this->model('User');
        if ($userModel->findByEmail($email)) {
            $this->redirect('/users/invite?error=user_exists');
        }

        // Check if already invited
        $invitationModel = $this->model('Invitation');
        if ($invitationModel->isEmailInvited($email)) {
            $this->redirect('/users/invite?error=already_invited');
        }

        try {
            $invitationModel->createInvitation([
                'tenant_id' => $this->getTenantId(),
                'email' => $email,
                'role' => $role,
                'invited_by' => $this->getUserId()
            ]);

            $this->redirect('/users?success=invited');
        } catch (Exception $e) {
            $this->redirect('/users/invite?error=invite_failed');
        }
    }
}
