<?php
declare(strict_types=1);

namespace App\Services;

use PDO;
use Exception;
use PDOException;
use Throwable;

/**
 * Db - MySQL Production Service
 * Optimized for Docker internal networking with Defensive Schema Mapping.
 */
class Db {
    private PDO $pdo;

    public function __construct() {
        $host = getenv('DB_HOST') ?: 'db';
        $db   = getenv('DB_NAME') ?: 'sharpishly';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: 'root_password';
        
        $dsn  = "mysql:host=$host;dbname=$db;charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            throw new Exception("MySQL Connection Failed: " . $e->getMessage());
        }
    }

    /**
     * Cross-references input data against actual DB columns to prevent 1054 errors.
     */
    private function filterData(string $table, array $data): array {
        try {
            $stmt = $this->pdo->prepare("DESCRIBE `$table` ");
            $stmt->execute();
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Return only keys that actually exist in the database table
            return array_intersect_key($data, array_flip($existingColumns));
        } catch (Throwable $e) {
            error_log("Schema Check Failed for $table: " . $e->getMessage());
            return $data; // Fallback to original if DESCRIBE fails
        }
    }

    /**
     * UPSERT logic: Now filtered against the "Source of Truth" (the table schema).
     */
    public function save(string $table, array $data): int|bool {
        // DEFENSIVE: Remove columns that do not exist in the database
        $data = $this->filterData($table, $data);

        if (empty($data)) {
            error_log("Db::save aborted: No valid columns found for table '$table'");
            return false;
        }

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

    public function columnExists(string $table, string $column): bool {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch();
        } catch (Exception $e) {
            return false;
        }
    }

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

    public function execute(string $sql, array $params = []): bool {
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}