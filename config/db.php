<?php
// Charger le .env si disponible (local)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        [$key, $val] = explode('=', $line, 2);
        $_ENV[trim($key)] = trim($val);
    }
}

$host     = $_ENV['DB_HOST']     ?? getenv('DB_HOST')     ?: 'localhost';
$port     = $_ENV['DB_PORT']     ?? getenv('DB_PORT')     ?: '3306';
$dbname   = $_ENV['DB_DATABASE'] ?? getenv('DB_DATABASE') ?: 'peerlink';
$username = $_ENV['DB_USERNAME'] ?? getenv('DB_USERNAME') ?: 'root';
$password = $_ENV['DB_PASSWORD'] ?? getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE                      => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE           => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ]
    );
} catch (PDOException $e) {
    die('Connexion BDD échouée : ' . $e->getMessage());
}