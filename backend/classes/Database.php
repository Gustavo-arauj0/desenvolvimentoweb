<?php
// classes/Database.php - Classe para gerenciar conexão com banco

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $config = DatabaseConfig::getConfig();
            $dsn = DatabaseConfig::getDSN();
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . $config['charset']
            ];
            
            $this->pdo = new PDO($dsn, $config['username'], $config['password'], $options);
            
        } catch (PDOException $e) {
            error_log("Erro de conexão com banco de dados: " . $e->getMessage());
            throw new Exception("Erro de conexão com banco de dados");
        }
    }
    
    /**
     * Singleton - retorna instância única da conexão
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Retorna a conexão PDO
     */
    public function getConnection() {
        return $this->pdo;
    }
    
    /**
     * Previne clonagem
     */
    private function __clone() {}
    
    /**
     * Previne deserialização
     */
    public function __wakeup() {}
}