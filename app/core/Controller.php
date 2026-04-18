<?php
// FILE: /app/core/Controller.php

/**
 * Base Controller Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * All controllers extend this base class to inherit common functionality.
 * Provides methods for loading models, views, and handling common operations.
 */
class Controller
{
    protected $view;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->view = new View();
    }

    /**
     * Load a model
     *
     * @param string $model
     * @return object
     */
    protected function model($model)
    {
        $modelPath = __DIR__ . '/../models/' . $model . '.php';

        if (file_exists($modelPath)) {
            require_once $modelPath;
            return new $model();
        }

        throw new Exception("Model {$model} not found");
    }

    /**
     * Redirect to a specific URL
     *
     * @param string $url
     */
    protected function redirect($url)
    {
        header("Location: " . BASE_URL . $url);
        exit;
    }

    /**
     * Check if user is authenticated
     *
     * @return bool
     */
    protected function isAuthenticated()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Require authentication - redirect to login if not authenticated
     */
    protected function requireAuth()
{
    if (!$this->isAuthenticated()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        $this->redirect('/auth/login');
    }
}

    /**
     * Check if user has a specific role
     *
     * @param string|array $roles
     * @return bool
     */
    protected function hasRole($roles)
    {
        if (!$this->isAuthenticated()) {
            return false;
        }

        $userRole = $_SESSION['role'] ?? '';

        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }

        return $userRole === $roles;
    }

    /**
     * Require specific role - redirect if user doesn't have permission
     *
     * @param string|array $roles
     */
    protected function requireRole($roles)
    {
        $this->requireAuth();

        if (!$this->hasRole($roles)) {
            $this->redirect('/dashboard?error=unauthorized');
        }
    }

    /**
     * Get current user ID
     *
     * @return int|null
     */
    protected function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current tenant ID
     *
     * @return int|null
     */
    protected function getTenantId()
    {
        return $_SESSION['tenant_id'] ?? null;
    }

    /**
     * Validate CSRF token
     *
     * @param string $token
     * @return bool
     */
    protected function validateCSRF($token)
    {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Generate CSRF token
     *
     * @return string
     */
    protected function generateCSRF()
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Get CSRF token
     *
     * @return string
     */
    protected function getCSRF()
    {
        return $this->generateCSRF();
    }

    /**
     * Validate input data
     *
     * @param array $data
     * @param array $rules
     * @return array Errors array
     */
    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            $rulesArray = explode('|', $fieldRules);

            foreach ($rulesArray as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleValue = $ruleParts[1] ?? null;

                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0') {
                            $errors[$field] = ucfirst($field) . ' is required';
                        }
                        break;

                    case 'email':
                        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = ucfirst($field) . ' must be a valid email';
                        }
                        break;

                    case 'min':
                        if (!empty($value) && strlen($value) < $ruleValue) {
                            $errors[$field] = ucfirst($field) . " must be at least {$ruleValue} characters";
                        }
                        break;

                    case 'max':
                        if (!empty($value) && strlen($value) > $ruleValue) {
                            $errors[$field] = ucfirst($field) . " must not exceed {$ruleValue} characters";
                        }
                        break;

                    case 'numeric':
                        if (!empty($value) && !is_numeric($value)) {
                            $errors[$field] = ucfirst($field) . ' must be numeric';
                        }
                        break;
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitize input string
     *
     * @param string $input
     * @return string
     */
    protected function sanitize($input)
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Return JSON response
     *
     * @param mixed $data
     * @param int $statusCode
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * Get pagination data
     *
     * @param int $total Total records
     * @param int $page Current page
     * @param int $perPage Records per page
     * @return array
     */
    protected function paginate($total, $page = 1, $perPage = 20)
    {
        $page = max(1, (int)$page);
        $totalPages = ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;

        return [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'offset' => $offset,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages
        ];
    }
}
