<?php
// repositories/UserRepository.php - Repositório para operações com usuários

class UserRepository {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getInstance()->getConnection();
    }
    
    /**
     * Busca usuário por ID
     */
    public function findById($id) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
            $stmt->execute([$id]);
            $data = $stmt->fetch();
            
            return $data ? User::fromArray($data) : null;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário por ID: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Busca usuário por email
     */
    public function findByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE email = ?");
            $stmt->execute([$email]);
            $data = $stmt->fetch();
            
            return $data ? User::fromArray($data) : null;
            
        } catch (PDOException $e) {
            error_log("Erro ao buscar usuário por email: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Cria novo usuário
     */
    public function create($userData) {
        try {
            $sql = "INSERT INTO usuarios (nome, email, senha, telefone, localizacao, tipo_usuario) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([
                $userData['nome'],
                $userData['email'],
                md5($userData['senha']), // Em produção, usar password_hash()
                $userData['telefone'],
                $userData['localizacao'],
                $userData['tipo_usuario'] ?? 'usuario'
            ]);
            
            if ($result) {
                $userId = $this->pdo->lastInsertId();
                return $this->findById($userId);
            }
            
            return null;
            
        } catch (PDOException $e) {
            error_log("Erro ao criar usuário: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Atualiza usuário
     */
    public function update($id, $userData) {
        try {
            $sql = "UPDATE usuarios SET nome = ?, telefone = ?, localizacao = ?";
            $params = [$userData['nome'], $userData['telefone'], $userData['localizacao']];
            
            // Se senha foi fornecida, incluir na atualização
            if (!empty($userData['senha'])) {
                $sql .= ", senha = ?";
                $params[] = md5($userData['senha']);
            }
            
            $sql .= " WHERE id = ?";
            $params[] = $id;
            
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute($params);
            
            return $result ? $this->findById($id) : null;
            
        } catch (PDOException $e) {
            error_log("Erro ao atualizar usuário: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Exclui usuário
     */
    public function delete($id) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = ?");
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            error_log("Erro ao excluir usuário: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lista todos os usuários (apenas para admin)
     */
    public function findAll($search = '', $orderBy = 'nome', $order = 'ASC') {
        try {
            $sql = "SELECT * FROM usuarios WHERE 1=1";
            $params = [];
            
            if (!empty($search)) {
                $sql .= " AND (nome LIKE ? OR email LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            $sql .= " ORDER BY $orderBy $order";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            $users = [];
            while ($row = $stmt->fetch()) {
                $users[] = User::fromArray($row);
            }
            
            return $users;
            
        } catch (PDOException $e) {
            error_log("Erro ao listar usuários: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Conta total de usuários
     */
    public function count() {
        try {
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM usuarios");
            return $stmt->fetchColumn();
            
        } catch (PDOException $e) {
            error_log("Erro ao contar usuários: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Verifica se email já existe
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT COUNT(*) FROM usuarios WHERE email = ?";
            $params = [$email];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchColumn() > 0;
            
        } catch (PDOException $e) {
            error_log("Erro ao verificar email: " . $e->getMessage());
            return false;
        }
    }
}