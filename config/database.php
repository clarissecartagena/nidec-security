<?php

// Database connection helper (PDO)
// Configure these for your local XAMPP MySQL.

if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'nidec_security');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');
if (!defined('DB_PORT')) define('DB_PORT', 3306);

function db(): PDO {
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (Throwable $e) {
        throw new RuntimeException('Database connection failed. Please check your database configuration.', 0, $e);
    }

    return $pdo;
}

function db_fetch_all(string $sql, string $types = '', array $params = []): array {
    $stmt = db_prepare($sql, $types, $params);
    $stmt->execute();
    return $stmt->fetchAll() ?: [];
}

function db_fetch_one(string $sql, string $types = '', array $params = []): ?array {
    $stmt = db_prepare($sql, $types, $params);
    $stmt->execute();
    $row = $stmt->fetch();
    return $row ?: null;
}

function db_execute(string $sql, string $types = '', array $params = []): int {
    $stmt = db_prepare($sql, $types, $params);
    $stmt->execute();
    return $stmt->rowCount();
}

function db_prepare(string $sql, string $types = '', array $params = []): PDOStatement {
    $pdo = db();
    $stmt = $pdo->prepare($sql);

    // For compatibility with previous mysqli helpers: accept $types but ignore it.
    // Use positional placeholders (?) with $params array.
    foreach (array_values($params) as $i => $val) {
        $paramType = PDO::PARAM_STR;
        if (is_int($val)) $paramType = PDO::PARAM_INT;
        elseif (is_bool($val)) $paramType = PDO::PARAM_BOOL;
        elseif ($val === null) $paramType = PDO::PARAM_NULL;

        $stmt->bindValue($i + 1, $val, $paramType);
    }

    return $stmt;
}

function db_last_insert_id(): string {
    return db()->lastInsertId();
}
