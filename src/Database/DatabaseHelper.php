<?php

namespace PhpHelper\Database;

class DatabaseHelper
{
    private $connection;
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private static $instance = null;

    private function __construct(array $config)
    {
        $this->host     = $config['host'] ?? 'localhost';
        $this->username = $config['username'] ?? '';
        $this->password = $config['password'] ?? '';
        $this->database = $config['database'] ?? '';
        $this->port     = $config['port'] ?? 3306;
    }

    public static function getInstance(array $config = []): self
    {
        if (self::$instance === null) {
            self::$instance = new self($config);
        }
        return self::$instance;
    }

    public function connect(): bool
    {
        try {
            $dsn              = "mysql:host={$this->host};dbname={$this->database};port={$this->port};charset=utf8mb4";
            $this->connection = new \PDO($dsn, $this->username, $this->password, [
                \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
                \PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
            return true;
        } catch (\PDOException $e) {
            throw new \Exception("Connection failed: " . $e->getMessage());
        }
    }

    public function query(string $sql, array $params = []): \PDOStatement
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (\PDOException $e) {
            throw new \Exception("Query failed: " . $e->getMessage());
        }
    }

    public function fetch(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetch();
    }

    public function fetchAll(string $sql, array $params = []): array
    {
        return $this->query($sql, $params)->fetchAll();
    }

    public function insert(string $table, array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $values  = implode(', ', array_fill(0, count($data), '?'));
        $sql     = "INSERT INTO {$table} ({$columns}) VALUES ({$values})";

        $this->query($sql, array_values($data));
        return (int) $this->connection->lastInsertId();
    }

    public function update(string $table, array $data, string $where, array $whereParams = []): int
    {
        $set = implode(', ', array_map(fn($column) => "{$column} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";

        $params = array_merge(array_values($data), $whereParams);
        $stmt   = $this->query($sql, $params);
        return $stmt->rowCount();
    }

    public function delete(string $table, string $where, array $whereParams = []): int
    {
        $sql  = "DELETE FROM {$table} WHERE {$where}";
        $stmt = $this->query($sql, $whereParams);
        return $stmt->rowCount();
    }

    public function beginTransaction(): bool
    {
        return $this->connection->beginTransaction();
    }

    public function commit(): bool
    {
        return $this->connection->commit();
    }

    public function rollBack(): bool
    {
        return $this->connection->rollBack();
    }

    public function getConnection(): \PDO
    {
        return $this->connection;
    }
}