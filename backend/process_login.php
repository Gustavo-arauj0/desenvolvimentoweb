<?php
// process_login.php - Processa formulário de login

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Auth.php';
require_once 'repositories/UserRepository.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email'] ?? '');
    $password = sanitizeInput($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        if (isAjaxRequest()) {
            jsonResponse(false, 'Email e senha são obrigatórios');
        } else {
            header('Location: login.html?error=campos_obrigatorios');
        }
        exit();
    }
    
    try {
        $userRepository = new UserRepository();
        $auth = new Auth($userRepository);
        
        $result = $auth->login($email, $password);
        
        if (isAjaxRequest()) {
            echo json_encode($result);
        } else {
            if ($result['success']) {
                $user = $result['user'];
                if ($user['tipo_usuario'] === 'admin') {
                    header('Location: admin-dashboard.html');
                } else {
                    header('Location: dashboard.html');
                }
            } else {
                header('Location: login.html?error=' . urlencode($result['message']));
            }
        }
        
    } catch (Exception $e) {
        logError('Erro no login: ' . $e->getMessage());
        
        if (isAjaxRequest()) {
            jsonResponse(false, 'Erro interno do servidor');
        } else {
            header('Location: login.html?error=erro_interno');
        }
    }
} else {
    header('Location: login.html');
}
exit();
?>