<?php
// FILE: /app/models/ApiKey.php

/**
 * ApiKey Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages API keys for external integrations.
 */
class ApiKey extends Model
{
    protected $table = 'api_keys';

    /**
     * Find API key and return tenant
     *
     * @param string $apiKey
     * @return array|false
     */
    public function findByKey($apiKey)
    {
        $sql = "SELECT a.*, t.name as tenant_name, t.status as tenant_status
                FROM {$this->table} a
                LEFT JOIN tenants t ON a.tenant_id = t.id
                WHERE a.api_key = :api_key
                AND a.status = 'active'
                LIMIT 1";

        $stmt = $this->query($sql, ['api_key' => $apiKey]);
        return $stmt->fetch();
    }

    /**
     * Get API keys by tenant
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getKeysByTenant($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT * FROM {$this->table}
                WHERE tenant_id = :tenant_id
                ORDER BY created_at DESC";

        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Generate new API key
     *
     * @param int $tenantId
     * @param string $name
     * @return string API key
     */
    public function generateKey($tenantId, $name)
    {
        $apiKey = 'sk_live_' . bin2hex(random_bytes(32));

        $this->create([
            'tenant_id' => $tenantId,
            'api_key' => $apiKey,
            'name' => $name,
            'status' => 'active'
        ]);

        return $apiKey;
    }

    /**
     * Update last used timestamp
     *
     * @param int $keyId
     * @return bool
     */
    public function updateLastUsed($keyId)
    {
        $sql = "UPDATE {$this->table} SET last_used = NOW() WHERE id = :id";
        $stmt = $this->query($sql, ['id' => $keyId]);
        return true;
    }

    /**
     * Revoke API key
     *
     * @param int $keyId
     * @return bool
     */
    public function revokeKey($keyId)
    {
        return $this->update($keyId, ['status' => 'inactive'], false);
    }
}
