<?php
namespace App\Models;

use App\Config\Database;
use PDO;

class Model {
    /**
     * @var PDO
     */
    protected $db;

    public function __construct() {
        // Resolve database connection singleton
        $this->db = Database::getConnection();
    }

    /**
     * Execute a query with parameters (Prepared Statement).
     */
    protected function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    /**
     * Fetch all records matching the query.
     */
    protected function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Fetch a single record matching the query.
     */
    protected function fetch($sql, $params = []) {
        return $this->query($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get the last inserted ID.
     */
    protected function lastInsertId() {
        return $this->db->lastInsertId();
    }
}
