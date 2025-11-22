<?php
// FILE: /app/models/Invitation.php

/**
 * Invitation Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Manages user invitations to workspaces.
 */
class Invitation extends Model
{
    protected $table = 'invitations';

    /**
     * Create invitation
     *
     * @param array $data
     * @return int Invitation ID
     */
    public function createInvitation($data)
    {
        // Generate unique token
        $data['token'] = bin2hex(random_bytes(32));

        // Set expiration (7 days from now)
        $data['expires_at'] = date('Y-m-d H:i:s', strtotime('+7 days'));

        return $this->create($data);
    }

    /**
     * Find invitation by token
     *
     * @param string $token
     * @return array|false
     */
    public function findByToken($token)
    {
        $sql = "SELECT i.*, t.name as tenant_name, u.name as inviter_name
                FROM {$this->table} i
                LEFT JOIN tenants t ON i.tenant_id = t.id
                LEFT JOIN users u ON i.invited_by = u.id
                WHERE i.token = :token
                LIMIT 1";

        $stmt = $this->query($sql, ['token' => $token]);
        return $stmt->fetch();
    }

    /**
     * Get pending invitations by tenant
     *
     * @param int|null $tenantId
     * @return array
     */
    public function getPendingInvitations($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT i.*, u.name as inviter_name
                FROM {$this->table} i
                LEFT JOIN users u ON i.invited_by = u.id
                WHERE i.tenant_id = :tenant_id
                AND i.status = 'pending'
                AND i.expires_at > NOW()
                ORDER BY i.created_at DESC";

        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);
        return $stmt->fetchAll();
    }

    /**
     * Accept invitation
     *
     * @param string $token
     * @return array|false Invitation data if valid
     */
    public function acceptInvitation($token)
    {
        $invitation = $this->findByToken($token);

        if (!$invitation) {
            return false;
        }

        // Check if expired
        if (strtotime($invitation['expires_at']) < time()) {
            $this->update($invitation['id'], ['status' => 'expired'], false);
            return false;
        }

        // Check if already accepted
        if ($invitation['status'] !== 'pending') {
            return false;
        }

        // Mark as accepted
        $this->update($invitation['id'], ['status' => 'accepted'], false);

        return $invitation;
    }

    /**
     * Check if email already invited
     *
     * @param string $email
     * @param int|null $tenantId
     * @return bool
     */
    public function isEmailInvited($email, $tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT COUNT(*) as count FROM {$this->table}
                WHERE email = :email
                AND tenant_id = :tenant_id
                AND status = 'pending'
                AND expires_at > NOW()";

        $stmt = $this->query($sql, [
            'email' => $email,
            'tenant_id' => $tenantId
        ]);

        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }
}
