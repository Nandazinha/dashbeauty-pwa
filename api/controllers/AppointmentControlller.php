<?php

namespace Controllers;

use Models\Appointment;
use Models\Service;

class AppointmentController
{
    private $appointment;
    private $service;

    public function __construct()
    {
        $this->appointment = new Appointment();
        $this->service = new Service();
    }

    public function create($data, $client_id)
    {
        $service = $this->service->getById($data['service_id']);
        if (!$service) {
            return ['success' => false, 'message' => 'Serviço não encontrado'];
        }

        $data['price'] = $service['price'];
        $appointment_id = $this->appointment->create($data, $client_id);

        if ($appointment_id) {
            return ['success' => true, 'appointment_id' => $appointment_id];
        }
        return ['success' => false, 'message' => 'Erro ao criar agendamento'];
    }

    public function getByClient($client_id)
    {
        $appointments = $this->appointment->getByClient($client_id);
        return ['success' => true, 'data' => $appointments];
    }

    public function getByBusiness($business_id)
    {
        $appointments = $this->appointment->getByBusiness($business_id);
        return ['success' => true, 'data' => $appointments];
    }

    public function cancel($id)
    {
        if ($this->appointment->cancel($id)) {
            return ['success' => true, 'message' => 'Agendamento cancelado'];
        }
        return ['success' => false, 'message' => 'Erro ao cancelar agendamento'];
    }

    public function getAvailableTimes($business_id, $date, $service_id)
    {
        $times = $this->appointment->getAvailableTimes($business_id, $date, $service_id);
        return ['success' => true, 'data' => $times];
    }
}
