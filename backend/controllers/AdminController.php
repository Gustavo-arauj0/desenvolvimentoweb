<?php
// controllers/AdminController.php - Controlador administrativo

class AdminController {
    private $userRepository;
    private $itemRepository;
    private $auth;
    
    public function __construct() {
        $this->userRepository = new UserRepository();
        $this->itemRepository = new ItemRepository();
        $this->auth = new Auth($this->userRepository);
    }
    
    /**
     * Verifica se usuário é admin
     */
    private function checkAdminAuth() {
        if (!$this->auth->isLoggedIn() || !$this->auth->isAdmin()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Acesso negado']);
            return false;
        }
        return true;
    }
    
    /**
     * Dashboard administrativo
     */
    public function getDashboardStats() {
        header('Content-Type: application/json');
        
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        try {
            $stats = [
                'total_usuarios' => $this->userRepository->count(),
                'total_itens' => $this->getItemStats()['total_itens'],
                'itens_disponiveis' => $this->getItemStats()['itens_disponiveis'],
                'itens_trocados' => $this->getItemStats()['itens_trocados'],
                'usuarios_ativos' => $this->getActiveUsersCount(),
                'novos_usuarios_mes' => $this->getNewUsersThisMonth(),
                'media_itens_usuario' => $this->getAverageItemsPerUser(),
                'estatisticas_categorias' => $this->getCategoryStats()
            ];
            
            echo json_encode([
                'success' => true,
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            error_log("Erro no dashboard admin: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
        }
    }
    
    /**
     * Lista todos os usuários
     */
    public function getUsers() {
        header('Content-Type: application/json');
        
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        $search = $_GET['search'] ?? '';
        $orderBy = $_GET['orderBy'] ?? 'nome';
        $order = $_GET['order'] ?? 'ASC';
        
        $users = $this->userRepository->findAll($search, $orderBy, $order);
        
        // Adiciona estatísticas de itens para cada usuário
        $usersWithStats = [];
        foreach ($users as $user) {
            $userArray = $user->toArray();
            $userArray['total_itens'] = $this->itemRepository->countByUser($user->getId());
            $userArray['itens_disponiveis'] = $this->itemRepository->countByUser($user->getId(), 'disponivel');
            $userArray['itens_trocados'] = $this->itemRepository->countByUser($user->getId(), 'trocado');
            $usersWithStats[] = $userArray;
        }
        
        echo json_encode([
            'success' => true,
            'users' => $usersWithStats
        ]);
    }
    
    /**
     * Exclui usuário (admin)
     */
    public function deleteUser($userId) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        // Não permitir exclusão do próprio admin
        if ($userId == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Não é possível excluir sua própria conta']);
            return;
        }
        
        // Verificar se usuário existe
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            return;
        }
        
        if ($this->userRepository->delete($userId)) {
            echo json_encode([
                'success' => true,
                'message' => 'Usuário excluído com sucesso'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir usuário']);
        }
    }
    
    /**
     * Detalhes de um usuário
     */
    public function getUserDetails($userId) {
        header('Content-Type: application/json');
        
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        $user = $this->userRepository->findById($userId);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            return;
        }
        
        // Buscar itens do usuário
        $userItems = $this->itemRepository->findAll(['usuario_id' => $userId]);
        
        $userDetails = $user->toArray();
        $userDetails['itens'] = $userItems;
        $userDetails['stats'] = [
            'total_itens' => $this->itemRepository->countByUser($userId),
            'itens_disponiveis' => $this->itemRepository->countByUser($userId, 'disponivel'),
            'itens_trocados' => $this->itemRepository->countByUser($userId, 'trocado')
        ];
        
        echo json_encode([
            'success' => true,
            'user' => $userDetails
        ]);
    }
    
    /**
     * Gera relatório do sistema
     */
    public function generateReport() {
        header('Content-Type: application/json');
        
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        try {
            $report = [
                'data_geracao' => date('Y-m-d H:i:s'),
                'resumo' => [
                    'total_usuarios' => $this->userRepository->count(),
                    'usuarios_ativos' => $this->getActiveUsersCount(),
                    'total_itens' => $this->getItemStats()['total_itens'],
                    'itens_disponiveis' => $this->getItemStats()['itens_disponiveis'],
                    'itens_trocados' => $this->getItemStats()['itens_trocados']
                ],
                'estatisticas_categorias' => $this->getCategoryStats(),
                'usuarios_mais_ativos' => $this->getTopUsers(),
                'crescimento_mensal' => $this->getMonthlyGrowth()
            ];
            
            echo json_encode([
                'success' => true,
                'report' => $report
            ]);
            
        } catch (Exception $e) {
            error_log("Erro ao gerar relatório: " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Erro ao gerar relatório']);
        }
    }
    
    /**
     * Exporta dados dos usuários
     */
    public function exportUsers() {
        if (!$this->checkAdminAuth()) {
            return;
        }
        
        $users = $this->userRepository->findAll();
        
        // Configurar headers para download CSV
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=usuarios_ecoswap_' . date('Y-m-d') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Cabeçalho do CSV
        fputcsv($output, [
            'ID', 'Nome', 'Email', 'Telefone', 'Localização', 
            'Tipo', 'Ativo', 'Total Itens', 'Data Cadastro'
        ]);
        
        // Dados dos usuários
        foreach ($users as $user) {
            $totalItens = $this->itemRepository->countByUser($user->getId());
            
            fputcsv($output, [
                $user->getId(),
                $user->getNome(),
                $user->getEmail(),
                $user->getTelefone(),
                $user->getLocalizacao(),
                $user->getTipoUsuario(),
                $user->isAtivo() ? 'Sim' : 'Não',
                $totalItens,
                $user->getDataCadastro()
            ]);
        }
        
        fclose($output);
    }
    
    /**
     * Métodos auxiliares para estatísticas
     */
    
    private function getItemStats() {
        return $this->itemRepository->getStats();
    }
    
    private function getActiveUsersCount() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE ativo = 1");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getNewUsersThisMonth() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT COUNT(*) FROM usuarios 
                WHERE MONTH(data_cadastro) = MONTH(CURRENT_DATE()) 
                AND YEAR(data_cadastro) = YEAR(CURRENT_DATE())
            ");
            return $stmt->fetchColumn();
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getAverageItemsPerUser() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT AVG(item_count) as media FROM (
                    SELECT COUNT(*) as item_count 
                    FROM itens 
                    GROUP BY usuario_id
                ) as user_items
            ");
            $result = $stmt->fetchColumn();
            return round($result, 1);
        } catch (Exception $e) {
            return 0;
        }
    }
    
    private function getCategoryStats() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT c.nome, COUNT(i.id) as total,
                       COUNT(CASE WHEN i.status = 'disponivel' THEN 1 END) as disponiveis,
                       COUNT(CASE WHEN i.status = 'trocado' THEN 1 END) as trocados
                FROM categorias c 
                LEFT JOIN itens i ON c.id = i.categoria_id 
                GROUP BY c.id, c.nome 
                ORDER BY total DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getTopUsers() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT u.nome, u.email, COUNT(i.id) as total_itens,
                       COUNT(CASE WHEN i.status = 'trocado' THEN 1 END) as trocas_realizadas
                FROM usuarios u 
                LEFT JOIN itens i ON u.id = i.usuario_id 
                WHERE u.tipo_usuario = 'usuario'
                GROUP BY u.id, u.nome, u.email 
                ORDER BY total_itens DESC 
                LIMIT 10
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function getMonthlyGrowth() {
        try {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->query("
                SELECT 
                    DATE_FORMAT(data_cadastro, '%Y-%m') as mes,
                    COUNT(*) as novos_usuarios
                FROM usuarios 
                WHERE data_cadastro >= DATE_SUB(CURRENT_DATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(data_cadastro, '%Y-%m')
                ORDER BY mes DESC
            ");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>