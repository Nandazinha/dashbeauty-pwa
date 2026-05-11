<?php

namespace Controllers;

use Models\Service;

class ServiceController
{
    private $service;

    public function __construct()
    {
        $this->service = new Service();
    }

    public function getByBusiness($business_id)
    {
        $services = $this->service->getByBusiness($business_id);
        return ['success' => true, 'data' => $services];
    }

    public function getById($id)
    {
        $service = $this->service->getById($id);
        if ($service) {
            return ['success' => true, 'data' => $service];
        }
        return ['success' => false, 'message' => 'Serviço não encontrado'];
    }

    public function create($data, $business_id)
    {
        $service_id = $this->service->create($data, $business_id);
        if ($service_id) {
            return ['success' => true, 'service_id' => $service_id];
        }
        return ['success' => false, 'message' => 'Erro ao criar serviço'];
    }

    public function update($id, $data)
    {
        if ($this->service->update($id, $data)) {
            return ['success' => true, 'message' => 'Serviço atualizado'];
        }
        return ['success' => false, 'message' => 'Erro ao atualizar serviço'];
    }

    public function delete($id)
    {
        if ($this->service->delete($id)) {
            return ['success' => true, 'message' => 'Serviço removido'];
        }
        return ['success' => false, 'message' => 'Erro ao remover serviço'];
    }
}
