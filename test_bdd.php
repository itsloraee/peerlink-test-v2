<?php
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=peerlink;charset=utf8mb4",
        "root",
        "123456"
    );
    echo "Connexion OK !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}