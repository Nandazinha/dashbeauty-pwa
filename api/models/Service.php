<?php

namespace Models;

use Config\Database;

class Service
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getByBusiness($business_id)
    {
        $query = "SELECT * FROM services 
                  WHERE business_id = :business_id AND is_active = 1 
                  ORDER BY name";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':business_id' => $business_id]);
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $query = "SELECT * FROM services WHERE id = :id AND is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function create($data, $business_id)
    {
        $query = "INSERT INTO services 
                  (business_id, name, description, price, duration_minutes, category) 
                  VALUES (:business_id, :name, :description, :price, :duration, :category)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':business_id' => $business_id,
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':duration' => $data['duration_minutes'],
            ':category' => $data['category']
        ]);

        return $this->conn->lastInsertId();
    }

    public function update($id, $data)
    {
        $query = "UPDATE services SET 
                  name = :name, 
                  description = :description, 
                  price = :price, 
                  duration_minutes = :duration,
                  category = :category
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':name' => $data['name'],
            ':description' => $data['description'],
            ':price' => $data['price'],
            ':duration' => $data['duration_minutes'],
            ':category' => $data['category'],
            ':id' => $id
        ]);

        return true;
    }

    public function delete($id)
    {
        $query = "UPDATE services SET is_active = 0 WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return true;
    }
}
