<?php
// FILE: /app/controllers/HomeController.php

/**
 * Home Controller
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles home page and dashboard.
 */
class HomeController extends Controller
{
    /**
     * Landing page
     */
    public function index()
    {
        // If authenticated, redirect to dashboard
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }

        // Show landing page
        $planModel = $this->model('Plan');
        $data = [
            'plans' => $planModel->getActivePlans()
        ];

        $this->view->render('home/index', $data, 'landing');
    }

    /**
     * Dashboard
     */
    public function dashboard()
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $tenantId = $this->getTenantId();

        // Get models
        $projectModel = $this->model('Project');
        $taskModel = $this->model('Task');
        $activityModel = $this->model('Activity');
        $notificationModel = $this->model('Notification');
        $subscriptionModel = $this->model('Subscription');

        // Get statistics
        $totalProjects = $projectModel->countProjects();
        $activeProjects = $projectModel->countProjects(['status' => 'active']);
        $myTasks = $taskModel->getTasksByUser($userId, 10);
        $overdueTasks = $taskModel->getOverdueTasks($userId);
        $recentActivities = $activityModel->getRecentActivities(10);
        $unreadNotifications = $notificationModel->countUnread($userId);

        // Get subscription info
        $subscription = $subscriptionModel->getActiveSubscription();

        // Task statistics
        $totalTasks = $taskModel->countTasks();
        $completedTasks = $taskModel->countTasks(['status' => 'completed']);
        $inProgressTasks = $taskModel->countTasks(['status' => 'in_progress']);

        // Recent projects
        $recentProjects = $projectModel->getProjects([], 5);

        $data = [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'total_tasks' => $totalTasks,
            'completed_tasks' => $completedTasks,
            'in_progress_tasks' => $inProgressTasks,
            'my_tasks' => $myTasks,
            'overdue_tasks' => $overdueTasks,
            'recent_activities' => $recentActivities,
            'recent_projects' => $recentProjects,
            'unread_notifications' => $unreadNotifications,
            'subscription' => $subscription,
            'welcome' => isset($_GET['welcome'])
        ];

        $this->view->render('dashboard/index', $data);
    }
}
