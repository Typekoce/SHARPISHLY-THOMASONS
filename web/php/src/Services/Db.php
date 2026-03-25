<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Exception;
use PDOException;

/**
 * Db - MySQL Production Service
 * Optimized for Docker internal networking (db:3306).
 */
class Db {
    private PDO $pdo;

    public function __construct() {
        // Pulling directly from your provided .env keys
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'sharpishly';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: 'root_password';
        
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5, // Prevent hanging on dead connections
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            // Re-throwing as a generic Exception to keep Registry errors clean
            throw new Exception("MySQL Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Automates 'CREATE TABLE' based on structured definitions.
     */
    public function createTable(string $table, string|array $definition): bool {
        if (is_array($definition)) {
            $parts = [];
            foreach ($definition as $column => $spec) {
                $parts[] = is_numeric($column) ? $spec : "`$column` $spec";
            }
            $columnSql = implode(",\n            ", $parts);
        } else {
            $columnSql = $definition;
        }

        $sql = "CREATE TABLE IF NOT EXISTS `$table` (
            $columnSql
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        return $this->execute($sql);
    }

    /**
     * Checks if a column exists (useful for zero-downtime migrations).
     */
    public function columnExists(string $table, string $column): bool {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Structured SELECT. Example: find(['tbl'=>'jobs', 'where'=>['id'=>1]])
     */
    public function find(array $params): array {
        $tbl    = $params['tbl'];
        $fields = isset($params['fields']) ? implode(', ', $params['fields']) : '*';
        $where  = "";
        $values = [];

        if (isset($params['where'])) {
            $conds = [];
            foreach ($params['where'] as $col => $val) {
                $conds[] = "`$col` = ?";
                $values[] = $val;
            }
            $where = "WHERE " . implode(' AND ', $conds);
        }

        $order = isset($params['order']) ? "ORDER BY `" . key($params['order']) . "` " . current($params['order']) : "";
        $limit = isset($params['limit']) ? "LIMIT " . (int)$params['limit'] : "";

        $sql = "SELECT $fields FROM `$tbl` $where $order $limit";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        return $stmt->fetchAll();
    }

    /**
     * UPSERT logic: Inserts new or updates existing based on Unique Keys.
     */
    public function save(string $table, array $data): int|bool {
        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `$table` (`$columns`) VALUES ($placeholders) 
                ON DUPLICATE KEY UPDATE ";
        
        $updates = [];
        foreach ($data as $col => $val) {
            $updates[] = "`$col` = VALUES(`$col`)";
        }
        $sql .= implode(', ', $updates);

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(array_values($data));
        
        $id = $this->pdo->lastInsertId();
        return $id ? (int)$id : true;
    }

    public function execute(string $sql, array $params = []): bool {
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}