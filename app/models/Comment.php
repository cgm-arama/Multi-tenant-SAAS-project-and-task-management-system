<?php
// FILE: /app/models/Comment.php

/**
 * Comment Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages task comments.
 */
class Comment extends Model
{
    protected $table = 'comments';

    /**
     * Get comments by task
     *
     * @param int $taskId
     * @return array
     */
    public function getCommentsByTask($taskId)
    {
        $sql = "SELECT c.*, u.name as user_name, u.avatar as user_avatar
                FROM {$this->table} c
                LEFT JOIN users u ON c.user_id = u.id
                WHERE c.task_id = :task_id
                AND c.tenant_id = :tenant_id
                ORDER BY c.created_at ASC";

        $stmt = $this->query($sql, [
            'task_id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Count comments by task
     *
     * @param int $taskId
     * @return int
     */
    public function countByTask($taskId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE task_id = :task_id AND tenant_id = :tenant_id";

        $stmt = $this->query($sql, [
            'task_id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}
