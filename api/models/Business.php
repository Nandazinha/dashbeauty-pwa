<?php

namespace Models;

use Config\Database;

class Business
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function getAll()
    {
        $query = "SELECT b.*, u.name as owner_name,
                         (SELECT AVG(rating) FROM reviews r 
                          JOIN appointments a ON r.appointment_id = a.id 
                          JOIN services s ON a.service_id = s.id 
                          WHERE s.business_id = b.id) as avg_rating
                  FROM businesses b
                  JOIN users u ON b.user_id = u.id
                  ORDER BY b.is_featured DESC, b.rating DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id)
    {
        $query = "SELECT b.*, u.name as owner_name, u.email, u.phone
                  FROM businesses b
                  JOIN users u ON b.user_id = u.id
                  WHERE b.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getServices($business_id)
    {
        $query = "SELECT * FROM services WHERE business_id = :business_id AND is_active = 1 ORDER BY name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':business_id' => $business_id]);
        return $stmt->fetchAll();
    }

    public function getHours($business_id)
    {
        $query = "SELECT * FROM business_hours WHERE business_id = :business_id ORDER BY day_of_week";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':business_id' => $business_id]);
        return $stmt->fetchAll();
    }

    public function getReviews($business_id)
    {
        $query = "SELECT r.*, u.name as client_name 
                 FROM reviews r
                 JOIN appointments a ON r.appointment_id = a.id
                 JOIN users u ON a.client_id = u.id
                 WHERE a.service_id IN (SELECT id FROM services WHERE business_id = :business_id)
                 ORDER BY r.created_at DESC LIMIT 10";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':business_id' => $business_id]);
        return $stmt->fetchAll();
    }

    public function search($query, $lat = null, $lng = null)
    {
        $sql = "SELECT b.*, u.name as owner_name,
                       (SELECT AVG(rating) FROM reviews r 
                        JOIN appointments a ON r.appointment_id = a.id 
                        JOIN services s ON a.service_id = s.id 
                        WHERE s.business_id = b.id) as avg_rating
                FROM businesses b
                JOIN users u ON b.user_id = u.id
                WHERE MATCH(b.business_name, b.description) AGAINST(:query IN NATURAL LANGUAGE MODE)
                ORDER BY b.is_featured DESC, avg_rating DESC";

        $stmt = $this->conn->prepare($sql);
        $stmt->execute([':query' => $query]);
        return $stmt->fetchAll();
    }

    public function create($data, $user_id)
    {
        $query = "INSERT INTO businesses 
                  (user_id, business_name, description, address, latitude, longitude, cpf_cnpj) 
                  VALUES (:user_id, :name, :description, :address, :lat, :lng, :cpf)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':user_id' => $user_id,
            ':name' => $data['business_name'],
            ':description' => $data['description'],
            ':address' => $data['address'],
            ':lat' => $data['latitude'],
            ':lng' => $data['longitude'],
            ':cpf' => $data['cpf_cnpj']
        ]);

        return $this->conn->lastInsertId();
    }
}
