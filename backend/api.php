<?php
// api.php - Arquivo principal da API

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/Auth.php';
require_once 'classes/User.php';
require_once 'classes/Item.php';
require_once 'repositories/UserRepository.php';
require_once 'repositories/ItemRepository.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/UserController.php';
require_once 'controllers/ItemController.php';
require_once 'controllers/AdminController.php';
require_once 'includes/session.php';

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // --- ROTAS DE ADMIN ---
        case 'getAdminStatistics':
            handleAdminStatistics();
            break;
        case 'getUsersAdmin':
            handleGetUsersAdmin();
            break;
        case 'getAdminProfileData':
            handleGetAdminProfileData();
            break;
        case 'updateAdminProfile':
            handleUpdateAdminProfile();
            break;
        case 'generateReport':
            handleGenerateReport();
            break;
        case 'exportData':
            handleExportData();
            break;

        // --- ROTAS DE USUÁRIO ---
        case 'getUserData': handleUserData(); break;
        case 'getUserStatistics': handleUserStatistics(); break;
        case 'getUserRecentItems': handleUserRecentItems(); break;
        case 'getSustainabilityTip': handleSustainabilityTip(); break;
        case 'getItemsPageData': handleItemsPageData(); break;
        case 'getCategories': handleCategories(); break;
        case 'getUserItems': handleGetUserItems(); break;
        case 'getNoItemsMessage': handleNoItemsMessage(); break;
        case 'getPublicItems': handlePublicItems(); break;
        case 'getItemDetails': handleItemDetails(); break;
        case 'getNoPublicItemsMessage': handleNoPublicItemsMessage(); break;
        case 'searchItems': handleSearchItems(); break;
        case 'register': handleRegister(); break;
        case 'login': handleLogin(); break;
        case 'logout': handleLogout(); break;
        case 'addItem': handleAddItem(); break;
        case 'updateItem': handleUpdateItem(); break;
        case 'deleteItem': handleDeleteItem(); break;
        case 'getItemById': handleGetItemById(); break;
        case 'updateProfile': handleUpdateProfile(); break;
        case 'deleteAccount': handleDeleteAccount(); break;
        default:
            throw new Exception('Ação não encontrada');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// --- FUNÇÕES DE ADMIN ---
function handleAdminSecurityCheck() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        throw new Exception('Não autorizado. Faça login como administrador.');
    }
    
    $userRepo = new UserRepository();
    $user = $userRepo->findById($_SESSION['user_id']);
    
    if ($user->getTipoUsuario() !== 'admin') {
        throw new Exception('Acesso negado. Permissões insuficientes.');
    }
    return $user;
}

function handleAdminStatistics() {
    handleAdminSecurityCheck();
    $pdo = Database::getInstance()->getConnection();

    $totalUsers = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario != 'admin'")->fetchColumn();
    $totalItems = $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn();
    $availableItems = $pdo->query("SELECT COUNT(*) FROM itens WHERE status = 'disponivel'")->fetchColumn();
    $tradedItems = $pdo->query("SELECT COUNT(*) FROM itens WHERE status = 'trocado'")->fetchColumn() ?: 0;
    
    // Log para debug - pode ser removido em produção
    error_log("Admin Statistics - Total Users: $totalUsers, Total Items: $totalItems, Available: $availableItems, Traded: $tradedItems");

    $categoryQuery = $pdo->query("SELECT c.nome as category, COUNT(i.id) as count FROM itens i JOIN categorias c ON i.categoria_id = c.id GROUP BY c.nome");
    $categoryData = $categoryQuery->fetchAll(PDO::FETCH_ASSOC);

    $statusQuery = $pdo->query("SELECT status, COUNT(*) as count FROM itens GROUP BY status");
    $statusData = $statusQuery->fetchAll(PDO::FETCH_ASSOC);

    $recentUsersQuery = $pdo->query("SELECT nome, data_cadastro FROM usuarios WHERE tipo_usuario != 'admin' ORDER BY data_cadastro DESC LIMIT 5");
    $recentUsers = $recentUsersQuery->fetchAll(PDO::FETCH_ASSOC);

    $recentItemsQuery = $pdo->query("SELECT i.titulo, i.data_cadastro, u.nome as nome_usuario FROM itens i JOIN usuarios u ON i.usuario_id = u.id ORDER BY i.data_cadastro DESC LIMIT 5");
    $recentItems = $recentItemsQuery->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'statistics' => ['totalUsers' => $totalUsers, 'totalItems' => $totalItems, 'availableItems' => $availableItems, 'tradedItems' => $tradedItems], 'charts' => ['byCategory' => $categoryData, 'byStatus' => $statusData], 'activity' => ['users' => $recentUsers, 'items' => $recentItems]]);
}

function handleGetUsersAdmin() {
    handleAdminSecurityCheck();
    $pdo = Database::getInstance()->getConnection();

    $query = "SELECT u.id, u.nome, u.email, u.localizacao, u.telefone, u.data_cadastro, COUNT(i.id) as itemCount FROM usuarios u LEFT JOIN itens i ON u.id = i.usuario_id WHERE u.tipo_usuario != 'admin' GROUP BY u.id";
    $stmt = $pdo->query($query);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalUsers = count($users);
    $activeUsers = 0;
    $totalItems = 0;
    foreach ($users as $user) {
        if ($user['itemCount'] > 0) {
            $activeUsers++;
        }
        $totalItems += $user['itemCount'];
    }
    
    $averageItems = ($totalUsers > 0) ? round($totalItems / $totalUsers, 2) : 0;
    
    $newThisMonthQuery = "SELECT COUNT(*) FROM usuarios WHERE tipo_usuario != 'admin' AND MONTH(data_cadastro) = MONTH(CURDATE()) AND YEAR(data_cadastro) = YEAR(CURDATE())";
    $newThisMonth = $pdo->query($newThisMonthQuery)->fetchColumn();
    
    echo json_encode(['success' => true, 'stats' => ['totalUsers' => $totalUsers, 'activeUsers' => $activeUsers, 'newThisMonth' => $newThisMonth, 'averageItems' => $averageItems], 'users' => $users]);
}

function handleGetAdminProfileData() {
    $adminUser = handleAdminSecurityCheck();
    $pdo = Database::getInstance()->getConnection();

    $totalUsers = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario != 'admin'")->fetchColumn();
    $totalItems = $pdo->query("SELECT COUNT(*) FROM itens")->fetchColumn();

    echo json_encode([
        'success' => true,
        'profile' => [
            'name' => $adminUser->getNome(),
            'email' => $adminUser->getEmail(),
            'phone' => $adminUser->getTelefone(),
            'location' => $adminUser->getLocalizacao(),
            'createdAt' => $adminUser->getDataCadastro()
        ],
        'stats' => [
            'totalUsers' => $totalUsers,
            'totalItems' => $totalItems
        ]
    ]);
}

function handleUpdateAdminProfile() {
    $adminUser = handleAdminSecurityCheck();
    $input = json_decode(file_get_contents('php://input'), true);

    $dataToUpdate = [
        'nome' => trim($input['name'] ?? ''),
        'telefone' => trim($input['phone'] ?? ''),
        'localizacao' => trim($input['location'] ?? '')
    ];

    if (!empty($input['password'])) {
        if (strlen($input['password']) < 6) {
            throw new Exception("A nova senha deve ter pelo menos 6 caracteres.");
        }
        $dataToUpdate['senha'] = password_hash($input['password'], PASSWORD_DEFAULT);
    }
    
    $userRepo = new UserRepository();
    $success = $userRepo->update($adminUser->getId(), array_filter($dataToUpdate));

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
    } else {
        throw new Exception("Não foi possível atualizar o perfil.");
    }
}

// --- FUNÇÕES DE USUÁRIO (CÓDIGO ORIGINAL) ---
function handleUserData() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        return;
    }
    $userRepo = new UserRepository();
    $user = $userRepo->findById($_SESSION['user_id']);
    echo json_encode(['success' => true, 'user' => ['name' => $user->getNome(), 'email' => $user->getEmail(), 'type' => $user->getTipoUsuario()], 'welcomeMessage' => 'Aqui você pode gerenciar seus itens e acompanhar suas atividades de troca.']);
}

function handleUserStatistics() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        return;
    }
    $itemRepo = new ItemRepository();
    $userRepo = new UserRepository();
    $userId = $_SESSION['user_id'];
    $user = $userRepo->findById($userId);
    $totalItems = $itemRepo->countUserItems($userId);
    $availableItems = $itemRepo->countUserItems($userId, 'disponivel');
    $tradedItems = $itemRepo->countUserItems($userId, 'trocado');
    $joinDate = new DateTime($user->getDataCadastro());
    $today = new DateTime();
    $daysSinceJoin = $today->diff($joinDate)->days;
    echo json_encode(['success' => true, 'statistics' => ['totalItems' => $totalItems, 'availableItems' => $availableItems, 'tradedItems' => $tradedItems, 'daysSinceJoin' => $daysSinceJoin], 'labels' => ['totalItems' => 'Itens Cadastrados', 'availableItems' => 'Disponíveis', 'tradedItems' => 'Trocados', 'daysSinceJoin' => 'Dias no Sistema']]);
}

function handleUserRecentItems() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        return;
    }
    $itemRepo = new ItemRepository();
    $limit = $_GET['limit'] ?? 3;
    $items = $itemRepo->findRecentUserItems($_SESSION['user_id'], $limit);
    echo json_encode(['success' => true, 'items' => $items, 'noItemsMessage' => '<i class="bi bi-inbox display-1 text-muted mb-3"></i><p class="text-muted">Você ainda não cadastrou nenhum item</p><a href="items.html?action=add" class="btn btn-primary"><i class="bi bi-plus me-2"></i>Adicionar Primeiro Item</a>']);
}

function handleSustainabilityTip() {
    $tips = ['🌱 <strong>Sabia que:</strong> Cada item que você troca evita a produção de um novo produto, reduzindo significativamente sua pegada de carbono!', '♻️ <strong>Economia Circular:</strong> Ao trocar itens, você está participando da economia circular, onde nada é desperdiçado!', '🌍 <strong>Impacto Global:</strong> Pequenas ações como trocar um livro podem ter um grande impacto no meio ambiente.', '🌿 <strong>Consumo Consciente:</strong> Antes de comprar algo novo, pense se você pode encontrar no EcoSwap!', '📚 <strong>Educação Ambiental:</strong> Compartilhe a ideia de trocas sustentáveis com amigos e família!', '🔄 <strong>Reutilização:</strong> Dar uma segunda vida aos objetos é uma das formas mais eficazes de reduzir o lixo.', '💚 <strong>Comunidade Verde:</strong> Você faz parte de uma comunidade que se preocupa com o futuro do planeta!', '🎯 <strong>ODS 12:</strong> Suas trocas contribuem diretamente para o Objetivo de Desenvolvimento Sustentável 12 - Consumo Responsável!'];
    echo json_encode(['success' => true, 'tip' => $tips[array_rand($tips)]]);
}

function handleItemsPageData() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        return;
    }
    $userRepo = new UserRepository();
    $user = $userRepo->findById($_SESSION['user_id']);
    echo json_encode(['success' => true, 'user' => ['name' => $user->getNome()], 'pageTitle' => 'Meus Itens', 'pageDescription' => 'Gerencie os itens que você disponibilizou para troca']);
}

function handleCategories() {
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT nome FROM categorias WHERE ativa = 1 ORDER BY nome");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($categories)) {
            // Fallback para categorias padrão caso não existam no banco
            $categories = ['Eletrônicos', 'Roupas', 'Casa e Jardim', 'Livros', 'Esportes', 'Música', 'Brinquedos', 'Veículos', 'Beleza', 'Outros'];
        }
        
        echo json_encode(['success' => true, 'categories' => $categories]);
    } catch (Exception $e) {
        error_log("Erro ao buscar categorias: " . $e->getMessage());
        // Fallback em caso de erro
        $categories = ['Eletrônicos', 'Roupas', 'Casa e Jardim', 'Livros', 'Esportes', 'Música', 'Brinquedos', 'Veículos', 'Beleza', 'Outros'];
        echo json_encode(['success' => true, 'categories' => $categories]);
    }
}

function handleGetUserItems() {
    $auth = new Auth(new UserRepository());
    if (!$auth->isLoggedIn()) {
        echo json_encode(['success' => false, 'message' => 'Não autorizado']);
        return;
    }
    
    // Construir filtros
    $filters = [
        'usuario_id' => $_SESSION['user_id']
    ];
    
    if (!empty($_GET['category'])) {
        $filters['category'] = $_GET['category'];
    }
    
    if (!empty($_GET['status'])) {
        // Mapear status de inglês para português
        $statusMap = [
            'available' => 'disponivel',
            'traded' => 'trocado', 
            'removed' => 'removido'
        ];
        $filters['status'] = $statusMap[$_GET['status']] ?? $_GET['status'];
    }
    
    if (!empty($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    $itemRepo = new ItemRepository();
    $items = $itemRepo->findUserItemsWithFilters($filters);
    echo json_encode(['success' => true, 'items' => $items]);
}

function handleNoItemsMessage() {
    echo json_encode(['success' => true, 'message' => '<i class="bi bi-inbox display-1 text-muted mb-3"></i><p class="text-muted">Você ainda não cadastrou nenhum item</p><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal"><i class="bi bi-plus me-2"></i>Adicionar Primeiro Item</button>']);
}

function handlePublicItems() {
    $itemRepo = new ItemRepository();
    $filters = ['category' => $_GET['category'] ?? '', 'condition' => $_GET['condition'] ?? ''];
    $items = $itemRepo->findPublicItems($filters);
    
    // Buscar categorias do banco de dados
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT nome FROM categorias WHERE ativa = 1 ORDER BY nome");
        $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($categories)) {
            $categories = ['Eletrônicos', 'Roupas', 'Casa e Jardim', 'Livros', 'Esportes', 'Música', 'Brinquedos', 'Veículos', 'Beleza', 'Outros'];
        }
    } catch (Exception $e) {
        error_log("Erro ao buscar categorias: " . $e->getMessage());
        $categories = ['Eletrônicos', 'Roupas', 'Casa e Jardim', 'Livros', 'Esportes', 'Música', 'Brinquedos', 'Veículos', 'Beleza', 'Outros'];
    }
    
    echo json_encode(['success' => true, 'items' => $items, 'categories' => $categories, 'catalogTitle' => 'Itens Disponíveis para Troca']);
}

function handleItemDetails() {
    $itemId = $_GET['id'] ?? '';
    if (empty($itemId)) {
        echo json_encode(['success' => false, 'message' => 'ID do item não fornecido']);
        return;
    }
    $itemRepo = new ItemRepository();
    $item = $itemRepo->findByIdWithOwner($itemId);
    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
        return;
    }
    echo json_encode(['success' => true, 'item' => $item]);
}

function handleNoPublicItemsMessage() {
    echo json_encode(['success' => true, 'message' => '<i class="bi bi-inbox display-1 text-muted"></i><p class="text-muted mt-3">Nenhum item disponível no momento</p>']);
}

function handleSearchItems() {
    $query = $_GET['q'] ?? '';
    if (empty($query) || strlen($query) < 2) {
        echo json_encode(['success' => false, 'message' => 'Termo de busca deve ter pelo menos 2 caracteres']);
        return;
    }
    
    $itemRepo = new ItemRepository();
    $filters = [
        'search' => $query,
        'status' => 'disponivel'
    ];
    
    $items = $itemRepo->findPublicItems($filters);
    echo json_encode(['success' => true, 'items' => $items]);
}

function handleRegister() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);
    if (!$input) { $input = $_POST; }
    $data = ['nome' => trim($input['name'] ?? ''), 'email' => trim($input['email'] ?? ''), 'senha' => $input['password'] ?? '', 'telefone' => trim($input['phone'] ?? ''), 'localizacao' => trim($input['location'] ?? ''), 'tipo_usuario' => 'usuario'];
    $errors = [];
    if (empty($data['nome'])) { $errors[] = 'Nome é obrigatório'; }
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors[] = 'Email válido é obrigatório'; }
    if (empty($data['senha']) || strlen($data['senha']) < 6) { $errors[] = 'Senha deve ter pelo menos 6 caracteres'; }
    if (empty($data['telefone'])) { $errors[] = 'Telefone é obrigatório'; }
    if (empty($data['localizacao'])) { $errors[] = 'Localização é obrigatória'; }
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    try {
        $userRepo = new UserRepository();
        if ($userRepo->findByEmail($data['email'])) {
            echo json_encode(['success' => false, 'message' => 'Este email já está cadastrado']);
            return;
        }
        if ($userRepo->create($data)) {
            echo json_encode(['success' => true, 'message' => 'Cadastro realizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao criar usuário']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleLogin() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }
    $email = trim($input['email'] ?? '');
    $senha = $input['password'] ?? '';
    if (empty($email) || empty($senha)) {
        echo json_encode(['success' => false, 'message' => 'Email e senha são obrigatórios']);
        return;
    }
    try {
        $auth = new Auth(new UserRepository());
        echo json_encode($auth->login($email, $senha));
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleLogout() {
    try {
        $auth = new Auth(new UserRepository());
        echo json_encode($auth->logout());
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleAddItem() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }
    $data = ['titulo' => trim($input['name'] ?? ''), 'descricao' => trim($input['description'] ?? ''), 'categoria' => trim($input['category'] ?? ''), 'condicao' => trim($input['condition'] ?? ''), 'localizacao' => trim($input['location'] ?? ''), 'imagem_url' => trim($input['image'] ?? ''), 'usuario_id' => $_SESSION['user_id'], 'status' => 'disponivel'];
    $errors = [];
    if (empty($data['titulo'])) { $errors[] = 'Título é obrigatório'; }
    if (empty($data['descricao'])) { $errors[] = 'Descrição é obrigatória'; }
    if (empty($data['categoria'])) { $errors[] = 'Categoria é obrigatória'; }
    if (empty($data['condicao'])) { $errors[] = 'Condição é obrigatória'; }
    if (empty($data['localizacao'])) { $data['localizacao'] = 'Não informado'; }
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    try {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nome = ?");
        $stmt->execute([$data['categoria']]);
        $categoria = $stmt->fetch();
        if (!$categoria) {
            echo json_encode(['success' => false, 'message' => 'Categoria inválida']);
            return;
        }
        $data['categoria_id'] = $categoria['id'];
        unset($data['categoria']);
        $itemRepo = new ItemRepository();
        $item = $itemRepo->create($data);
        if ($item) {
            echo json_encode(['success' => true, 'message' => 'Item adicionado com sucesso!', 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao adicionar item']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleUpdateItem() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }
    $itemId = $input['id'] ?? null;
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'ID do item é obrigatório']);
        return;
    }
    try {
        $itemRepo = new ItemRepository();
        $existingItem = $itemRepo->findById($itemId);
        if (!$existingItem || $existingItem['usuario_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Item não encontrado ou sem permissão']);
            return;
        }
        $updateData = [];
        if (!empty($input['name'])) $updateData['titulo'] = trim($input['name']);
        if (!empty($input['description'])) $updateData['descricao'] = trim($input['description']);
        if (!empty($input['category'])) $updateData['categoria'] = trim($input['category']);
        if (!empty($input['condition'])) $updateData['condicao'] = trim($input['condition']);
        if (!empty($input['location'])) $updateData['localizacao'] = trim($input['location']);
        if (!empty($input['image'])) $updateData['imagem_url'] = trim($input['image']);
        if (!empty($input['status'])) $updateData['status'] = trim($input['status']);
        if (empty($updateData)) {
            echo json_encode(['success' => false, 'message' => 'Nenhum dado para atualizar']);
            return;
        }
        if (isset($updateData['categoria'])) {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nome = ?");
            $stmt->execute([$updateData['categoria']]);
            $categoria = $stmt->fetch();
            if (!$categoria) {
                echo json_encode(['success' => false, 'message' => 'Categoria inválida']);
                return;
            }
            $updateData['categoria_id'] = $categoria['id'];
            unset($updateData['categoria']);
        }
        
        // Mapear status de inglês para português
        if (isset($updateData['status'])) {
            $statusMap = [
                'traded' => 'trocado',
                'available' => 'disponivel',
                'removed' => 'removido'
            ];
            if (isset($statusMap[$updateData['status']])) {
                $updateData['status'] = $statusMap[$updateData['status']];
            }
        }
        if ($itemRepo->update($itemId, $updateData)) {
            echo json_encode(['success' => true, 'message' => 'Item atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar item']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleDeleteItem() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }
    $itemId = $input['id'] ?? null;
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'ID do item é obrigatório']);
        return;
    }
    try {
        $itemRepo = new ItemRepository();
        $existingItem = $itemRepo->findById($itemId);
        if (!$existingItem || $existingItem['usuario_id'] != $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Item não encontrado ou sem permissão']);
            return;
        }
        if ($itemRepo->delete($itemId)) {
            echo json_encode(['success' => true, 'message' => 'Item excluído com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir item']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleGetItemById() {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    $itemId = $_GET['id'] ?? null;
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'ID do item é obrigatório']);
        return;
    }
    try {
        $itemRepo = new ItemRepository();
        $item = $itemRepo->findById($itemId);
        if ($item) {
            echo json_encode(['success' => true, 'item' => $item]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item não encontrado']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleUpdateProfile() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) { $input = $_POST; }
    $updateData = [];
    if (!empty($input['name'])) $updateData['nome'] = trim($input['name']);
    if (!empty($input['phone'])) $updateData['telefone'] = trim($input['phone']);
    if (!empty($input['location'])) $updateData['localizacao'] = trim($input['location']);
    if (isset($updateData['nome']) && empty($updateData['nome'])) {
        echo json_encode(['success' => false, 'message' => 'Nome não pode ser vazio']);
        return;
    }
    if (empty($updateData)) {
        echo json_encode(['success' => false, 'message' => 'Nenhum dado para atualizar']);
        return;
    }
    try {
        $userRepo = new UserRepository();
        if ($userRepo->update($_SESSION['user_id'], $updateData)) {
            echo json_encode(['success' => true, 'message' => 'Perfil atualizado com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao atualizar perfil']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleDeleteAccount() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'Método não permitido']);
        return;
    }
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
        return;
    }
    try {
        $itemRepo = new ItemRepository();
        $itemRepo->deleteByUserId($_SESSION['user_id']);
        $userRepo = new UserRepository();
        if ($userRepo->delete($_SESSION['user_id'])) {
            session_destroy();
            echo json_encode(['success' => true, 'message' => 'Conta excluída com sucesso!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erro ao excluir conta']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
    }
}

function handleGenerateReport() {
    // Verificar se é admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        return;
    }

    try {
        $db = Database::getInstance()->getConnection();
        
        // Buscar dados para o relatório
        $query = "
            SELECT 
                u.nome as usuario_nome,
                u.email as usuario_email,
                u.telefone as usuario_telefone,
                u.localizacao as usuario_localizacao,
                u.data_cadastro as usuario_data_cadastro,
                COUNT(i.id) as total_itens,
                COUNT(CASE WHEN i.status = 'disponivel' THEN 1 END) as itens_disponiveis,
                COUNT(CASE WHEN i.status = 'trocado' THEN 1 END) as itens_trocados,
                COUNT(CASE WHEN i.status = 'removido' THEN 1 END) as itens_removidos
            FROM usuarios u
            LEFT JOIN itens i ON u.id = i.usuario_id
            WHERE u.tipo_usuario = 'user'
            GROUP BY u.id, u.nome, u.email, u.telefone, u.localizacao, u.data_cadastro
            ORDER BY u.data_cadastro DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar estatísticas gerais
        $statsQuery = "
            SELECT 
                COUNT(DISTINCT u.id) as total_usuarios,
                COUNT(i.id) as total_itens,
                COUNT(CASE WHEN i.status = 'disponivel' THEN 1 END) as total_disponiveis,
                COUNT(CASE WHEN i.status = 'trocado' THEN 1 END) as total_trocados
            FROM usuarios u
            LEFT JOIN itens i ON u.id = i.usuario_id
            WHERE u.tipo_usuario = 'user'
        ";
        
        $statsStmt = $db->prepare($statsQuery);
        $statsStmt->execute();
        $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
        
        // Buscar itens detalhados
        $itemsQuery = "
            SELECT 
                i.titulo,
                i.descricao,
                c.nome as categoria,
                i.condicao,
                i.status,
                i.data_cadastro,
                u.nome as usuario_nome,
                u.email as usuario_email
            FROM itens i
            JOIN usuarios u ON i.usuario_id = u.id
            JOIN categorias c ON i.categoria_id = c.id
            ORDER BY i.data_cadastro DESC
        ";
        
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute();
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => [
                'users' => $users,
                'items' => $items,
                'statistics' => $stats,
                'generated_at' => date('Y-m-d H:i:s'),
                'report_type' => 'admin_report'
            ]
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao gerar relatório: ' . $e->getMessage()]);
    }
}

function handleExportData() {
    // Verificar se é admin
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        return;
    }

    try {
        $db = Database::getInstance()->getConnection();
        
        // Buscar todos os dados do sistema
        $data = [];
        
        // Usuários (excluir senhas)
        $usersQuery = "SELECT id, nome, email, telefone, localizacao, tipo_usuario, data_cadastro FROM usuarios ORDER BY data_cadastro DESC";
        $stmt = $db->prepare($usersQuery);
        $stmt->execute();
        $data['usuarios'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Categorias
        $categoriesQuery = "SELECT * FROM categorias ORDER BY nome";
        $stmt = $db->prepare($categoriesQuery);
        $stmt->execute();
        $data['categorias'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Itens com informações de usuário e categoria
        $itemsQuery = "
            SELECT 
                i.id,
                i.titulo,
                i.descricao,
                i.condicao,
                i.status,
                i.imagem_url,
                i.data_cadastro,
                u.nome as usuario_nome,
                u.email as usuario_email,
                c.nome as categoria_nome
            FROM itens i
            JOIN usuarios u ON i.usuario_id = u.id
            JOIN categorias c ON i.categoria_id = c.id
            ORDER BY i.data_cadastro DESC
        ";
        $stmt = $db->prepare($itemsQuery);
        $stmt->execute();
        $data['itens'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Trocas (se existir tabela)
        try {
            $tradesQuery = "SELECT * FROM trocas ORDER BY data_troca DESC";
            $stmt = $db->prepare($tradesQuery);
            $stmt->execute();
            $data['trocas'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Tabela de trocas não existe ainda
            $data['trocas'] = [];
        }
        
        // Metadados da exportação
        $data['metadata'] = [
            'exported_at' => date('Y-m-d H:i:s'),
            'exported_by' => $_SESSION['user_email'] ?? 'admin',
            'version' => '1.0',
            'system' => 'EcoSwap'
        ];
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'filename' => 'ecoswap_export_' . date('Y-m-d_H-i-s') . '.json'
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao exportar dados: ' . $e->getMessage()]);
    }
}

?>
