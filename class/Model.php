<?php
/**
 * Model — thin base class providing prepared-statement helpers for the
 * ePalmHaul service layer. All domain services extend this.
 */
require_once __DIR__ . '/connection.php';

class Model extends Database
{
    public function __construct()
    {
        parent::__construct();
        $this->conn = $this->connect();
    }

    /**
     * Execute a prepared statement.
     * @param string $sql
     * @param string $types mysqli type string ('' for no params)
     * @param array  $params
     * @return mysqli_stmt
     */
    protected function run($sql, $types = '', array $params = [])
    {
        $stmt = $this->conn->prepare($sql);
        if ($stmt === false) {
            throw new RuntimeException('SQL prepare error: ' . $this->conn->error);
        }
        if ($types !== '') {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        return $stmt;
    }

    /** Return all rows as an array of assoc arrays. */
    protected function all($sql, $types = '', array $params = [])
    {
        return $this->run($sql, $types, $params)->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    /** Return the first row or null. */
    protected function one($sql, $types = '', array $params = [])
    {
        return $this->run($sql, $types, $params)->get_result()->fetch_assoc();
    }

    /** Return a single scalar value from the first column of the first row. */
    protected function scalar($sql, $types = '', array $params = [])
    {
        $row = $this->one($sql, $types, $params);
        return $row === null ? null : array_values($row)[0];
    }

    protected function insertId()
    {
        return $this->conn->insert_id;
    }
}
