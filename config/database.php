<?php
/**
 * Database connection via PDO
 */

require_once __DIR__ . '/config.php';

function get_db_connection() {
    static $pdo = null;
    if ($pdo === null) {
        // Shared hosting (InfinityFree / cPanel) optimization:
        // Avoid passing explicit port=3306 in DSN as shared hosting internal firewalls may refuse explicit port sockets
        $port_part = (!empty(DB_PORT) && DB_PORT != '3306') ? ";port=" . DB_PORT : "";
        $dsn = "mysql:host=" . DB_HOST . $port_part . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 5,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("خطأ في الاتصال بقاعدة البيانات: " . htmlspecialchars($e->getMessage()));
        }
    }
    return $pdo;
}
