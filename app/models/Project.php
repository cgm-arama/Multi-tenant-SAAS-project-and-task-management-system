<?php
// FILE: /app/models/Project.php

/**
 * Project Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages project data and operations.
 */
class Project extends Model
{
    protected $table = 'projects';

    /**
     * Get projects with owner and task count
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProjects($filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT p.*, u.name as owner_name,
                       (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.tenant_id = :tenant_id";

        $params = ['tenant_id' => $this->tenantId];

        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND p.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['owner_id'])) {
            $sql .= " AND p.owner_id = :owner_id";
            $params['owner_id'] = $filters['owner_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (p.name LIKE :search OR p.description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        $sql .= " ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get project with details
     *
     * @param int $projectId
     * @return array|false
     */
    public function getProjectWithDetails($projectId)
    {
        $sql = "SELECT p.*, u.name as owner_name,
                       (SELECT COUNT(*) FROM boards WHERE project_id = p.id) as board_count,
                       (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count,
                       (SELECT COUNT(*) FROM tasks WHERE project_id = p.id AND status = 'completed') as completed_count,
                       (SELECT COUNT(*) FROM project_members WHERE project_id = p.id) as member_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                WHERE p.id = :id AND p.tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->query($sql, [
            'id' => $projectId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetch();
    }

    /**
     * Count projects
     *
     * @param array $filters
     * @return int
     */
    public function countProjects($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE tenant_id = :tenant_id";

        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['owner_id'])) {
            $sql .= " AND owner_id = :owner_id";
            $params['owner_id'] = $filters['owner_id'];
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();

        return (int)$result['count'];
    }

    /**
     * Get projects accessible by user (including guest access)
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getProjectsByUser($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT DISTINCT p.*, u.name as owner_name,
                       (SELECT COUNT(*) FROM tasks WHERE project_id = p.id) as task_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.tenant_id = :tenant_id
                AND (p.owner_id = :user_id OR pm.user_id = :user_id)
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Check if user has access to project
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public function userHasAccess($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table} p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.id = :project_id
                AND p.tenant_id = :tenant_id
                AND (p.owner_id = :user_id OR pm.user_id = :user_id)";

        $stmt = $this->query($sql, [
            'project_id' => $projectId,
            'tenant_id' => $this->tenantId,
            'user_id' => $userId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }

    /**
     * Archive project
     *
     * @param int $projectId
     * @return bool
     */
    public function archive($projectId)
    {
        return $this->update($projectId, ['status' => 'archived']);
    }

    /**
     * Restore archived project
     *
     * @param int $projectId
     * @return bool
     */
    public function restore($projectId)
    {
        return $this->update($projectId, ['status' => 'active']);
    }
}
