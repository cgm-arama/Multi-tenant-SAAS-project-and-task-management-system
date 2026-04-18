<?php
// FILE: /app/controllers/DashboardController.php

/**
 * Dashboard Controller
 * Fix for routing /dashboard
 */
class DashboardController extends HomeController
{
    /**
     * Default method for /dashboard
     */
    public function index()
    {
        // Call the dashboard method from HomeController
        $this->dashboard();
    }
}