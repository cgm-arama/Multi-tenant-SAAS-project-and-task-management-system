<?php
// FILE: /app/core/Model.php

/**
 * Base Model Class
 * SplashProjects - Multi-tenant SaaS Platform
 *
 * All models extend this base class to inherit common database operations.
 * Provides standardized methods for CRUD operations using PDO prepared statements.
 */
class Model
{
    protected $db;
    protected $table;
    protected $tenantId;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();

        // Set tenant ID from session if user is logged in
        if (isset($_SESSION['user_id']) && isset($_SESSION['tenant_id'])) {
            $this->tenantId = $_SESSION['tenant_id'];
        }
    }

    /**
     * Find record by ID with tenant isolation
     *
     * @param int $id
     * @param bool $includeTenantFilter
     * @return mixed
     */
    public function find($id, $includeTenantFilter = true)
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";

        if ($includeTenantFilter && $this->tenantId) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $sql .= " LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($includeTenantFilter && $this->tenantId) {
            $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all records with optional tenant isolation
     *
     * @param bool $includeTenantFilter
     * @return array
     */
    public function all($includeTenantFilter = true)
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($includeTenantFilter && $this->tenantId) {
            $sql .= " WHERE tenant_id = :tenant_id";
        }

        $stmt = $this->db->prepare($sql);

        if ($includeTenantFilter && $this->tenantId) {
            $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        }

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Insert new record
     *
     * @param array $data
     * @return int Last insert ID
     */
    public function create($data)
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->execute();

        return $this->db->lastInsertId();
    }

    /**
     * Update record by ID
     *
     * @param int $id
     * @param array $data
     * @param bool $includeTenantFilter
     * @return bool
     */
    public function update($id, $data, $includeTenantFilter = true)
    {
        $setParts = [];
        foreach (array_keys($data) as $key) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = :id";

        if ($includeTenantFilter && $this->tenantId) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $stmt = $this->db->prepare($sql);

        foreach ($data as $key => $value) {
            $stmt->bindValue(":{$key}", $value);
        }

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        if ($includeTenantFilter && $this->tenantId) {
            $stmt->bindValue(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Delete record by ID
     *
     * @param int $id
     * @param bool $includeTenantFilter
     * @return bool
     */
    public function delete($id, $includeTenantFilter = true)
    {
        $sql = "DELETE FROM {$this->table} WHERE id = :id";

        if ($includeTenantFilter && $this->tenantId) {
            $sql .= " AND tenant_id = :tenant_id";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        if ($includeTenantFilter && $this->tenantId) {
            $stmt->bindParam(':tenant_id', $this->tenantId, PDO::PARAM_INT);
        }

        return $stmt->execute();
    }

    /**
     * Execute custom query
     *
     * @param string $sql
     * @param array $params
     * @return PDOStatement
     */
    protected function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);

        foreach ($params as $key => $value) {
            if (is_int($key)) {
                $stmt->bindValue($key + 1, $value);
            } else {
                $stmt->bindValue(":{$key}", $value);
            }
        }

        $stmt->execute();

        return $stmt;
    }

    /**
     * Set tenant ID manually (for platform admin operations)
     *
     * @param int $tenantId
     */
    public function setTenantId($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Get current tenant ID
     *
     * @return int|null
     */
    public function getTenantId()
    {
        return $this->tenantId;
    }
}
