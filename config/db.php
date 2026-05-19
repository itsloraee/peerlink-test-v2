<?php
// ── Chargement du .env ────────────────────────────────────
$envPath = __DIR__ . '/../.env';
if (file_exists($envPath)) {
    foreach (file($envPath) as $line) {
        $line = trim($line);
        if ($line && !str_starts_with($line, '#') && str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            putenv(trim($key) . '=' . trim($value));
        }
    }
}

// ── Connexion PDO ─────────────────────────────────────────
$host     = getenv('DB_HOST')     ?: 'localhost';
$port     = getenv('DB_PORT')     ?: '3306';
$dbname   = getenv('DB_DATABASE') ?: 'peerlink';
$username = getenv('DB_USERNAME') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('<p style="color:red;font-family:monospace;padding:1rem">Connexion BDD échouée : ' . $e->getMessage() . '</p>');
}
