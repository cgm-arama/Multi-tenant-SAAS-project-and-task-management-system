<?php
// FILE: /app/models/Checklist.php

/**
 * Checklist Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages task checklist items (subtasks).
 */
class Checklist extends Model
{
    protected $table = 'checklists';

    /**
     * Get checklists by task
     *
     * @param int $taskId
     * @return array
     */
    public function getChecklistsByTask($taskId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE task_id = :task_id
                AND tenant_id = :tenant_id
                ORDER BY position ASC, created_at ASC";

        $stmt = $this->query($sql, [
            'task_id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Toggle checklist item completion
     *
     * @param int $checklistId
     * @return bool
     */
    public function toggleCompleted($checklistId)
    {
        $sql = "UPDATE {$this->table}
                SET completed = NOT completed
                WHERE id = :id AND tenant_id = :tenant_id";

        $stmt = $this->query($sql, [
            'id' => $checklistId,
            'tenant_id' => $this->tenantId
        ]);

        return true;
    }

    /**
     * Get checklist progress for task
     *
     * @param int $taskId
     * @return array
     */
    public function getProgress($taskId)
    {
        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(completed) as completed
                FROM {$this->table}
                WHERE task_id = :task_id AND tenant_id = :tenant_id";

        $stmt = $this->query($sql, [
            'task_id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        $result = $stmt->fetch();

        $total = (int)$result['total'];
        $completed = (int)$result['completed'];

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => $total > 0 ? round(($completed / $total) * 100) : 0
        ];
    }
}
