<?php
// FILE: /app/models/Tenant.php

/**
 * Tenant Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages workspace/company data and operations.
 */
class Tenant extends Model
{
    protected $table = 'tenants';

    /**
     * Find tenant by slug
     *
     * @param string $slug
     * @return array|false
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table} WHERE slug = :slug LIMIT 1";
        $stmt = $this->query($sql, ['slug' => $slug]);
        return $stmt->fetch();
    }

    /**
     * Get all tenants (platform admin only)
     *
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getAllTenants($limit = 20, $offset = 0)
    {
        $sql = "SELECT t.*,
                       (SELECT COUNT(*) FROM users WHERE tenant_id = t.id) as user_count,
                       (SELECT COUNT(*) FROM projects WHERE tenant_id = t.id) as project_count
                FROM {$this->table} t
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get tenant with subscription
     *
     * @param int $tenantId
     * @return array|false
     */
    public function getTenantWithSubscription($tenantId)
    {
        $sql = "SELECT t.*, s.status as subscription_status,
                       s.current_period_end, p.name as plan_name, p.slug as plan_slug
                FROM {$this->table} t
                LEFT JOIN subscriptions s ON t.id = s.tenant_id
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE t.id = :id
                LIMIT 1";

        $stmt = $this->query($sql, ['id' => $tenantId]);
        return $stmt->fetch();
    }

    /**
     * Create tenant with slug generation
     *
     * @param array $data
     * @return int
     */
    public function createTenant($data)
    {
        // Generate slug if not provided
        if (!isset($data['slug'])) {
            $data['slug'] = $this->generateSlug($data['name']);
        }

        return $this->create($data);
    }

    /**
     * Generate unique slug from name
     *
     * @param string $name
     * @return string
     */
    private function generateSlug($name)
    {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name), '-'));

        // Check if slug exists
        $original = $slug;
        $counter = 1;

        while ($this->findBySlug($slug)) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Count all tenants
     *
     * @return int
     */
    public function countAll()
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $stmt = $this->query($sql);
        $result = $stmt->fetch();

        return (int)$result['count'];
    }

    /**
     * Update tenant status
     *
     * @param int $tenantId
     * @param string $status
     * @return bool
     */
    public function updateStatus($tenantId, $status)
    {
        $sql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        return $this->query($sql, ['status' => $status, 'id' => $tenantId]);
    }
}
