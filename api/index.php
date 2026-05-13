<?php
// ============================================
// DASHBEAUTY API - VERSÃO CORRIGIDA
// ============================================

// Configuração de CORS - ESSENCIAL PARA O APP FUNCIONAR
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");

// Responder requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// CONEXÃO COM O BANCO DE DADOS
// ============================================
$host = 'localhost';
$dbname = 'dashbeauty';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro de conexão com o banco: ' . $e->getMessage()]);
    exit();
}

// ============================================
// FUNÇÕES JWT
// ============================================
function generateToken($user_id, $email, $user_type)
{
    $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
    $payload = base64_encode(json_encode([
        'user_id' => $user_id,
        'email' => $email,
        'user_type' => $user_type,
        'exp' => time() + 86400
    ]));
    $signature = hash_hmac('sha256', "$header.$payload", 'dashbeauty_secret_2024', true);
    $signature = base64_encode($signature);
    return "$header.$payload.$signature";
}

function validateToken()
{
    $headers = getallheaders();
    $auth = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    $token = str_replace('Bearer ', '', $auth);

    $parts = explode('.', $token);
    if (count($parts) != 3) return null;

    $signature = hash_hmac('sha256', "$parts[0].$parts[1]", 'dashbeauty_secret_2024', true);
    $signature = base64_encode($signature);

    if ($signature !== $parts[2]) return null;

    $payload = json_decode(base64_decode($parts[1]), true);
    if ($payload['exp'] < time()) return null;

    return $payload;
}

// ============================================
// ROTEAMENTO
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Extrair o caminho corretamente
$path = strtok($request_uri, '?');
$path = str_replace('/api', '', $path);
$path = str_replace('/dashbeauty/api', '', $path);
$path = rtrim($path, '/');
$segments = explode('/', trim($path, '/'));
$resource = $segments[0] ?? '';
$id = $segments[1] ?? null;

// Pegar dados do body
$input = json_decode(file_get_contents('php://input'), true) ?? $_POST;

try {
    // ============================================
    // ROTA DE TESTE
    // ============================================
    if ($resource === '' || $resource === 'test') {
        echo json_encode([
            'success' => true,
            'message' => 'API DashBeauty está funcionando!',
            'timestamp' => date('Y-m-d H:i:s'),
            'server' => $_SERVER['SERVER_NAME']
        ]);
        exit();
    }

    // ============================================
    // ROTAS DE AUTENTICAÇÃO
    // ============================================
    if ($resource === 'auth') {
        // LOGIN
        if ($method === 'POST' && $id === 'login') {
            if (!isset($input['email']) || !isset($input['password'])) {
                echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
                exit();
            }

            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND password = MD5(?)");
            $stmt->execute([$input['email'], $input['password']]);
            $user = $stmt->fetch();

            if ($user) {
                $token = generateToken($user['id'], $user['email'], $user['user_type']);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'user_id' => $user['id'],
                        'name' => $user['name'],
                        'email' => $user['email'],
                        'phone' => $user['phone'] ?? '',
                        'user_type' => $user['user_type'],
                        'token' => $token
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Email ou senha inválidos']);
            }
            exit();
        }

        // REGISTRO
        if ($method === 'POST' && $id === 'register') {
            if (!isset($input['email']) || !isset($input['password']) || !isset($input['name'])) {
                echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
                exit();
            }

            // Verificar se email já existe
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'Email já cadastrado']);
                exit();
            }

            // Inserir usuário
            $stmt = $pdo->prepare("INSERT INTO users (email, password, name, phone, user_type) VALUES (?, MD5(?), ?, ?, ?)");
            $result = $stmt->execute([
                $input['email'],
                $input['password'],
                $input['name'],
                $input['phone'] ?? '',
                $input['user_type'] ?? 'client'
            ]);

            if ($result) {
                $user_id = $pdo->lastInsertId();

                // Se for empresa, criar registro na tabela businesses
                if (($input['user_type'] ?? 'client') === 'business') {
                    $business_name = $input['business_name'] ?? $input['name'];
                    $stmt = $pdo->prepare("INSERT INTO businesses (user_id, business_name) VALUES (?, ?)");
                    $stmt->execute([$user_id, $business_name]);
                }

                $token = generateToken($user_id, $input['email'], $input['user_type'] ?? 'client');
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'user_id' => $user_id,
                        'name' => $input['name'],
                        'email' => $input['email'],
                        'user_type' => $input['user_type'] ?? 'client',
                        'token' => $token
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar usuário']);
            }
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Rota auth não encontrada']);
        exit();
    }

    // ============================================
    // ROTAS DE ESTABELECIMENTOS
    // ============================================
    if ($resource === 'businesses') {
        if ($method === 'GET') {
            $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
            $stmt = $pdo->prepare("
                SELECT b.*, 
                       (SELECT AVG(rating) FROM reviews r 
                        JOIN appointments a ON r.appointment_id = a.id 
                        JOIN services s ON a.service_id = s.id 
                        WHERE s.business_id = b.id) as avg_rating
                FROM businesses b
                WHERE b.business_name LIKE ? OR b.description LIKE ?
                ORDER BY b.is_featured DESC
            ");
            $stmt->execute([$search, $search]);
            $businesses = $stmt->fetchAll();

            echo json_encode(['success' => true, 'data' => $businesses]);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit();
    }

    // ============================================
    // ROTAS DE FAVORITOS
    // ============================================
    if ($resource === 'favorites') {
        $userData = validateToken();
        if (!$userData) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            exit();
        }

        if ($method === 'GET') {
            $stmt = $pdo->prepare("SELECT business_id FROM favorites WHERE user_id = ?");
            $stmt->execute([$userData['user_id']]);
            $favorites = $stmt->fetchAll();
            echo json_encode(['success' => true, 'data' => $favorites]);
            exit();
        }

        if ($method === 'POST') {
            $business_id = $input['business_id'] ?? null;
            if (!$business_id) {
                echo json_encode(['success' => false, 'message' => 'ID do estabelecimento é obrigatório']);
                exit();
            }
            $stmt = $pdo->prepare("INSERT IGNORE INTO favorites (user_id, business_id) VALUES (?, ?)");
            $stmt->execute([$userData['user_id'], $business_id]);
            echo json_encode(['success' => true, 'message' => 'Adicionado aos favoritos']);
            exit();
        }

        if ($method === 'DELETE' && $id) {
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND business_id = ?");
            $stmt->execute([$userData['user_id'], $id]);
            echo json_encode(['success' => true, 'message' => 'Removido dos favoritos']);
            exit();
        }

        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        exit();
    }

    // ============================================
    // ROTA PADRÃO
    // ============================================
    echo json_encode([
        'success' => true,
        'message' => 'API DashBeauty funcionando!',
        'version' => '1.0.0',
        'endpoints' => [
            'GET /api/test' => 'Testar API',
            'POST /api/auth/login' => 'Login',
            'POST /api/auth/register' => 'Registro',
            'GET /api/businesses' => 'Listar estabelecimentos',
            'GET /api/favorites' => 'Listar favoritos',
            'POST /api/favorites' => 'Adicionar favorito',
            'DELETE /api/favorites/{id}' => 'Remover favorito'
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
