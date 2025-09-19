<?php
// save_data.php - Endpoint unificado para salvar dados

require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'config/database.php';
require_once 'classes/Database.php';
require_once 'classes/User.php';
require_once 'classes/Item.php';
require_once 'repositories/UserRepository.php';
require_once 'repositories/ItemRepository.php';

startSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Método não permitido');
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'update_profile':
        requireLogin();
        try {
            $data = [
                'name' => sanitizeInput($_POST['name'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'location' => sanitizeInput($_POST['location'] ?? ''),
                'newPassword' => $_POST['newPassword'] ?? '',
                'confirmNewPassword' => $_POST['confirmNewPassword'] ?? ''
            ];
            
            // Validações
            $errors = [];
            
            if (empty($data['name'])) $errors[] = 'Nome é obrigatório';
            if (empty($data['phone'])) $errors[] = 'Telefone é obrigatório';
            if (empty($data['location'])) $errors[] = 'Localização é obrigatória';
            
            if (!empty($data['newPassword'])) {
                if (strlen($data['newPassword']) < 6) {
                    $errors[] = 'Nova senha deve ter pelo menos 6 caracteres';
                }
                if ($data['newPassword'] !== $data['confirmNewPassword']) {
                    $errors[] = 'Confirmação da nova senha não confere';
                }
            }
            
            if (!empty($errors)) {
                jsonResponse(false, implode(', ', $errors));
                exit();
            }
            
            $userRepository = new UserRepository();
            $userId = getCurrentUserId();
            
            $userData = [
                'nome' => $data['name'],
                'telefone' => $data['phone'],
                'localizacao' => $data['location']
            ];
            
            if (!empty($data['newPassword'])) {
                $userData['senha'] = $data['newPassword'];
            }
            
            $user = $userRepository->update($userId, $userData);
            
            if ($user) {
                jsonResponse(true, 'Perfil atualizado com sucesso', $user->toArray());
            } else {
                jsonResponse(false, 'Erro ao atualizar perfil');
            }
            
        } catch (Exception $e) {
            logError('Erro ao atualizar perfil: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'delete_account':
        requireLogin();
        try {
            $userRepository = new UserRepository();
            $userId = getCurrentUserId();
            
            if ($userRepository->delete($userId)) {
                $_SESSION = [];
                session_destroy();
                jsonResponse(true, 'Conta excluída com sucesso');
            } else {
                jsonResponse(false, 'Erro ao excluir conta');
            }
            
        } catch (Exception $e) {
            logError('Erro ao excluir conta: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'save_item':
        requireLogin();
        try {
            $data = [
                'title' => sanitizeInput($_POST['title'] ?? ''),
                'description' => sanitizeInput($_POST['description'] ?? ''),
                'category' => sanitizeInput($_POST['category'] ?? ''),
                'condition' => sanitizeInput($_POST['condition'] ?? ''),
                'imageUrl' => sanitizeInput($_POST['imageUrl'] ?? '')
            ];
            
            // Validações
            $errors = [];
            
            if (empty($data['title'])) $errors[] = 'Título é obrigatório';
            if (empty($data['description'])) $errors[] = 'Descrição é obrigatória';
            if (empty($data['category'])) $errors[] = 'Categoria é obrigatória';
            if (empty($data['condition'])) $errors[] = 'Condição é obrigatória';
            if (empty($data['imageUrl'])) $errors[] = 'URL da imagem é obrigatória';
            if (!empty($data['imageUrl']) && !isValidUrl($data['imageUrl'])) {
                $errors[] = 'URL da imagem inválida';
            }
            
            if (!empty($errors)) {
                jsonResponse(false, implode(', ', $errors));
                exit();
            }
            
            $itemRepository = new ItemRepository();
            $itemId = $_POST['itemId'] ?? null;
            
            if ($itemId) {
                // Atualizar item existente
                $item = $itemRepository->findById($itemId);
                if (!$item || $item['usuario_id'] != getCurrentUserId()) {
                    jsonResponse(false, 'Item não encontrado ou não autorizado');
                    exit();
                }
                
                $itemData = [
                    'categoria_id' => $data['category'],
                    'titulo' => $data['title'],
                    'descricao' => $data['description'],
                    'condicao' => $data['condition'],
                    'imagem_url' => $data['imageUrl']
                ];
                
                $updatedItem = $itemRepository->update($itemId, $itemData);
                
                if ($updatedItem) {
                    jsonResponse(true, 'Item atualizado com sucesso', $updatedItem);
                } else {
                    jsonResponse(false, 'Erro ao atualizar item');
                }
                
            } else {
                // Criar novo item
                $itemData = [
                    'usuario_id' => getCurrentUserId(),
                    'categoria_id' => $data['category'],
                    'titulo' => $data['title'],
                    'descricao' => $data['description'],
                    'condicao' => $data['condition'],
                    'imagem_url' => $data['imageUrl']
                ];
                
                $item = $itemRepository->create($itemData);
                
                if ($item) {
                    jsonResponse(true, 'Item cadastrado com sucesso', $item);
                } else {
                    jsonResponse(false, 'Erro ao cadastrar item');
                }
            }
            
        } catch (Exception $e) {
            logError('Erro ao salvar item: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'delete_item':
        requireLogin();
        try {
            $itemId = $_POST['itemId'] ?? '';
            
            if (empty($itemId)) {
                jsonResponse(false, 'ID do item é obrigatório');
                exit();
            }
            
            $itemRepository = new ItemRepository();
            $item = $itemRepository->findById($itemId);
            
            if (!$item || $item['usuario_id'] != getCurrentUserId()) {
                jsonResponse(false, 'Item não encontrado ou não autorizado');
                exit();
            }
            
            if ($itemRepository->delete($itemId)) {
                jsonResponse(true, 'Item excluído com sucesso');
            } else {
                jsonResponse(false, 'Erro ao excluir item');
            }
            
        } catch (Exception $e) {
            logError('Erro ao excluir item: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    case 'delete_user':
        requireAdmin();
        try {
            $userId = $_POST['userId'] ?? '';
            
            if (empty($userId)) {
                jsonResponse(false, 'ID do usuário é obrigatório');
                exit();
            }
            
            // Não permitir exclusão do próprio admin
            if ($userId == getCurrentUserId()) {
                jsonResponse(false, 'Não é possível excluir sua própria conta');
                exit();
            }
            
            $userRepository = new UserRepository();
            $user = $userRepository->findById($userId);
            
            if (!$user) {
                jsonResponse(false, 'Usuário não encontrado');
                exit();
            }
            
            if ($userRepository->delete($userId)) {
                jsonResponse(true, 'Usuário excluído com sucesso');
            } else {
                jsonResponse(false, 'Erro ao excluir usuário');
            }
            
        } catch (Exception $e) {
            logError('Erro ao excluir usuário: ' . $e->getMessage());
            jsonResponse(false, 'Erro interno do servidor');
        }
        break;
        
    default:
        jsonResponse(false, 'Ação não reconhecida');
        break;
}
?>