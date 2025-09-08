<?php
final class Database
{
    private static ?Database $instance = null;
    private \PDO $pdo;

    private const DB_HOST = 'localhost';
    private const DB_NAME = 'your_db_name';
    private const DB_USER = 'your_db_user';
    private const DB_PASS = 'your_db_pass';
    private const DB_CHARSET = 'utf8mb4';

    private function __construct()
    {
        $dsn = 'mysql:host=' . self::DB_HOST . ';dbname=' . self::DB_NAME . ';charset=' . self::DB_CHARSET;
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => false,
        ];
        try {
            $this->pdo = new \PDO($dsn, self::DB_USER, self::DB_PASS, $options);
        } catch (\PDOException $e) {
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection(): \PDO
    {
        return $this->pdo;
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }
}
