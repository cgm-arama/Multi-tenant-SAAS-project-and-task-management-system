<?php
// FILE: /app/models/Label.php

/**
 * Label Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages task labels/tags.
 */
class Label extends Model
{
    protected $table = 'labels';

    /**
     * Get all labels for tenant
     *
     * @return array
     */
    public function getLabels()
    {
        $sql = "SELECT l.*,
                       (SELECT COUNT(*) FROM task_labels WHERE label_id = l.id) as task_count
                FROM {$this->table} l
                WHERE l.tenant_id = :tenant_id
                ORDER BY l.name ASC";

        $stmt = $this->query($sql, ['tenant_id' => $this->tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Get labels for a specific task
     *
     * @param int $taskId
     * @return array
     */
    public function getLabelsByTask($taskId)
    {
        $sql = "SELECT l.*
                FROM {$this->table} l
                INNER JOIN task_labels tl ON l.id = tl.label_id
                WHERE tl.task_id = :task_id
                ORDER BY l.name ASC";

        $stmt = $this->query($sql, ['task_id' => $taskId]);
        return $stmt->fetchAll();
    }

    /**
     * Attach label to task
     *
     * @param int $taskId
     * @param int $labelId
     * @return bool
     */
    public function attachToTask($taskId, $labelId)
    {
        $sql = "INSERT INTO task_labels (task_id, label_id) VALUES (:task_id, :label_id)";

        try {
            $this->query($sql, [
                'task_id' => $taskId,
                'label_id' => $labelId
            ]);
            return true;
        } catch (PDOException $e) {
            // Ignore duplicate entry errors
            return false;
        }
    }

    /**
     * Detach label from task
     *
     * @param int $taskId
     * @param int $labelId
     * @return bool
     */
    public function detachFromTask($taskId, $labelId)
    {
        $sql = "DELETE FROM task_labels WHERE task_id = :task_id AND label_id = :label_id";
        $this->query($sql, [
            'task_id' => $taskId,
            'label_id' => $labelId
        ]);

        return true;
    }

    /**
     * Find label by name
     *
     * @param string $name
     * @return array|false
     */
    public function findByName($name)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE name = :name AND tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->query($sql, [
            'name' => $name,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetch();
    }
}
