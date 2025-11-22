<?php
// FILE: /app/models/User.php

/**
 * User Model
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * Handles user data and authentication operations.
 */
class User extends Model
{
    protected $table = 'users';

    /**
     * Find user by email
     *
     * @param string $email
     * @return array|false
     */
    public function findByEmail($email)
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        $stmt = $this->query($sql, ['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Create new user
     *
     * @param array $data
     * @return int User ID
     */
    public function createUser($data)
    {
        // Hash password before storing
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->create($data);
    }

    /**
     * Verify user password
     *
     * @param string $email
     * @param string $password
     * @return array|false User data if valid, false otherwise
     */
    public function verifyCredentials($email, $password)
    {
        $user = $this->findByEmail($email);

        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }

        return false;
    }

    /**
     * Get users by tenant
     *
     * @param int|null $tenantId
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getUsersByTenant($tenantId = null, $limit = 20, $offset = 0)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT id, tenant_id, email, name, role, avatar, status, last_login, created_at
                FROM {$this->table}
                WHERE tenant_id = :tenant_id
                ORDER BY created_at DESC
                LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Count users by tenant
     *
     * @param int|null $tenantId
     * @return int
     */
    public function countByTenant($tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE tenant_id = :tenant_id";
        $stmt = $this->query($sql, ['tenant_id' => $tenantId]);
        $result = $stmt->fetch();

        return (int)$result['count'];
    }

    /**
     * Update last login timestamp
     *
     * @param int $userId
     * @return bool
     */
    public function updateLastLogin($userId)
    {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = :id";
        return $this->query($sql, ['id' => $userId]);
    }

    /**
     * Update user password
     *
     * @param int $userId
     * @param string $newPassword
     * @return bool
     */
    public function updatePassword($userId, $newPassword)
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        $sql = "UPDATE {$this->table} SET password = :password WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':id', $userId, PDO::PARAM_INT);

        return $stmt->execute();
    }

    /**
     * Get user with tenant information
     *
     * @param int $userId
     * @return array|false
     */
    public function getUserWithTenant($userId)
    {
        $sql = "SELECT u.*, t.name as tenant_name, t.slug as tenant_slug
                FROM {$this->table} u
                LEFT JOIN tenants t ON u.tenant_id = t.id
                WHERE u.id = :id
                LIMIT 1";

        $stmt = $this->query($sql, ['id' => $userId]);
        return $stmt->fetch();
    }

    /**
     * Search users
     *
     * @param string $search
     * @param int|null $tenantId
     * @return array
     */
    public function search($search, $tenantId = null)
    {
        $tenantId = $tenantId ?: $this->tenantId;

        $sql = "SELECT id, email, name, role, avatar
                FROM {$this->table}
                WHERE tenant_id = :tenant_id
                AND (name LIKE :search OR email LIKE :search)
                ORDER BY name ASC
                LIMIT 20";

        $searchTerm = "%{$search}%";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId, PDO::PARAM_INT);
        $stmt->bindParam(':search', $searchTerm);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
