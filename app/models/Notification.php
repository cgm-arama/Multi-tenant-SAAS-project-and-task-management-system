<?php
// FILE: /app/models/Notification.php

/**
 * Notification Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages in-app notifications for users.
 */
class Notification extends Model
{
    protected $table = 'notifications';

    /**
     * Create notification
     *
     * @param array $data
     * @return int Notification ID
     */
    public function createNotification($data)
    {
        // Ensure tenant_id is set
        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = $this->tenantId;
        }

        return $this->create($data);
    }

    /**
     * Get notifications for user
     *
     * @param int $userId
     * @param bool $unreadOnly
     * @param int $limit
     * @return array
     */
    public function getNotificationsByUser($userId, $unreadOnly = false, $limit = 50)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE user_id = :user_id
                AND tenant_id = :tenant_id";

        if ($unreadOnly) {
            $sql .= " AND is_read = 0";
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count unread notifications for user
     *
     * @param int $userId
     * @return int
     */
    public function countUnread($userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE user_id = :user_id
                AND tenant_id = :tenant_id
                AND is_read = 0";

        $stmt = $this->query($sql, [
            'user_id' => $userId,
            'tenant_id' => $this->tenantId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'];
    }

    /**
     * Mark notification as read
     *
     * @param int $notificationId
     * @return bool
     */
    public function markAsRead($notificationId)
    {
        return $this->update($notificationId, ['is_read' => 1]);
    }

    /**
     * Mark all notifications as read for user
     *
     * @param int $userId
     * @return bool
     */
    public function markAllAsRead($userId)
    {
        $sql = "UPDATE {$this->table}
                SET is_read = 1
                WHERE user_id = :user_id
                AND tenant_id = :tenant_id
                AND is_read = 0";

        $stmt = $this->query($sql, [
            'user_id' => $userId,
            'tenant_id' => $this->tenantId
        ]);

        return true;
    }
}
