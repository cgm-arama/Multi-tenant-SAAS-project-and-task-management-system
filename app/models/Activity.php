<?php
// FILE: /app/models/Activity.php

/**
 * Activity Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages activity logging for projects and tasks.
 */
class Activity extends Model
{
    protected $table = 'activities';

    /**
     * Log activity
     *
     * @param array $data
     * @return int Activity ID
     */
    public function log($data)
    {
        // Ensure tenant_id is set
        if (!isset($data['tenant_id'])) {
            $data['tenant_id'] = $this->tenantId;
        }

        return $this->create($data);
    }

    /**
     * Get activities by project
     *
     * @param int $projectId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getActivitiesByProject($projectId, $limit = 50, $offset = 0)
    {
        $sql = "SELECT a.*, u.name as user_name, u.avatar as user_avatar
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.project_id = :project_id
                AND a.tenant_id = :tenant_id
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get activities by task
     *
     * @param int $taskId
     * @param int $limit
     * @return array
     */
    public function getActivitiesByTask($taskId, $limit = 50)
    {
        $sql = "SELECT a.*, u.name as user_name, u.avatar as user_avatar
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.task_id = :task_id
                AND a.tenant_id = :tenant_id
                ORDER BY a.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':task_id', $taskId, PDO::PARAM_INT);
        $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get recent activities for dashboard
     *
     * @param int $limit
     * @return array
     */
    public function getRecentActivities($limit = 20)
    {
        $sql = "SELECT a.*, u.name as user_name, u.avatar as user_avatar,
                       p.name as project_name
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                LEFT JOIN projects p ON a.project_id = p.id
                WHERE a.tenant_id = :tenant_id
                ORDER BY a.created_at DESC
                LIMIT :limit";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
