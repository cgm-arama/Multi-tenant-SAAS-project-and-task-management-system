<?php
// FILE: /app/models/Usage.php


class Usage extends Model
{
    protected $table = '`usage`';

    /**
     * Get usage by tenant
     *
     * @param int|null $tenantId
     * @return array|false
     */
    public function getUsageByTenant($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id LIMIT 1";
        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);

        return $stmt->fetch();
    }

    /**
     * Update usage counts for tenant
     *
     * @param int|null $tenantId
     * @return bool
     */
    public function updateUsage($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $projectModel = new Project();
        $projectModel->setTenantId($tenantId);
        $projectsCount = $projectModel->countProjects();

        $userModel = new User();
        $userModel->setTenantId($tenantId);
        $usersCount = $userModel->countByTenant($tenantId);

        $taskModel = new Task();
        $taskModel->setTenantId($tenantId);
        $tasksCount = $taskModel->countTasks();

        $attachmentModel = new Attachment();
        $attachmentModel->setTenantId($tenantId);
        $storageBytes = $attachmentModel->getTotalStorageUsed($tenantId);
        $storageMB = round($storageBytes / (1024 * 1024), 2);

        $existing = $this->getUsageByTenant($tenantId);

        $data = [
            'projects_count' => $projectsCount,
            'users_count' => $usersCount,
            'tasks_count' => $tasksCount,
            'storage_used_mb' => $storageMB
        ];

        if ($existing) {
            $sql = "UPDATE {$this->table}
                    SET projects_count = :projects_count,
                        users_count = :users_count,
                        tasks_count = :tasks_count,
                        storage_used_mb = :storage_used_mb,
                        updated_at = NOW()
                    WHERE tenant_id = :tenant_id";

            $data['tenant_id'] = $tenantId;
            $this->query($sql, $data);
        } else {
            $data['tenant_id'] = $tenantId;
            $this->create($data);
        }

        return true;
    }

    /**
     * Increment usage counter
     *
     * @param string $resource
     * @param int $amount
     * @param int|null $tenantId
     * @return bool
     */
    public function increment($resource, $amount = 1, $tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $field = "{$resource}_count";

        $sql = "UPDATE {$this->table}
                SET {$field} = {$field} + :amount
                WHERE tenant_id = :tenant_id";

        $this->query($sql, [
            'amount' => $amount,
            'tenant_id' => $tenantId
        ]);

        return true;
    }

    /**
     * Decrement usage counter
     *
     * @param string $resource
     * @param int $amount
     * @param int|null $tenantId
     * @return bool
     */
    public function decrement($resource, $amount = 1, $tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $field = "{$resource}_count";

        $sql = "UPDATE {$this->table}
                SET {$field} = GREATEST(0, {$field} - :amount)
                WHERE tenant_id = :tenant_id";

        $this->query($sql, [
            'amount' => $amount,
            'tenant_id' => $tenantId
        ]);

        return true;
    }
}