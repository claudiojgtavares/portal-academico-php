<?php

require_once __DIR__ . '/app.php';

$host = env_value('DB_HOST', '127.0.0.1');
$port = env_value('DB_PORT', '3306');
$dbname = env_value('DB_NAME', 'portal_academico');
$username = env_value('DB_USER', 'root');
$password = env_value('DB_PASSWORD', '');

try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $error) {
    error_log('Database connection failed: ' . $error->getMessage());
    http_response_code(503);
    exit('Serviço temporariamente indisponível. Confirme a configuração da base de dados.');
}
