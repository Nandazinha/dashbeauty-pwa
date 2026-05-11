<?php

namespace Models;

use Config\Database;

class User
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data)
    {
        $query = "INSERT INTO users (email, password, name, phone, user_type) 
                  VALUES (:email, MD5(:password), :name, :phone, :user_type)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':name' => $data['name'],
            ':phone' => $data['phone'],
            ':user_type' => $data['user_type']
        ]);

        return $this->conn->lastInsertId();
    }

    public function login($email, $password)
    {
        $query = "SELECT * FROM users WHERE email = :email AND password = MD5(:password)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email, ':password' => $password]);
        return $stmt->fetch();
    }

    public function findByEmail($email)
    {
        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':email' => $email]);
        return $stmt->fetch();
    }

    public function getById($id)
    {
        $query = "SELECT id, email, name, phone, photo, user_type FROM users WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }
}
