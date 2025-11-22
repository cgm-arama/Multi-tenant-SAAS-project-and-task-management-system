<?php
// FILE: /app/models/Attachment.php

/**
 * Attachment Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages file attachments on tasks.
 */
class Attachment extends Model
{
    protected $table = 'attachments';

    /**
     * Get attachments by task
     *
     * @param int $taskId
     * @return array
     */
    public function getAttachmentsByTask($taskId)
    {
        $sql = "SELECT a.*, u.name as uploader_name
                FROM {$this->table} a
                LEFT JOIN users u ON a.user_id = u.id
                WHERE a.task_id = :task_id
                AND a.tenant_id = :tenant_id
                ORDER BY a.created_at DESC";

        $stmt = $this->query($sql, [
            'task_id' => $taskId,
            'tenant_id' => $this->tenantId
        ]);

        return $stmt->fetchAll();
    }

    /**
     * Count attachments by task
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

    /**
     * Get total storage used by tenant
     *
     * @param int|null $tenantId
     * @return int Size in bytes
     */
    public function getTotalStorageUsed($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT SUM(file_size) as total FROM {$this->table}
                WHERE tenant_id = :tenant_id";

        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);
        $result = $stmt->fetch();

        return (int)$result['total'];
    }

    /**
     * Delete attachment with file
     *
     * @param int $attachmentId
     * @return bool
     */
    public function deleteAttachment($attachmentId)
    {
        // Get attachment data first
        $attachment = $this->find($attachmentId);

        if (!$attachment) {
            return false;
        }

        // Delete file from filesystem
        $filePath = __DIR__ . '/../../' . $attachment['file_path'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete from database
        return $this->delete($attachmentId);
    }
}
