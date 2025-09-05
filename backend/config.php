<?php
// config.php
declare(strict_types=1);
session_start();

ini_set('display_errors', '0'); // Turn off in production
error_reporting(E_ALL);

define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'smart_retail');
define('DB_USER', 'your_db_user');
define('DB_PASS', 'your_db_password');

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
