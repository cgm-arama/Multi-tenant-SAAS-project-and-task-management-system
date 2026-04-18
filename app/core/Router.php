<?php
// FILE: /app/core/Router.php

/**
 * Router Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles URL routing and dispatches requests to appropriate controllers.
 * Supports dynamic routing with parameters.
 */
class Router
{
    private $controller = 'HomeController';
    private $method = 'index';
    private $params = [];

    /**
     * Constructor - Parse URL and route request
     */
    public function __construct()
    {
        $url = $this->parseUrl();

        // Check for API routes
        if (isset($url[0]) && $url[0] === 'api') {
            $this->handleApiRoute($url);
            return;
        }

        // Handle web routes
        $this->handleWebRoute($url);
    }

    /**
     * Handle API routes
     *
     * @param array $url
     */
    private function handleApiRoute($url)
    {
        // Remove 'api' from URL
        array_shift($url);

        // API controller is in controllers/api/ directory
        if (isset($url[0])) {
            $controllerName = ucfirst($url[0]) . 'ApiController';
            $controllerPath = __DIR__ . '/../controllers/api/' . $controllerName . '.php';

            if (file_exists($controllerPath)) {
                require_once $controllerPath;
                $this->controller = new $controllerName();
                array_shift($url);
            } else {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'API endpoint not found']);
                exit;
            }
        }

        // Set method
        if (isset($url[0])) {
            $method = $url[0];
            if (method_exists($this->controller, $method)) {
                $this->method = $method;
                array_shift($url);
            }
        }

        // Set params
        $this->params = $url ? array_values($url) : [];

        // Call controller method
        call_user_func_array([$this->controller, $this->method], $this->params);
    }

    /**
     * Handle web routes
     *
     * @param array $url
     */
    private function handleWebRoute($url)
{
    $route = $url[0] ?? '';

    $routeMap = [
        'dashboard' => ['HomeController', 'dashboard'],
        'projects' => ['ProjectController', null],
        'boards' => ['BoardController', null],
        'auth' => ['AuthController', null],
        'home' => ['HomeController', null],
        // add these only if the files exist
        'team' => ['TeamController', null],
        'notifications' => ['NotificationController', null],
    ];

    if ($route && isset($routeMap[$route])) {
        $mappedController = $routeMap[$route][0];
        $mappedMethod = $routeMap[$route][1];

        $controllerPath = __DIR__ . '/../controllers/' . $mappedController . '.php';

        if (file_exists($controllerPath)) {
            require_once $controllerPath;
            $this->controller = new $mappedController();

            unset($url[0]);
            $url = array_values($url);

            if ($mappedMethod) {
                $this->method = $mappedMethod;
            } elseif (isset($url[0]) && method_exists($this->controller, $url[0])) {
                $this->method = $url[0];
                unset($url[0]);
                $url = array_values($url);
            }

            $this->params = $url ?: [];
            call_user_func_array([$this->controller, $this->method], $this->params);
            return;
        }
    }

    if (isset($url[0])) {
        $controllerName = ucfirst($url[0]) . 'Controller';
        $controllerPath = __DIR__ . '/../controllers/' . $controllerName . '.php';

        if (file_exists($controllerPath)) {
            $this->controller = $controllerName;
            unset($url[0]);
        }
    }

    $controllerPath = __DIR__ . '/../controllers/' . $this->controller . '.php';

    if (file_exists($controllerPath)) {
        require_once $controllerPath;
        $this->controller = new $this->controller();
    } else {
        http_response_code(404);
        die("Controller not found");
    }

    if (isset($url[1])) {
        if (method_exists($this->controller, $url[1])) {
            $this->method = $url[1];
            unset($url[1]);
        }
    }

    $this->params = $url ? array_values($url) : [];
    call_user_func_array([$this->controller, $this->method], $this->params);
}

    /**
     * Parse URL from request
     *
     * @return array
     */
    private function parseUrl()
    {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }

        return [];
    }
}
