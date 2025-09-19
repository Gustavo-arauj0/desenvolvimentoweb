<?php
// process_register.php - Processa formulário de cadastro

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'repositories/UserRepository.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'password' => $_POST['password'] ?? '',
        'confirmPassword' => $_POST['confirmPassword'] ?? '',
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'location' => sanitizeInput($_POST['location'] ?? '')
    ];
    
    // Validações
    $errors = [];
    
    if (empty($data['name'])) {
        $errors[] = 'Nome é obrigatório';
    }
    
    if (empty($data['email']) || !isValidEmail($data['email'])) {
        $errors[] = 'Email válido é obrigatório';
    }
    
    if (empty($data['password']) || strlen($data['password']) < 6) {
        $errors[] = 'Senha deve ter pelo menos 6 caracteres';
    }
    
    if ($data['password'] !== $data['confirmPassword']) {
        $errors[] = 'Confirmação de senha não confere';
    }
    
    if (empty($data['phone'])) {
        $errors[] = 'Telefone é obrigatório';
    }
    
    if (empty($data['location'])) {
        $errors[] = 'Localização é obrigatória';
    }
    
    if (!empty($errors)) {
        if (isAjaxRequest()) {
            jsonResponse(false, implode(', ', $errors));
        } else {
            header('Location: cadastro.html?error=' . urlencode(implode(', ', $errors)));
        }
        exit();
    }
    
    try {
        $userRepository = new UserRepository();
        
        // Verificar se email já existe
        if ($userRepository->emailExists($data['email'])) {
            if (isAjaxRequest()) {
                jsonResponse(false, 'Email já está em uso');
            } else {
                header('Location: cadastro.html?error=email_existe');
            }
            exit();
        }
        
        // Criar usuário
        $userData = [
            'nome' => $data['name'],
            'email' => $data['email'],
            'senha' => $data['password'],
            'telefone' => $data['phone'],
            'localizacao' => $data['location']
        ];
        
        $user = $userRepository->create($userData);
        
        if ($user) {
            if (isAjaxRequest()) {
                jsonResponse(true, 'Cadastro realizado com sucesso');
            } else {
                header('Location: login.html?success=cadastro_realizado');
            }
        } else {
            if (isAjaxRequest()) {
                jsonResponse(false, 'Erro ao criar usuário');
            } else {
                header('Location: cadastro.html?error=erro_criar_usuario');
            }
        }
        
    } catch (Exception $e) {
        logError('Erro no cadastro: ' . $e->getMessage());
        
        if (isAjaxRequest()) {
            jsonResponse(false, 'Erro interno do servidor');
        } else {
            header('Location: cadastro.html?error=erro_interno');
        }
    }
} else {
    header('Location: cadastro.html');
}
exit();
?>