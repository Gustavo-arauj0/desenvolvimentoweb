<?php
// includes/functions.php - Funções utilitárias

/**
 * Sanitiza entrada de dados
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida URL
 */
function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Gera hash de senha
 */
function hashPassword($password) {
    // Para compatibilidade com dados existentes, usando MD5
    // Em produção, recomenda-se usar password_hash()
    return md5($password);
}

/**
 * Verifica senha
 */
function verifyPassword($password, $hash) {
    return md5($password) === $hash;
}

/**
 * Resposta JSON padronizada
 */
function jsonResponse($success, $message = '', $data = []) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
}

/**
 * Log de erros personalizado
 */
function logError($message, $context = []) {
    $logMessage = date('[Y-m-d H:i:s] ') . $message;
    if (!empty($context)) {
        $logMessage .= ' - Context: ' . json_encode($context);
    }
    error_log($logMessage);
}

/**
 * Verifica se requisição é AJAX
 */
function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

/**
 * Redireciona se não for AJAX
 */
function redirectIfNotAjax($url) {
    if (!isAjaxRequest()) {
        header("Location: $url");
        exit();
    }
}

/**
 * Formata data brasileira
 */
function formatDateBR($date) {
    return date('d/m/Y', strtotime($date));
}

/**
 * Formata data e hora brasileira
 */
function formatDateTimeBR($datetime) {
    return date('d/m/Y H:i', strtotime($datetime));
}

/**
 * Limpa telefone para formato padrão
 */
function cleanPhone($phone) {
    return preg_replace('/[^0-9]/', '', $phone);
}

/**
 * Formata telefone brasileiro
 */
function formatPhone($phone) {
    $clean = cleanPhone($phone);
    if (strlen($clean) == 11) {
        return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 5) . '-' . substr($clean, 7);
    } elseif (strlen($clean) == 10) {
        return '(' . substr($clean, 0, 2) . ') ' . substr($clean, 2, 4) . '-' . substr($clean, 6);
    }
    return $phone;
}

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica token CSRF
 */
function verifyCSRFToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>