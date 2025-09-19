<?php
// controllers/ItemController.php - Controlador de itens

class ItemController {
    private $itemRepository;
    private $userRepository;
    private $auth;
    
    public function __construct() {
        $this->itemRepository = new ItemRepository();
        $this->userRepository = new UserRepository();
        $this->auth = new Auth($this->userRepository);
    }
    
    /**
     * Lista itens públicos
     */
    public function getPublicItems() {
        header('Content-Type: application/json');
        
        $filters = [
            'status' => 'disponivel',
            'categoria_id' => $_GET['category'] ?? '',
            'search' => $_GET['search'] ?? '',
            'limit' => $_GET['limit'] ?? null
        ];
        
        $items = $this->itemRepository->findAll($filters);
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    }
    
    /**
     * Lista itens do usuário logado
     */
    public function getUserItems() {
        header('Content-Type: application/json');
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        $filters = [
            'usuario_id' => $_SESSION['user_id'],
            'categoria_id' => $_GET['category'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? ''
        ];
        
        $items = $this->itemRepository->findAll($filters);
        
        echo json_encode([
            'success' => true,
            'items' => $items
        ]);
    }
    
    /**
     * Cria novo item
     */
    public function createItem() {
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
        
        // Validações
        $errors = $this->validateItem($input);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            return;
        }
        
        $itemData = [
            'usuario_id' => $_SESSION['user_id'],
            'categoria_id' => $input['category'],
            'titulo' => $input['title'],
            'descricao' => $input['description'],
            'condicao' => $input['condition'],
            'imagem_url' => $input['imageUrl']
        ];
        
        $item = $this->itemRepository->create($itemData);
        
        if ($item) {
            echo json_encode([
                'success' => true,
                'message' => 'Item cadastrado com sucesso',
                'item' => $item
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao cadastrar item']);
        }
    }
    
    /**
     * Atualiza item
     */
    public function updateItem($itemId) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        // Verifica se o item pertence ao usuário
        $item = $this->itemRepository->findById($itemId);
        if (!$item || $item['usuario_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Item não encontrado ou não autorizado']);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            $input = $_POST;
        }
        
        // Validações
        $errors = $this->validateItem($input);
        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
            return;
        }
        
        $itemData = [
            'categoria_id' => $input['category'],
            'titulo' => $input['title'],
            'descricao' => $input['description'],
            'condicao' => $input['condition'],
            'imagem_url' => $input['imageUrl']
        ];
        
        $updatedItem = $this->itemRepository->update($itemId, $itemData);
        
        if ($updatedItem) {
            echo json_encode([
                'success' => true,
                'message' => 'Item atualizado com sucesso',
                'item' => $updatedItem
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar item']);
        }
    }
    
    /**
     * Exclui item
     */
    public function deleteItem($itemId) {
        header('Content-Type: application/json');
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'message' => 'Método não permitido']);
            return;
        }
        
        if (!$this->auth->isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => 'Não autorizado']);
            return;
        }
        
        // Verifica se o item pertence ao usuário
        $item = $this->itemRepository->findById($itemId);
        if (!$item || $item['usuario_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Item não encontrado ou não autorizado']);
            return;
        }
        
        if ($this->itemRepository->delete($itemId)) {
            echo json_encode([
                'success' => true,
                'message' => 'Item excluído com sucesso'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir item']);
        }
    }
    
    /**
     * Obtém categorias
     */
    public function getCategories() {
        header('Content-Type: application/json');
        
        $categories = $this->itemRepository->getCategories();
        
        echo json_encode([
            'success' => true,
            'categories' => $categories
        ]);
    }
    
    /**
     * Valida dados do item
     */
    private function validateItem($data) {
        $errors = [];
        
        if (empty($data['title'])) {
            $errors[] = 'Título é obrigatório';
        }
        
        if (empty($data['description'])) {
            $errors[] = 'Descrição é obrigatória';
        }
        
        if (empty($data['category'])) {
            $errors[] = 'Categoria é obrigatória';
        }
        
        if (empty($data['condition'])) {
            $errors[] = 'Condição é obrigatória';
        }
        
        if (empty($data['imageUrl'])) {
            $errors[] = 'URL da imagem é obrigatória';
        } elseif (!filter_var($data['imageUrl'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL da imagem inválida';
        }
        
        return $errors;
    }
}