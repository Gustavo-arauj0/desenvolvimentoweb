<?php
// repositories/ItemRepository.php - Repositório para operações com itens

class ItemRepository {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca item por ID
     */
    public function findById($id) {
        try {
            $sql = "SELECT i.*, c.nome as categoria_nome, u.nome as usuario_nome 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    WHERE i.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            return $data ? $data : null;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar item por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cria novo item
     */
    public function create($itemData) {
        try {
            $sql = "INSERT INTO itens (usuario_id, categoria_id, titulo, descricao, condicao, imagem_url) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $itemData['usuario_id'],
                $itemData['categoria_id'],
                $itemData['titulo'],
                $itemData['descricao'],
                $itemData['condicao'],
                $itemData['imagem_url']
            ]);
            
            if ($result) {
                $itemId = $this->pdo->lastInsertId();
                return $this->findById($itemId);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erro ao criar item: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza item
     */
    public function update($id, $itemData) {
        try {
            // Construir query dinâmica baseada nos campos fornecidos
            $fields = [];
            $params = [];
            
            if (isset($itemData['categoria_id'])) {
                $fields[] = "categoria_id = ?";
                $params[] = $itemData['categoria_id'];
            }
            
            if (isset($itemData['titulo'])) {
                $fields[] = "titulo = ?";
                $params[] = $itemData['titulo'];
            }
            
            if (isset($itemData['descricao'])) {
                $fields[] = "descricao = ?";
                $params[] = $itemData['descricao'];
            }
            
            if (isset($itemData['condicao'])) {
                $fields[] = "condicao = ?";
                $params[] = $itemData['condicao'];
            }
            
            if (isset($itemData['imagem_url'])) {
                $fields[] = "imagem_url = ?";
                $params[] = $itemData['imagem_url'];
            }
            
            if (isset($itemData['status'])) {
                $fields[] = "status = ?";
                $params[] = $itemData['status'];
            }
            
            if (empty($fields)) {
                return null; // Nada para atualizar
            }
            
            $sql = "UPDATE itens SET " . implode(', ', $fields) . " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result ? $this->findById($id) : null;
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar item: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Exclui item
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM itens WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Erro ao excluir item: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista itens com filtros
     */
    public function findAll($filters = []) {
        try {
            $sql = "SELECT i.*, c.nome as categoria_nome, u.nome as usuario_nome, u.localizacao as usuario_localizacao
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    WHERE 1=1";
            
            $params = [];
            
            // Filtro por usuário
            if (!empty($filters['usuario_id'])) {
                $sql .= " AND i.usuario_id = ?";
                $params[] = $filters['usuario_id'];
            }
            
            // Filtro por categoria
            if (!empty($filters['categoria_id'])) {
                $sql .= " AND i.categoria_id = ?";
                $params[] = $filters['categoria_id'];
            }
            
            // Filtro por status
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = ?";
                $params[] = $filters['status'];
            } else {
                // Por padrão, mostrar apenas itens disponíveis
                $sql .= " AND i.status = 'disponivel'";
            }
            
            // Filtro por busca
            if (!empty($filters['search'])) {
                $sql .= " AND (i.titulo LIKE ? OR i.descricao LIKE ?)";
                $params[] = "%" . $filters['search'] . "%";
                $params[] = "%" . $filters['search'] . "%";
            }
            
            // Ordenação
            $orderBy = $filters['order_by'] ?? 'data_cadastro';
            $order = $filters['order'] ?? 'DESC';
            $sql .= " ORDER BY i.$orderBy $order";
            
            // Limite
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = $filters['limit'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao listar itens: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta itens por usuário
     */
    public function countByUser($userId, $status = null) {
        try {
            $sql = "SELECT COUNT(*) FROM itens WHERE usuario_id = ?";
            $params = [$userId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Erro ao contar itens: " . $e->getMessage());
            return 0;
        }
    }

    public function deleteByUserId($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("DELETE FROM itens WHERE usuario_id = ?");
        return $stmt->execute([$userId]);
    }
    
    /**
     * Busca categorias
     */
    public function getCategories() {
        try {
            $stmt = $this->pdo->query("SELECT * FROM categorias WHERE ativa = 1 ORDER BY nome");
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar categorias: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta itens do usuário
     */
    public function countUserItems($userId, $status = null) {
        try {
            $sql = "SELECT COUNT(*) FROM itens WHERE usuario_id = ?";
            $params = [$userId];
            
            if ($status) {
                $sql .= " AND status = ?";
                $params[] = $status;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Erro ao contar itens do usuário: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Busca itens recentes do usuário
     */
    public function findRecentUserItems($userId, $limit = 3) {
        try {
            $sql = "SELECT i.id, i.titulo as title, i.descricao as description, 
                           i.condicao as `condition`, i.imagem_url as image_url, 
                           CASE 
                               WHEN i.status = 'disponivel' THEN 'available'
                               WHEN i.status = 'trocado' THEN 'traded'
                               WHEN i.status = 'removido' THEN 'removed'
                               ELSE i.status
                           END as status, 
                           i.data_cadastro as created_at, 
                           c.nome as category 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    WHERE i.usuario_id = ? 
                    ORDER BY i.data_cadastro DESC 
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar itens recentes: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca todos os itens do usuário
     */
    public function findUserItems($userId) {
        try {
            $sql = "SELECT i.id, i.titulo as title, i.descricao as description, 
                           i.condicao as `condition`, i.imagem_url as image_url, 
                           CASE 
                               WHEN i.status = 'disponivel' THEN 'available'
                               WHEN i.status = 'trocado' THEN 'traded'
                               WHEN i.status = 'removido' THEN 'removed'
                               ELSE i.status
                           END as status, 
                           i.data_cadastro as created_at, 
                           c.nome as category 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    WHERE i.usuario_id = ? 
                    ORDER BY i.data_cadastro DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar itens do usuário: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca itens do usuário com filtros
     */
    public function findUserItemsWithFilters($filters) {
        try {
            $sql = "SELECT i.id, i.titulo as title, i.descricao as description, 
                           i.condicao as `condition`, i.imagem_url as image_url, 
                           CASE 
                               WHEN i.status = 'disponivel' THEN 'available'
                               WHEN i.status = 'trocado' THEN 'traded'
                               WHEN i.status = 'removido' THEN 'removed'
                               ELSE i.status
                           END as status, 
                           i.data_cadastro as created_at, 
                           c.nome as category 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    WHERE i.usuario_id = ?";
            
            $params = [$filters['usuario_id']];
            
            if (!empty($filters['category'])) {
                $sql .= " AND c.nome = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['status'])) {
                $sql .= " AND i.status = ?";
                $params[] = $filters['status'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (i.titulo LIKE ? OR i.descricao LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY i.data_cadastro DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar itens do usuário com filtros: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca itens públicos disponíveis
     */
    public function findPublicItems($filters = []) {
        try {
            $sql = "SELECT i.id, i.titulo as title, i.descricao as description, 
                           i.condicao as `condition`, i.imagem_url as image_url, 
                           'available' as status, 
                           i.data_cadastro as created_at, 
                           c.nome as category, u.nome as owner_name, u.localizacao as owner_location 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    WHERE i.status = 'disponivel'";
            
            $params = [];
            
            if (!empty($filters['category'])) {
                $sql .= " AND c.nome = ?";
                $params[] = $filters['category'];
            }
            
            if (!empty($filters['condition'])) {
                $sql .= " AND i.condicao = ?";
                $params[] = $filters['condition'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (i.titulo LIKE ? OR i.descricao LIKE ? OR c.nome LIKE ?)";
                $searchTerm = "%" . $filters['search'] . "%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql .= " ORDER BY i.data_cadastro DESC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar itens públicos: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Busca item por ID com dados do dono
     */
    public function findByIdWithOwner($id) {
        try {
            $sql = "SELECT i.id, i.titulo as title, i.descricao as description, 
                           i.condicao as `condition`, i.imagem_url as image_url, 
                           CASE 
                               WHEN i.status = 'disponivel' THEN 'available'
                               WHEN i.status = 'trocado' THEN 'traded'
                               WHEN i.status = 'removido' THEN 'removed'
                               ELSE i.status
                           END as status, 
                           i.data_cadastro as created_at, 
                           c.nome as category, u.nome as owner_name, u.email as owner_email, 
                           u.telefone as owner_phone, u.localizacao as owner_location 
                    FROM itens i 
                    LEFT JOIN categorias c ON i.categoria_id = c.id 
                    LEFT JOIN usuarios u ON i.usuario_id = u.id 
                    WHERE i.id = ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar item com dono: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Estatísticas gerais
     */
    public function getStats() {
        try {
            $stats = [];
            
            // Total de itens
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM itens");
            $stats['total_itens'] = $stmt->fetchColumn();
            
            // Itens disponíveis
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM itens WHERE status = 'disponivel'");
            $stats['itens_disponiveis'] = $stmt->fetchColumn();
            
            // Itens trocados
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM itens WHERE status = 'trocado'");
            $stats['itens_trocados'] = $stmt->fetchColumn();
            
            // Itens por categoria
            $stmt = $this->pdo->query("
                SELECT c.nome, COUNT(i.id) as total 
                FROM categorias c 
                LEFT JOIN itens i ON c.id = i.categoria_id 
                GROUP BY c.id, c.nome 
                ORDER BY total DESC
            ");
            $stats['por_categoria'] = $stmt->fetchAll();
            
            return $stats;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar estatísticas: " . $e->getMessage());
            return [];
        }
    }
}