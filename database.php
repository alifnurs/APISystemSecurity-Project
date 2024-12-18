<?php
class Database {
    private $conn;
    
    public function __construct($config) {
        try {
            $this->conn = new PDO(
                "mysql:host={$config['host']};dbname={$config['database']}",
                $config['username'],
                $config['password']
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
}