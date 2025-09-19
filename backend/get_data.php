<?php
// get_data.php - Endpoint unificado para buscar dados

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Item.php';
require_once 'repositories/UserRepository.php';
require_once 'repositories/ItemRepository.php';

startSession();

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'check_auth':
        $response = ['logged_in' => isLoggedIn()];
        if (isLoggedIn()) {
            $response['user'] = getCurrentUserData();
            $response['is_admin'] = isAdmin();
        }
        jsonResponse(true, '', $response);
        break;
        
    case 'user_profile':
        requireLogin();
        try {
            $userRepository = new UserRepository();
            $user = $userRepository->findById(getCurrentUserId());
            
            if ($user) {
                jsonResponse(true, '', $user->toArray());
            } else {
                jsonResponse(false, 'Usuário não encontrado');
            }
        } catch (Exception $e) {
            logError('Erro ao buscar perfil: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'user_stats':
        requireLogin();
        try {
            $itemRepository = new ItemRepository();
            $userId = getCurrentUserId();
            
            $stats = [
                'total_itens' => $itemRepository->countByUser($userId),
                'itens_disponiveis' => $itemRepository->countByUser($userId, 'disponivel'),
                'itens_trocados' => $itemRepository->countByUser($userId, 'trocado')
            ];
            
            jsonResponse(true, '', $stats);
        } catch (Exception $e) {
            logError('Erro ao buscar estatísticas: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'categories':
        try {
            $itemRepository = new ItemRepository();
            $categories = $itemRepository->getCategories();
            jsonResponse(true, '', $categories);
        } catch (Exception $e) {
            logError('Erro ao buscar categorias: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'public_items':
        try {
            $itemRepository = new ItemRepository();
            $filters = [
                'status' => 'disponivel',
                'categoria_id' => $_GET['category'] ?? '',
                'search' => $_GET['search'] ?? '',
                'limit' => $_GET['limit'] ?? null
            ];
            
            $items = $itemRepository->findAll($filters);
            jsonResponse(true, '', $items);
        } catch (Exception $e) {
            logError('Erro ao buscar itens públicos: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'user_items':
        requireLogin();
        try {
            $itemRepository = new ItemRepository();
            $filters = [
                'usuario_id' => getCurrentUserId(),
                'categoria_id' => $_GET['category'] ?? '',
                'status' => $_GET['status'] ?? '',
                'search' => $_GET['search'] ?? ''
            ];
            
            $items = $itemRepository->findAll($filters);
            jsonResponse(true, '', $items);
        } catch (Exception $e) {
            logError('Erro ao buscar itens do usuário: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'admin_stats':
        requireAdmin();
        try {
            $userRepository = new UserRepository();
            $itemRepository = new ItemRepository();
            
            $stats = [
                'total_usuarios' => $userRepository->count(),
                'total_itens' => $itemRepository->getStats()['total_itens'],
                'itens_disponiveis' => $itemRepository->getStats()['itens_disponiveis'],
                'itens_trocados' => $itemRepository->getStats()['itens_trocados']
            ];
            
            jsonResponse(true, '', $stats);
        } catch (Exception $e) {
            logError('Erro ao buscar estatísticas admin: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'admin_users':
        requireAdmin();
        try {
            $userRepository = new UserRepository();
            $itemRepository = new ItemRepository();
            
            $search = $_GET['search'] ?? '';
            $orderBy = $_GET['orderBy'] ?? 'nome';
            $order = $_GET['order'] ?? 'ASC';
            
            $users = $userRepository->findAll($search, $orderBy, $order);
            
            $usersWithStats = [];
            foreach ($users as $user) {
                $userArray = $user->toArray();
                $userArray['total_itens'] = $itemRepository->countByUser($user->getId());
                $usersWithStats[] = $userArray;
            }
            
            jsonResponse(true, '', $usersWithStats);
        } catch (Exception $e) {
            logError('Erro ao buscar usuários: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    default:
        jsonResponse(false, 'Ação não reconhecida');
        break;
}
?>