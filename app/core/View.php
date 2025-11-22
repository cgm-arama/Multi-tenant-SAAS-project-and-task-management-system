<?php
// FILE: /app/core/View.php

/**
 * View Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles rendering of view templates with data.
 * Supports layouts and partial views.
 */
class View
{
    /**
     * Render a view with optional data
     *
     * @param string $view View file path (without .php extension)
     * @param array $data Data to pass to the view
     * @param string|null $layout Layout file to use
     */
    public function render($view, $data = [], $layout = 'default')
    {
        // Extract data array to variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include view file
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (file_exists($viewPath)) {
            require $viewPath;
        } else {
            throw new Exception("View {$view} not found");
        }

        // Get view content
        $content = ob_get_clean();

        // If layout is specified, wrap content in layout
        if ($layout) {
            $layoutPath = __DIR__ . '/../views/layouts/' . $layout . '.php';

            if (file_exists($layoutPath)) {
                require $layoutPath;
            } else {
                echo $content;
            }
        } else {
            echo $content;
        }
    }

    /**
     * Render a partial view
     *
     * @param string $partial Partial view path
     * @param array $data Data to pass to the partial
     */
    public function partial($partial, $data = [])
    {
        extract($data);

        $partialPath = __DIR__ . '/../views/partials/' . $partial . '.php';

        if (file_exists($partialPath)) {
            require $partialPath;
        } else {
            throw new Exception("Partial {$partial} not found");
        }
    }

    /**
     * Escape output for security
     *
     * @param string $string
     * @return string
     */
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Alias for escape method
     *
     * @param string $string
     * @return string
     */
    public function e($string)
    {
        return $this->escape($string);
    }
}

/**
 * Global helper function for escaping output
 *
 * @param string $string
 * @return string
 */
function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}
