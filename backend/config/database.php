<?php
class DatabaseConfig {
    // Configurações do banco de dados
    private const HOST = 'localhost';
    private const DB_NAME = 'ecoswap';
    private const USERNAME = 'root';
    private const PASSWORD = '';
    private const CHARSET = 'utf8mb4';
    
    /**
     * Retorna as configurações do banco como array
     */
    public static function getConfig() {
        return [
            'host' => self::HOST,
            'dbname' => self::DB_NAME,
            'username' => self::USERNAME,
            'password' => self::PASSWORD,
            'charset' => self::CHARSET
        ];
    }
    
    /**
     * Retorna o DSN para conexão PDO
     */
    public static function getDSN() {
        return "mysql:host=" . self::HOST . ";dbname=" . self::DB_NAME . ";charset=" . self::CHARSET;
    }
}