<?php
/**
 * Database — shared MySQLi connection using settings from config.php.
 * Prepared statements are used everywhere for safety.
 */
require_once __DIR__ . '/../config/config.php';

class Database
{
    /** @var mysqli */
    public $conn;

    function __construct()
    {
        $this->conn = new mysqli(DB_SERVER, DB_USER, DB_PASS, DB_NAME);

        if ($this->conn->connect_error) {
            die('Database connection failed: ' . $this->conn->connect_error);
        }

        $this->conn->set_charset('utf8mb4');
    }

    public function connect()
    {
        return $this->conn;
    }
}
