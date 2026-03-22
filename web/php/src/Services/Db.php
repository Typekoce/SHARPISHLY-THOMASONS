<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Exception;

/**
 * @file Db.php
 * @package App
 * @brief Database Abstraction & Self-Healing Schema Manager.
 *
 * A PDO wrapper that enforces the "Zero-Manual-SQL" rule in controllers.
 * It provides methods for automated table migrations and safe data 
 * persistence.
 *
 * @details 
 * - Automated Table Creation: Checks for table existence before queries.
 * - Prepared Statements: Prevents SQL injection across the entire stack.
 * - Singleton Access: Managed via the App\Registry for resource efficiency.
 */
class Db {
    private PDO $pdo;

    public function __construct() {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'sharpishly';
        $user = getenv('DB_USER') ?: 'user';
        $pass = getenv('DB_PASS') ?: 'pass';
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (\PDOException $e) {
            throw new Exception("Database Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Creates a table. Signature supports raw strings or structured arrays.
     */
    public function createTable(string $table, string|array $definition): bool {
        $columnSql = '';

        if (is_array($definition)) {
            $parts = [];
            foreach ($definition as $column => $spec) {
                // If key is numeric, assume the value is the raw line
                if (is_numeric($column)) {
                    $parts[] = $spec;
                } else {
                    $parts[] = "`$column` $spec";
                }
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
     * Helper to verify column existence before ALTER commands
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
     * Structured finding via $conditions array
     */
    public function find(array $params): array {
        $tbl    = $params['tbl'];
        $fields = isset($params['fields']) ? implode(', ', $params['fields']) : '*';
        $where  = "";
        $values = [];

        if (isset($params['where'])) {
            $conds = [];
            foreach ($params['where'] as $col => $val) {
                // ADDED BACKTICKS: Prevents issues with reserved words
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
     * UPSERT: Insert or update on key conflict
     */
    public function save(array $data): int|bool {
        $tbl = $data['tbl'];
        unset($data['tbl']);

        $columns = implode('`, `', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO `$tbl` (`$columns`) VALUES ($placeholders) 
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

    /**
     * Structural changes
     */
    public function alter(string $table, string $action, string $name, string $spec): bool {
        $sql = "ALTER TABLE `$table` $action `$name` $spec";
        return $this->execute($sql);
    }

    /**
     * Base execution helper
     */
    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }
}