<?php
/**
 * Database access via PDO with prepared statements (protects against SQL injection).
 */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);

    try {
        $pdo = new PDO($dsn, $c['user'], $c['password'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $pdo->exec("SET time_zone = '" . (new DateTime())->format('P') . "'");
    } catch (PDOException $e) {
        http_response_code(500);
        $msg = config('app.debug') ? e($e->getMessage()) : 'Database connection failed.';
        exit('<div style="font-family:system-ui;max-width:640px;margin:10vh auto;padding:24px;border-radius:12px;background:#fff4f4;color:#7a1f1f;border:1px solid #f3c2c2">'
            . '<h2 style="margin-top:0">Cannot connect to the database</h2><p>' . $msg . '</p>'
            . '<p>Make sure MySQL is running and that you imported <code>database/schema.sql</code>. '
            . 'Credentials live in <code>config/config.php</code>.</p></div>');
    }
    return $pdo;
}

/** Run a query and return the statement. */
function db_query(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_all(string $sql, array $params = []): array
{
    return db_query($sql, $params)->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $row = db_query($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = []): mixed
{
    $v = db_query($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

/** Run a callback inside a transaction; rolls back on any exception. */
function db_transaction(callable $fn): mixed
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $result = $fn($pdo);
        $pdo->commit();
        return $result;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
