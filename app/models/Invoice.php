<?php
// FILE: /app/models/Invoice.php

/**
 * Invoice Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages billing invoices.
 */
class Invoice extends Model
{
    protected $table = 'invoices';

    /**
     * Get invoices by tenant
     *
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getInvoicesByTenant($tenantId = null, $limit = 20, $offset = 0)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT i.*, p.name as plan_name
                FROM {$this->table} i
                LEFT JOIN subscriptions s ON i.subscription_id = s.id
                LEFT JOIN plans p ON s.plan_id = p.id
                WHERE i.tenant_id = :tenant_id
                ORDER BY i.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Get invoices by subscription
     *
     * @param int $subscriptionId
     * @return array
     */
    public function getInvoicesBySubscription($subscriptionId)
    {
        $sql = "SELECT * FROM {$this->table}
                WHERE subscription_id = :subscription_id
                ORDER BY created_at DESC";

        $stmt = $this->query($sql, ['subscription_id' => $subscriptionId]);
        return $stmt->fetchAll();
    }

    /**
     * Generate invoice number
     *
     * @return string
     */
    public function generateInvoiceNumber()
    {
        $prefix = 'INV-' . date('Y') . '-';

        // Get last invoice number
        $sql = "SELECT invoice_number FROM {$this->table}
                WHERE invoice_number LIKE :prefix
                ORDER BY id DESC
                LIMIT 1";

        $stmt = $this->query($sql, ['prefix' => $prefix . '%']);
        $last = $stmt->fetch();

        if ($last) {
            // Extract number and increment
            $lastNumber = (int)str_replace($prefix, '', $last['invoice_number']);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Mark invoice as paid
     *
     * @param int $invoiceId
     * @return bool
     */
    public function markAsPaid($invoiceId)
    {
        return $this->update($invoiceId, [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s')
        ], false);
    }

    /**
     * Get invoice with payment
     *
     * @param int $invoiceId
     * @return array|false
     */
    public function getInvoiceWithPayment($invoiceId)
    {
        $sql = "SELECT i.*, p.amount as payment_amount, p.status as payment_status,
                       p.transaction_id, p.created_at as payment_date
                FROM {$this->table} i
                LEFT JOIN payments p ON i.id = p.invoice_id
                WHERE i.id = :id
                LIMIT 1";

        $stmt = $this->query($sql, ['id' => $invoiceId]);
        return $stmt->fetch();
    }
}
