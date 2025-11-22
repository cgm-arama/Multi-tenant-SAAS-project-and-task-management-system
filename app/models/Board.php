<?php
// FILE: /app/models/Board.php

/**
 * Board Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages Kanban boards within projects.
 */
class Board extends Model
{
    protected $table = 'boards';

    /**
     * Get boards by project
     *
     * @param int $projectId
     * @return array
     */
    public function getBoardsByProject($projectId)
    {
        $sql = "SELECT b.*,
                       (SELECT COUNT(*) FROM columns WHERE board_id = b.id) as column_count,
                       (SELECT COUNT(*) FROM tasks WHERE board_id = b.id) as task_count
                FROM {$this->table} b
                WHERE b.project_id = :project_id
                AND b.tenant_id = :tenant_id
                ORDER BY b.position ASC";

        $stmt = $this->query($sql, [
            'project_id' => $projectId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Get board with columns and tasks
     *
     * @param int $boardId
     * @return array|false
     */
    public function getBoardWithColumns($boardId)
    {
        $sql = "SELECT b.*, p.name as project_name
                FROM {$this->table} b
                LEFT JOIN projects p ON b.project_id = p.id
                WHERE b.id = :id AND b.tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->query($sql, [
            'id' => $boardId,
            'tenant_id' => $this->tenantId
        ]);

        $board = $stmt->fetch();

        if ($board) {
            // Get columns for this board
            $columnModel = new Column();
            $board['columns'] = $columnModel->getColumnsByBoard($boardId);
        }

        return $board;
    }

    /**
     * Create board with default columns
     *
     * @param array $data
     * @param array $defaultColumns
     * @return int Board ID
     */
    public function createBoardWithColumns($data, $defaultColumns = null)
    {
        // Create board
        $boardId = $this->create($data);

        // Create default columns if provided
        if ($defaultColumns === null) {
            $defaultColumns = ['To Do', 'In Progress', 'Done'];
        }

        if ($boardId && !empty($defaultColumns)) {
            $columnModel = new Column();

            foreach ($defaultColumns as $index => $columnName) {
                $columnModel->create([
                    'tenant_id' => $data['tenant_id'],
                    'board_id' => $boardId,
                    'name' => $columnName,
                    'position' => $index
                ]);
            }
        }

        return $boardId;
    }

    /**
     * Count boards by project
     *
     * @param int $projectId
     * @return int
     */
    public function countByProject($projectId)
    {
        $sql = "SELECT COUNT(*) as count
                FROM {$this->table}
                WHERE project_id = :project_id AND tenant_id = :tenant_id";

        $stmt = $this->query($sql, [
            'project_id' => $projectId,
            'tenant_id' => $this->tenantId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}
