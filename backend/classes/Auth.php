<?php
// classes/Auth.php - Classe para autenticação

class Auth {
    private $userRepository;
    
    public function __construct(UserRepository $userRepository) {
        $this->userRepository = $userRepository;
    }
    
    /**
     * Realiza login do usuário
     */
    public function login($email, $senha) {
        try {
            // Busca usuário por email
            $user = $this->userRepository->findByEmail($email);
            
            if (!$user) {
                return ['success' => false, 'message' => 'Email não encontrado'];
            }
            
            if (!$user->isAtivo()) {
                return ['success' => false, 'message' => 'Conta inativa'];
            }
            
            // Verifica senha (usando MD5 para compatibilidade)
            if (md5($senha) !== $user->getSenha()) {
                return ['success' => false, 'message' => 'Senha incorreta'];
            }
            
            // Inicia sessão
            $this->startSession($user);
            
            return [
                'success' => true, 
                'user' => $user->toArray(),
                'message' => 'Login realizado com sucesso'
            ];
            
        } catch (Exception $e) {
            error_log("Erro no login: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erro interno do servidor'];
        }
    }
    
    /**
     * Inicia sessão do usuário
     */
    private function startSession($user) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION['user_id'] = $user->getId();
        $_SESSION['user_email'] = $user->getEmail();
        $_SESSION['user_name'] = $user->getNome();
        $_SESSION['user_type'] = $user->getTipoUsuario();
        $_SESSION['logged_in'] = true;
    }
    
    /**
     * Verifica se usuário está logado
     */
    public function isLoggedIn() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Retorna usuário logado
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $this->userRepository->findById($_SESSION['user_id']);
    }
    
    /**
     * Realiza logout
     */
    public function logout() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $_SESSION = [];
        session_destroy();
        
        return ['success' => true, 'message' => 'Logout realizado com sucesso'];
    }
    
    /**
     * Verifica se usuário é admin
     */
    public function isAdmin() {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        return $_SESSION['user_type'] === 'admin';
    }
}