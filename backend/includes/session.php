<?php
// includes/session.php - Gerenciamento de sessão

/**
 * Inicia sessão se não estiver ativa
 */
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        // Configurações de segurança da sessão
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 0); // Alterar para 1 em HTTPS
        
        session_start();
    }
}

/**
 * Verifica se usuário está logado
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Verifica se usuário é admin
 */
function isAdmin() {
    startSession();
    return isLoggedIn() && isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

/**
 * Obtém ID do usuário logado
 */
function getCurrentUserId() {
    startSession();
    return isLoggedIn() ? $_SESSION['user_id'] : null;
}

/**
 * Obtém dados básicos do usuário logado
 */
function getCurrentUserData() {
    startSession();
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'],
        'email' => $_SESSION['user_email'],
        'type' => $_SESSION['user_type']
    ];
}

/**
 * Require login - redireciona se não logado
 */
function requireLogin($redirectUrl = 'login.html') {
    if (!isLoggedIn()) {
        if (isAjaxRequest()) {
            jsonResponse(false, 'Login necessário');
        } else {
            header("Location: $redirectUrl");
        }
        exit();
    }
}

/**
 * Require admin - redireciona se não for admin
 */
function requireAdmin($redirectUrl = 'index.html') {
    if (!isAdmin()) {
        if (isAjaxRequest()) {
            jsonResponse(false, 'Acesso negado');
        } else {
            header("Location: $redirectUrl");
        }
        exit();
    }
}
?>