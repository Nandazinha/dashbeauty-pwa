<?php

namespace Config;

use PDO;
use PDOException;

class Database
{
    private $host = 'localhost';
    private $db_name = 'dashbeauty';
    private $username = 'root';
    private $password = '260908';
    private $conn;

    public function getConnection()
    {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            die(json_encode(['success' => false, 'message' => 'Erro de conexão: ' . $e->getMessage()]));
        }
        return $this->conn;
    }
}
