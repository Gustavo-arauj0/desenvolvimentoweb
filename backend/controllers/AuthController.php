<?php
// controllers/AuthController.php - Controlador de autenticação

class AuthController {
    private $auth;
    private $userRepository;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->auth = new Auth($this->userRepository);
    }
    
    /**
     * Processa login
     */
    public function login() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $email = $input['email'] ?? '';
        $senha = $input['senha'] ?? '';
        
        // Validações
        if (empty($email) || empty($senha)) {
            echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
            return;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email inválido']);
            return;
        }
        
        $result = $this->auth->login($email, $senha);
        echo json_encode($result);
    }
    
    /**
     * Processa cadastro
     */
    public function register() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Validações
        $errors = $this->validateRegistration($input);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            return;
        }
        
        // Verifica se email já existe
        if ($this->userRepository->emailExists($input['email'])) {
            echo json_encode(['success' => false, 'message' => 'Email já está em uso']);
            return;
        }
        
        // Cria usuário
        $userData = [
            'nome' => $input['name'],
            'email' => $input['email'],
            'senha' => $input['password'],
            'telefone' => $input['phone'],
            'localizacao' => $input['location']
        ];
        
        $user = $this->userRepository->create($userData);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'message' => 'Cadastro realizado com sucesso',
                'user' => $user->toArray()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário']);
        }
    }
    
    /**
     * Processa logout
     */
    public function logout() {
        header('Content-Type: application/json');
        
        $result = $this->auth->logout();
        echo json_encode($result);
    }
    
    /**
     * Verifica status de autenticação
     */
    public function checkAuth() {
        header('Content-Type: application/json');
        
        if ($this->auth->isLoggedIn()) {
            $user = $this->auth->getCurrentUser();
            echo json_encode([
                'success' => true,
                'logged_in' => true,
                'user' => $user ? $user->toArray() : null
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'logged_in' => false
            ]);
        }
    }
    
    /**
     * Valida dados de registro
     */
    private function validateRegistration($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email válido é obrigatório';
        }
        
        if (empty($data['password']) || strlen($data['password']) < 6) {
            $errors[] = 'Senha deve ter pelo menos 6 caracteres';
        }
        
        if (empty($data['confirmPassword']) || $data['password'] !== $data['confirmPassword']) {
            $errors[] = 'Confirmação de senha não confere';
        }
        
        if (empty($data['phone'])) {
            $errors[] = 'Telefone é obrigatório';
        }
        
        if (empty($data['location'])) {
            $errors[] = 'Localização é obrigatória';
        }
        
        return $errors;
    }
}