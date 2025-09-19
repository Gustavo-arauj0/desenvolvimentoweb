<?php
// logout.php - Processa logout

require_once 'includes/session.php';

startSession();

// Limpar sessão
$_SESSION = [];
session_destroy();

// Redirecionar
if (isAjaxRequest()) {
    jsonResponse(true, 'Logout realizado com sucesso');
} else {
    header('Location: index.html?logout=success');
}
exit();
?>