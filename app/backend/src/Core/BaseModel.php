<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class BaseModel {
    protected $pdo;

    public function __construct() {
        $host = $_ENV['DB_HOST'];
        $dbname = $_ENV['DB_NAME'];
        $user = $_ENV['DB_USER'];
        $password = $_ENV['DB_PASSWORD'];
        $charset = $_ENV['DB_CHARSET'];

        if (!$host || !$dbname || !$user || !$password || !$charset) {
            throw new Exception('Database configuration is not set properly.');
        }

        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $user, $password, $options);
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage(), (int)$e->getCode());
        }
    }

    /**
     * Parse pagination parameters from the query string.
     * @param int $defaultLimit Default items per page
     * @param int $maxLimit     Maximum allowed items per page
     * @return array{page: int, limit: int, offset: int}
     */
    protected function getPaginationParams(int $defaultLimit = 20, int $maxLimit = 100): array {
        $page  = max(1, (int)($_GET['page']  ?? 1));
        $limit = max(1, min($maxLimit, (int)($_GET['limit'] ?? $defaultLimit)));
        $offset = ($page - 1) * $limit;
        return ['page' => $page, 'limit' => $limit, 'offset' => $offset];
    }

    /**
     * Wrap rows in a standard paginated response envelope.
     * @param array $rows  The result rows for the current page
     * @param int   $total Total number of rows (before LIMIT)
     * @param int   $page  Current page number
     * @param int   $limit Items per page
     * @return array
     */
    protected function paginatedResponse(array $rows, int $total, int $page, int $limit): array {
        return [
            'data'       => $rows,
            'page'       => $page,
            'limit'      => $limit,
            'total'      => $total,
            'totalPages' => (int)ceil($total / $limit),
        ];
    }
}