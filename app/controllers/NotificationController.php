<?php
// FILE: /app/controllers/NotificationController.php

/**
 * Notification Controller
 * SplashProjects - Multi-tenant SaaS Platform
 */
class NotificationController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        $notificationModel = $this->model('Notification');
        $notifications = $notificationModel->getNotificationsByUser($this->getUserId(), false, 50);
        $unreadCount = $notificationModel->countUnread($this->getUserId());

        $data = [
            'notifications' => $notifications,
            'unread_count' => $unreadCount
        ];

        $this->view->render('notifications/index', $data);
    }

    public function markRead($notificationId)
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid method'], 405);
        }

        $notificationModel = $this->model('Notification');
        $notificationModel->markAsRead($notificationId);

        $this->json(['success' => true]);
    }

    public function markAllRead()
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['error' => 'Invalid method'], 405);
        }

        $notificationModel = $this->model('Notification');
        $notificationModel->markAllAsRead($this->getUserId());

        $this->json(['success' => true]);
    }

    public function getUnreadCount()
    {
        $this->requireAuth();

        $notificationModel = $this->model('Notification');
        $count = $notificationModel->countUnread($this->getUserId());

        $this->json(['count' => $count]);
    }
}
