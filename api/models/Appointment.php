<?php

namespace Models;

use Config\Database;

class Appointment
{
    private $conn;

    public function __construct()
    {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function create($data, $client_id)
    {
        $query = "INSERT INTO appointments 
                  (service_id, client_id, appointment_date, appointment_time, price, notes) 
                  VALUES (:service_id, :client_id, :date, :time, :price, :notes)";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([
            ':service_id' => $data['service_id'],
            ':client_id' => $client_id,
            ':date' => $data['appointment_date'],
            ':time' => $data['appointment_time'],
            ':price' => $data['price'],
            ':notes' => $data['notes']
        ]);

        return $this->conn->lastInsertId();
    }

    public function getByClient($client_id)
    {
        $query = "SELECT a.*, s.name as service_name, s.duration_minutes, 
                         b.business_name, b.id as business_id
                  FROM appointments a
                  JOIN services s ON a.service_id = s.id
                  JOIN businesses b ON s.business_id = b.id
                  WHERE a.client_id = :client_id 
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':client_id' => $client_id]);
        return $stmt->fetchAll();
    }

    public function getByBusiness($business_id)
    {
        $query = "SELECT a.*, s.name as service_name, s.duration_minutes,
                         u.name as client_name, u.phone as client_phone
                  FROM appointments a
                  JOIN services s ON a.service_id = s.id
                  JOIN users u ON a.client_id = u.id
                  WHERE s.business_id = :business_id
                  ORDER BY a.appointment_date DESC, a.appointment_time DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute([':business_id' => $business_id]);
        return $stmt->fetchAll();
    }

    public function cancel($id)
    {
        $query = "UPDATE appointments SET status = 'cancelled' WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([':id' => $id]);
        return true;
    }

    public function getAvailableTimes($business_id, $date, $service_id)
    {
        $day_of_week = date('w', strtotime($date));

        $hours_query = "SELECT open_time, close_time, is_closed 
                        FROM business_hours 
                        WHERE business_id = :business_id AND day_of_week = :day";

        $stmt = $this->conn->prepare($hours_query);
        $stmt->execute([':business_id' => $business_id, ':day' => $day_of_week]);
        $hours = $stmt->fetch();

        if (!$hours || $hours['is_closed']) {
            return [];
        }

        $service_query = "SELECT duration_minutes FROM services WHERE id = :service_id";
        $stmt = $this->conn->prepare($service_query);
        $stmt->execute([':service_id' => $service_id]);
        $service = $stmt->fetch();
        $duration = $service['duration_minutes'];

        $booked_query = "SELECT appointment_time FROM appointments 
                         WHERE service_id IN (SELECT id FROM services WHERE business_id = :business_id)
                         AND appointment_date = :date 
                         AND status != 'cancelled'";

        $stmt = $this->conn->prepare($booked_query);
        $stmt->execute([':business_id' => $business_id, ':date' => $date]);
        $booked = $stmt->fetchAll();
        $booked_times = array_column($booked, 'appointment_time');

        $open = strtotime($hours['open_time']);
        $close = strtotime($hours['close_time']);
        $available = [];

        for ($time = $open; $time + ($duration * 60) <= $close; $time += (30 * 60)) {
            $time_str = date('H:i:s', $time);
            if (!in_array($time_str, $booked_times)) {
                $available[] = date('H:i', $time);
            }
        }

        return $available;
    }
}
