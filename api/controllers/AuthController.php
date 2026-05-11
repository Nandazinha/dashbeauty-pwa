<?php
namespace Controllers;

use Models\User;
use Config\Auth;

class AuthController {
    private $user;

    public function __construct() {
        $this->user = new User();
    }

    public function register($request) {
        $existing = $this->user->findByEmail($request['email']);
        if ($existing) {
            return ['success' => false, 'message' => 'Email já cadastrado'];
        }

        $user_id = $this->user->create($request);

        if ($user_id) {
            $token = Auth::generateToken($user_id, $request['email'], $request['user_type']);
            return [
                'success' => true,
                'message' => 'Cadastro realizado com sucesso',
                'data' => [
                    'user_id' => $user_id,
                    'name' => $request['name'],
                    'email' => $request['email'],
                    'user_type' => $request['user_type'],
                    'token' => $token
                ]
            ];
        }

        return ['success' => false, 'message' => 'Erro ao cadastrar'];
    }

    public function login($request) {
        $user = $this->user->login($request['email'], $request['password']);

        if ($user) {
            $token = Auth::generateToken($user['id'], $user['email'], $user['user_type']);
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'data' => [
                    'user_id' => $user['id'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'photo' => $user['photo'],
                    'user_type' => $user['user_type'],
                    'token' => $token
                ]
            ];
        }

        return ['success' => false, 'message' => 'Email ou senha inválidos'];
    }

    public function me($user_id) {
        $user = $this->user->getById($user_id);
        if ($user) {
            return ['success' => true, 'data' => $user];
        }
        return ['success' => false, 'message' => 'Usuário não encontrado'];
    }
}
?>