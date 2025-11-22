<?php
// FILE: /app/models/Task.php

/**
 * Task Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages Kanban task cards.
 */
class Task extends Model
{
    protected $table = 'tasks';

    /**
     * Get tasks by column with full details
     *
     * @param int $columnId
     * @return array
     */
    public function getTasksByColumn($columnId)
    {
        $sql = "SELECT t.*,
                       u1.name as creator_name,
                       u2.name as assignee_name,
                       u2.avatar as assignee_avatar,
                       (SELECT COUNT(*) FROM comments WHERE task_id = t.id) as comment_count,
                       (SELECT COUNT(*) FROM attachments WHERE task_id = t.id) as attachment_count,
                       (SELECT COUNT(*) FROM checklists WHERE task_id = t.id) as checklist_total,
                       (SELECT COUNT(*) FROM checklists WHERE task_id = t.id AND completed = 1) as checklist_completed
                FROM {$this->table} t
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.column_id = :column_id
                AND t.tenant_id = :tenant_id
                ORDER BY t.position ASC, t.created_at DESC";

        $stmt = $this->query($sql, [
            'column_id' => $columnId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Get task with all related data
     *
     * @param int $taskId
     * @return array|false
     */
    public function getTaskWithDetails($taskId)
    {
        $sql = "SELECT t.*,
                       p.name as project_name,
                       b.name as board_name,
                       c.name as column_name,
                       u1.name as creator_name,
                       u2.name as assignee_name,
                       u2.email as assignee_email
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN boards b ON t.board_id = b.id
                LEFT JOIN columns c ON t.column_id = c.id
                LEFT JOIN users u1 ON t.created_by = u1.id
                LEFT JOIN users u2 ON t.assigned_to = u2.id
                WHERE t.id = :id AND t.tenant_id = :tenant_id
                LIMIT 1";

        $stmt = $this->query($sql, [
            'id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        $task = $stmt->fetch();

        if ($task) {
            // Get labels
            $labelModel = new Label();
            $task['labels'] = $labelModel->getLabelsByTask($taskId);

            // Get checklists
            $checklistModel = new Checklist();
            $task['checklists'] = $checklistModel->getChecklistsByTask($taskId);

            // Get comments
            $commentModel = new Comment();
            $task['comments'] = $commentModel->getCommentsByTask($taskId);

            // Get attachments
            $attachmentModel = new Attachment();
            $task['attachments'] = $attachmentModel->getAttachmentsByTask($taskId);
        }

        return $task;
    }

    /**
     * Get tasks by filters
     *
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTasks($filters = [], $limit = 20, $offset = 0)
    {
        $sql = "SELECT t.*,
                       p.name as project_name,
                       u.name as assignee_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.tenant_id = :tenant_id";

        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['project_id'])) {
            $sql .= " AND t.project_id = :project_id";
            $params['project_id'] = $filters['project_id'];
        }

        if (!empty($filters['board_id'])) {
            $sql .= " AND t.board_id = :board_id";
            $params['board_id'] = $filters['board_id'];
        }

        if (!empty($filters['assigned_to'])) {
            $sql .= " AND t.assigned_to = :assigned_to";
            $params['assigned_to'] = $filters['assigned_to'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND t.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND t.priority = :priority";
            $params['priority'] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (t.title LIKE :search OR t.description LIKE :search)";
            $params['search'] = "%{$filters['search']}%";
        }

        if (!empty($filters['label_id'])) {
            $sql .= " AND t.id IN (SELECT task_id FROM task_labels WHERE label_id = :label_id)";
            $params['label_id'] = $filters['label_id'];
        }

        $sql .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

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
     * Get tasks assigned to user
     *
     * @param int $userId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTasksByUser($userId, $limit = 20, $offset = 0)
    {
        $sql = "SELECT t.*,
                       p.name as project_name,
                       b.name as board_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN boards b ON t.board_id = b.id
                WHERE t.tenant_id = :tenant_id
                AND t.assigned_to = :user_id
                AND t.status != 'completed'
                ORDER BY
                    CASE WHEN t.due_date < CURDATE() THEN 0 ELSE 1 END,
                    t.due_date ASC,
                    FIELD(t.priority, 'critical', 'high', 'medium', 'low')
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
     * Move task to different column
     *
     * @param int $taskId
     * @param int $newColumnId
     * @param int|null $newPosition
     * @return bool
     */
    public function moveTask($taskId, $newColumnId, $newPosition = null)
    {
        $data = ['column_id' => $newColumnId];

        if ($newPosition !== null) {
            $data['position'] = $newPosition;
        }

        return $this->update($taskId, $data);
    }

    /**
     * Update task positions within column
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
     * Count tasks
     *
     * @param array $filters
     * @return int
     */
    public function countTasks($filters = [])
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE tenant_id = :tenant_id";

        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['project_id'])) {
            $sql .= " AND project_id = :project_id";
            $params['project_id'] = $filters['project_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }

        $stmt = $this->query($sql, $params);
        $result = $stmt->fetch();

        return (int)$result['count'];
    }

    /**
     * Get overdue tasks
     *
     * @param int|null $userId
     * @return array
     */
    public function getOverdueTasks($userId = null)
    {
        $sql = "SELECT t.*, p.name as project_name
                FROM {$this->table} t
                LEFT JOIN projects p ON t.project_id = p.id
                WHERE t.tenant_id = :tenant_id
                AND t.due_date < CURDATE()
                AND t.status != 'completed'";

        $params = ['tenant_id' => $this->tenantId];

        if ($userId) {
            $sql .= " AND t.assigned_to = :user_id";
            $params['user_id'] = $userId;
        }

        $sql .= " ORDER BY t.due_date ASC";

        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }
}
