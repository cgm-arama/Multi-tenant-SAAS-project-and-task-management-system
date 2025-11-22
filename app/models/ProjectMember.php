<?php
// FILE: /app/models/ProjectMember.php

/**
 * ProjectMember Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages project member access control.
 */
class ProjectMember extends Model
{
    protected $table = 'project_members';

    /**
     * Get members by project
     *
     * @param int $projectId
     * @return array
     */
    public function getMembersByProject($projectId)
    {
        $sql = "SELECT pm.*, u.name, u.email, u.avatar, u.role as user_role
                FROM {$this->table} pm
                LEFT JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = :project_id
                ORDER BY pm.created_at ASC";

        $stmt = $this->query($sql, ['project_id' => $projectId]);
        return $stmt->fetchAll();
    }

    /**
     * Add member to project
     *
     * @param int $projectId
     * @param int $userId
     * @param string $role
     * @return int|bool
     */
    public function addMember($projectId, $userId, $role = 'member')
    {
        try {
            return $this->create([
                'project_id' => $projectId,
                'user_id' => $userId,
                'role' => $role
            ]);
        } catch (PDOException $e) {
            // Member already exists
            return false;
        }
    }

    /**
     * Remove member from project
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public function removeMember($projectId, $userId)
    {
        $sql = "DELETE FROM {$this->table}
                WHERE project_id = :project_id
                AND user_id = :user_id";

        $stmt = $this->query($sql, [
            'project_id' => $projectId,
            'user_id' => $userId
        ]);

        return true;
    }

    /**
     * Check if user is project member
     *
     * @param int $projectId
     * @param int $userId
     * @return bool
     */
    public function isMember($projectId, $userId)
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE project_id = :project_id
                AND user_id = :user_id";

        $stmt = $this->query($sql, [
            'project_id' => $projectId,
            'user_id' => $userId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }

    /**
     * Update member role
     *
     * @param int $projectId
     * @param int $userId
     * @param string $role
     * @return bool
     */
    public function updateRole($projectId, $userId, $role)
    {
        $sql = "UPDATE {$this->table}
                SET role = :role
                WHERE project_id = :project_id
                AND user_id = :user_id";

        $stmt = $this->query($sql, [
            'role' => $role,
            'project_id' => $projectId,
            'user_id' => $userId
        ]);

        return true;
    }
}
