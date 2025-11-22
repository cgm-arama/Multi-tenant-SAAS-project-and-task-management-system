<?php
// FILE: /app/models/Payment.php

/**
 * Payment Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages payment transactions.
 */
class Payment extends Model
{
    protected $table = 'payments';

    /**
     * Get payments by tenant
     *
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getPaymentsByTenant($tenantId = null, $limit = 20, $offset = 0)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT p.*, i.invoice_number
                FROM {$this->table} p
                LEFT JOIN invoices i ON p.invoice_id = i.id
                WHERE p.tenant_id = :tenant_id
                ORDER BY p.created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Create payment and update invoice
     *
     * @param array $data
     * @return int Payment ID
     */
    public function createPayment($data)
    {
        $paymentId = $this->create($data);

        // If payment is successful, mark invoice as paid
        if ($data['status'] === 'success' && isset($data['invoice_id'])) {
            $invoiceModel = new Invoice();
            $invoiceModel->markAsPaid($data['invoice_id']);
        }

        return $paymentId;
    }

    /**
     * Simulate payment processing
     *
     * @param int $invoiceId
     * @param array $paymentData
     * @return array ['success' => bool, 'message' => string, 'payment_id' => int]
     */
    public function processPayment($invoiceId, $paymentData)
    {
        // Get invoice
        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($invoiceId, false);

        if (!$invoice) {
            return ['success' => false, 'message' => 'Invoice not found'];
        }

        // Simulate payment (always succeed in demo)
        $paymentId = $this->createPayment([
            'tenant_id' => $invoice['tenant_id'],
            'invoice_id' => $invoiceId,
            'amount' => $invoice['total'],
            'payment_method' => $paymentData['payment_method'] ?? 'card',
            'transaction_id' => 'txn_' . uniqid(),
            'status' => 'success'
        ]);

        return [
            'success' => true,
            'message' => 'Payment processed successfully',
            'payment_id' => $paymentId
        ];
    }
}
