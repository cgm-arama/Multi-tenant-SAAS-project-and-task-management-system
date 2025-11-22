<?php
// FILE: /app/models/Plan.php

/**
 * Plan Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages subscription plans.
 */
class Plan extends Model
{
    protected $table = 'plans';

    /**
     * Get all active plans
     *
     * @return array
     */
    public function getActivePlans()
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE status = 'active'
                ORDER BY price ASC";

        $stmt = $this->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Find plan by slug
     *
     * @param string $slug
     * @return array|false
     */
    public function findBySlug($slug)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE slug = :slug
                LIMIT 1";

        $stmt = $this->query($sql, ['slug' => $slug]);
        return $stmt->fetch();
    }

    /**
     * Get plan limits
     *
     * @param int $planId
     * @return array
     */
    public function getLimits($planId)
    {
        $plan = $this->find($planId, false);

        if (!$plan) {
            return [];
        }

        return [
            'max_projects' => (int)$plan['max_projects'],
            'max_users' => (int)$plan['max_users'],
            'max_tasks' => (int)$plan['max_tasks'],
            'max_storage_mb' => (int)$plan['max_storage_mb']
        ];
    }
}
