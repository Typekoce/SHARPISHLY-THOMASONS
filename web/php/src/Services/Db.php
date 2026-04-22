<?php

declare(strict_types=1);

namespace App\Services;

use PDO;
use PDOException;
use Throwable;

/**
 * Db - MySQL Production Service
 * Optimized for Docker internal networking.
 */
class Db
{
    private PDO $pdo;
    public $logger;

    public function __construct($logger = null)
    {
        $this->logger = $logger;
        $host = getenv('DB_HOST');
        $db   = getenv('DB_NAME');
        $user = getenv('DB_USER');
        $pass = getenv('DB_PASS');

        $dsn = "mysql:host={$host};dbname={$db};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $pass, $options);
        } catch (PDOException $e) {
            error_log("CRITICAL DATABASE ERROR: " . $e->getMessage());
            throw new \Exception("MySQL Connection Failed. Is the '127.0.0.1' container running?");
        }
    }

    /**
     * Cross-references input data against actual DB columns to prevent 1054 errors.
     */
    private function filterData(string $table, array $data): array
    {
        try {
            $stmt = $this->pdo->prepare("DESCRIBE `$table`");
            $stmt->execute();
            $existingColumns = $stmt->fetchAll(PDO::FETCH_COLUMN);

            // Return only keys that actually exist in the database table
            return array_intersect_key($data, array_flip($existingColumns));
        } catch (Throwable $e) {
            error_log("Schema Check Failed for table '$table': " . $e->getMessage());
            return $data; // Fallback
        }
    }

    /**
     * UPSERT logic with defensive column filtering.
     */
    public function save(string $table, array $data): int|bool
    {
        $data = $this->filterData($table, $data);

        if (empty($data)) {
            error_log("Db::save aborted: No valid columns found for table '$table'");
            return false;
        }

        $columns     = implode('`, `', array_keys($data));
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
    public function createTable(string $table, string|array $definition): bool
    {
        if (is_array($definition)) {
            $parts = [];
            foreach ($definition as $column => $spec) {
                $key = strtoupper((string)$column);

                if (in_array($key, ['INDEX', 'FOREIGN KEY', 'PRIMARY KEY', 'UNIQUE'], true)) {
                    $parts[] = "$key $spec";
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

    public function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
            $stmt->execute([$column]);
            return (bool)$stmt->fetch();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function find(array $params): array
    {
        $tbl    = $params['tbl'] ?? '';
        $fields = isset($params['fields']) ? implode(', ', $params['fields']) : '*';
        $where  = "";
        $values = [];

        if (!empty($params['where'])) {
            $conds = [];
            foreach ($params['where'] as $col => $val) {
                $conds[] = "`$col` = ?";
                $values[] = $val;
            }
            $where = "WHERE " . implode(' AND ', $conds);
        }

        $order = isset($params['order']) 
            ? "ORDER BY `" . key($params['order']) . "` " . current($params['order']) 
            : "";

        $limit = isset($params['limit']) 
            ? "LIMIT " . (int)$params['limit'] 
            : "";

        $sql = "SELECT $fields FROM `$tbl` $where $order $limit";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $stmt->fetchAll();
    }

    public function execute(string $sql, array $params = []): bool
    {
        return $this->pdo->prepare($sql)->execute($params);
    }

    public function lastInsertId(): string
    {
        return $this->pdo->lastInsertId();
    }

/**
 * Executes ALTER TABLE commands based on a structured array.
 * Supports ADD, MODIFY, and DROP operations safely.
 *
 * Example usage:
 * $this->db->alter([
 *     'jobs' => [
 *         'ADD' => [
 *             'processed_at' => 'TIMESTAMP NULL DEFAULT NULL',
 *             'embedding_model' => 'VARCHAR(100) DEFAULT NULL'
 *         ],
 *         'MODIFY' => [
 *             'status' => "ENUM('pending','processing','completed','failed','archived') DEFAULT 'pending'"
 *         ],
 *         'DROP' => ['old_column']
 *     ]
 * ]);
 */
public function alter(array $definitions): void
{
    foreach ($definitions as $table => $actions) {
        if (empty($actions) || !is_array($actions)) {
            continue;
        }

        $statements = [];

        foreach ($actions as $actionType => $columns) {
            $actionType = strtoupper(trim((string)$actionType));

            if (!in_array($actionType, ['ADD', 'MODIFY', 'DROP'], true)) {
                $this->logger->warning("Unknown ALTER action type: $actionType for table $table");
                continue;
            }

            if (!is_array($columns)) {
                continue;
            }

            foreach ($columns as $column => $spec) {
                if ($actionType === 'DROP') {
                    // For DROP, $column is actually the column name (key may be numeric)
                    $colName = is_string($column) ? $column : $spec;
                    $statements[] = "DROP COLUMN `$colName`";
                } 
                elseif ($actionType === 'MODIFY') {
                    $statements[] = "MODIFY COLUMN `$column` $spec";
                } 
                elseif ($actionType === 'ADD') {
                    $statements[] = "ADD COLUMN `$column` $spec";
                }
            }
        }

        if (!empty($statements)) {
            $sql = "ALTER TABLE `$table` " . implode(', ', $statements) . ";";

            try {
                $this->execute($sql);
                $this->logger->info("Database altered successfully", [
                    'table' => $table,
                    'statements' => $statements
                ]);
            } catch (\Throwable $e) {
                $this->logger->error("ALTER TABLE failed for $table", [
                    'sql' => $sql,
                    'error' => $e->getMessage()
                ]);
                throw $e;   // Re-throw so migration can fail visibly
            }
        }
    }
}
}