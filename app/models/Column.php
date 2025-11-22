<?php
// FILE: /app/models/Column.php

/**
 * Column Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages Kanban board columns/lists.
 */
class Column extends Model
{
    protected $table = 'columns';

    /**
     * Get columns by board with task counts
     *
     * @param int $boardId
     * @return array
     */
    public function getColumnsByBoard($boardId)
    {
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM tasks WHERE column_id = c.id) as task_count
                FROM {$this->table} c
                WHERE c.board_id = :board_id
                AND c.tenant_id = :tenant_id
                ORDER BY c.position ASC";

        $stmt = $this->query($sql, [
            'board_id' => $boardId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Get column with tasks
     *
     * @param int $columnId
     * @return array|false
     */
    public function getColumnWithTasks($columnId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE id = :id AND tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->query($sql, [
            'id' => $columnId,
            'tenant_id' => $this->tenantId
        ]);

        $column = $stmt->fetch();

        if ($column) {
            // Get tasks for this column
            $taskModel = new Task();
            $column['tasks'] = $taskModel->getTasksByColumn($columnId);
        }

        return $column;
    }

    /**
     * Update column positions
     *
     * @param array $positions Array of ['id' => position]
     * @return bool
     */
    public function updatePositions($positions)
    {
        $sql = "UPDATE {$this->table} SET position = :position WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);

        foreach ($positions as $id => $position) {
            $stmt->bindValue(':position', $position, PDO::PARAM_INT);
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->bindValue(':tenant_id', $this->tenantId, PDO::PARAM_INT);
            $stmt->execute();
        }

        return true;
    }

    /**
     * Check WIP limit
     *
     * @param int $columnId
     * @return array ['exceeded' => bool, 'current' => int, 'limit' => int]
     */
    public function checkWipLimit($columnId)
    {
        $column = $this->find($columnId);

        if (!$column || $column['wip_limit'] === null) {
            return ['exceeded' => false, 'current' => 0, 'limit' => null];
        }

        $sql = "SELECT COUNT(*) as count FROM tasks WHERE column_id = :column_id";
        $stmt = $this->query($sql, ['column_id' => $columnId]);
        $result = $stmt->fetch();

        $current = (int)$result['count'];
        $limit = (int)$column['wip_limit'];

        return [
            'exceeded' => $current >= $limit,
            'current' => $current,
            'limit' => $limit
        ];
    }
}
