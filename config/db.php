<?php
$host     = getenv('DB_HOST')     ?: 'peerlink-db-peerlink.h.aivencloud.com';
$port     = getenv('DB_PORT')     ?: '25841';
$dbname   = getenv('DB_DATABASE') ?: 'defaultdb';
$username = getenv('DB_USERNAME') ?: 'avnadmin';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch (PDOException $e) {
    die('Connexion BDD échouée : ' . $e->getMessage());
}