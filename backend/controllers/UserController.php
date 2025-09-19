<?php
// controllers/UserController.php - Controlador de usuários

class UserController {
    private $userRepository;
    private $auth;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->auth = new Auth($this->userRepository);
    }
    
    /**
     * Obtém perfil do usuário logado
     */
    public function getProfile() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        $user = $this->auth->getCurrentUser();
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'user' => $user->toArray()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
        }
    }
    
    /**
     * Atualiza perfil do usuário
     */
    public function updateProfile() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        $userId = $_SESSION['user_id'];
        
        // Validações
        $errors = $this->validateProfileUpdate($input);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            return;
        }
        
        $userData = [
            'nome' => $input['name'],
            'telefone' => $input['phone'],
            'localizacao' => $input['location']
        ];
        
        // Se nova senha foi fornecida
        if (!empty($input['newPassword'])) {
            $userData['senha'] = $input['newPassword'];
        }
        
        $user = $this->userRepository->update($userId, $userData);
        
        if ($user) {
            echo json_encode([
                'success' => true,
                'message' => 'Perfil atualizado com sucesso',
                'user' => $user->toArray()
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil']);
        }
    }
    
    /**
     * Exclui conta do usuário
     */
    public function deleteAccount() {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        
        if ($this->userRepository->delete($userId)) {
            $this->auth->logout();
            echo json_encode([
                'success' => true,
                'message' => 'Conta excluída com sucesso'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir conta']);
        }
    }
    
    /**
     * Obtém estatísticas do usuário
     */
    public function getStats() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        $userId = $_SESSION['user_id'];
        $itemRepository = new ItemRepository();
        
        $stats = [
            'total_itens' => $itemRepository->countByUser($userId),
            'itens_disponiveis' => $itemRepository->countByUser($userId, 'disponivel'),
            'itens_trocados' => $itemRepository->countByUser($userId, 'trocado')
        ];
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
    }
    
    /**
     * Valida atualização de perfil
     */
    private function validateProfileUpdate($data) {
        $errors = [];
        
        if (empty($data['name'])) {
            $errors[] = 'Nome é obrigatório';
        }
        
        if (empty($data['phone'])) {
            $errors[] = 'Telefone é obrigatório';
        }
        
        if (empty($data['location'])) {
            $errors[] = 'Localização é obrigatória';
        }
        
        // Se nova senha foi fornecida, validar
        if (!empty($data['newPassword'])) {
            if (strlen($data['newPassword']) < 6) {
                $errors[] = 'Nova senha deve ter pelo menos 6 caracteres';
            }
            
            if (empty($data['confirmNewPassword']) || $data['newPassword'] !== $data['confirmNewPassword']) {
                $errors[] = 'Confirmação da nova senha não confere';
            }
        }
        
        return $errors;
    }
}
?>