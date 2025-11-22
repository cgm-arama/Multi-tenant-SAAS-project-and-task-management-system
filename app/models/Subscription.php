<?php
// FILE: /app/models/Subscription.php

/**
 * Subscription Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages tenant subscriptions.
 */
class Subscription extends Model
{
    protected $table = 'subscriptions';

    /**
     * Get active subscription for tenant
     *
     * @param int|null $tenantId
     * @return array|false
     */
    public function getActiveSubscription($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT s.*, p.name as plan_name, p.slug as plan_slug,
                       p.max_projects, p.max_users, p.max_tasks, p.max_storage_mb
                FROM {$this->table} s
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE s.tenant_id = :tenant_id
                AND s.status IN ('active', 'trialing')
                ORDER BY s.created_at DESC
                LIMIT 1";

        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);
        return $stmt->fetch();
    }

    /**
     * Check if tenant can perform action based on limits
     *
     * @param string $resource (projects, users, tasks, storage)
     * @param int|null $tenantId
     * @return array ['allowed' => bool, 'current' => int, 'limit' => int]
     */
    public function checkLimit($resource, $tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        // Get subscription
        $subscription = $this->getActiveSubscription($tenantId);

        if (!$subscription) {
            return ['allowed' => false, 'current' => 0, 'limit' => 0, 'message' => 'No active subscription'];
        }

        // Get current usage
        $usageModel = new Usage();
        $usage = $usageModel->getUsageByTenant($tenantId);

        $limitField = "max_{$resource}";
        $countField = "{$resource}_count";

        if ($resource === 'storage') {
            $countField = 'storage_used_mb';
        }

        $limit = (int)$subscription[$limitField];
        $current = $usage ? (int)$usage[$countField] : 0;

        return [
            'allowed' => $current < $limit,
            'current' => $current,
            'limit' => $limit,
            'message' => $current >= $limit ? "You have reached your plan limit for {$resource}" : null
        ];
    }

    /**
     * Get subscription with invoices
     *
     * @param int $subscriptionId
     * @return array|false
     */
    public function getSubscriptionWithInvoices($subscriptionId)
    {
        $subscription = $this->find($subscriptionId, false);

        if ($subscription) {
            $invoiceModel = new Invoice();
            $subscription['invoices'] = $invoiceModel->getInvoicesBySubscription($subscriptionId);
        }

        return $subscription;
    }

    /**
     * Cancel subscription
     *
     * @param int $subscriptionId
     * @return bool
     */
    public function cancel($subscriptionId)
    {
        return $this->update($subscriptionId, [
            'status' => 'canceled',
            'canceled_at' => date('Y-m-d H:i:s')
        ], false);
    }
}
