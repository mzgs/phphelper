<?php

namespace App\Config;

class Database
{
    private static $instance = null;
    private $connection = null;

    private function __construct()
    {
        // Don't create connection in constructor
    }

    // Singleton pattern to ensure only one database connection
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    // Get the PDO connection with lazy initialization
    public function getConnection(): \PDO
    {
        if ($this->connection === null) {
            $this->initConnection();
        }
        return $this->connection;
    }

    // Initialize database connection
    private function initConnection(): void
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $db   = $_ENV['DB_NAME'] ?? '';
        $user = $_ENV['DB_USER'] ?? '';
        $pass = $_ENV['DB_PASS'] ?? '';

        if (empty($db)) {
            throw new \Exception('Database configuration is incomplete. Please check your .env file.');
        }

        try {
            $this->connection = new \PDO(
                "mysql:host=$host;dbname=$db;charset=utf8mb4",
                $user,
                $pass,
                [
                    \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                    \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                    \PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );
        } catch (\PDOException $e) {
            throw new \Exception("Database connection failed. Please check your database settings: " . $e->getMessage());
        }
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Automatically close the connection when the object is destroyed
    public function __destruct()
    {
        $this->connection = null;
    }
}