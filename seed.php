<?php
require_once 'config/db.php';

try {
    $pdo->exec("INSERT IGNORE INTO demande (titre, description, statut, id_demandeur, id_technologie) VALUES
        ('Token Sanctum expire trop vite', 'Mon token expire en quelques minutes alors que j''ai mis 60 dans sanctum.php.', 'en_cours', 1, 1),
        ('LEFT JOIN avec lignes dupliquees', 'Je fais un LEFT JOIN sur 3 tables et j''obtiens des doublons inexpliques.', 'ouverte', 2, 3),
        ('Pointeur NULL en C++ segfault', 'Mon programme crash avec segfault des que j''accede a mon pointeur.', 'ouverte', 3, 6),
        ('Deploiement Railway variables d env', 'Mes variables d environnement ne sont pas lues en production sur Railway.', 'terminee', 1, 2)");

    $pdo->exec("INSERT IGNORE INTO reponse (id_utilisateur, id_demande, contact, message) VALUES
        (2, 1, 'jules#4521 sur Discord', 'Tu dois utiliser le header Authorization Bearer au lieu du cookie.'),
        (3, 1, 'sara.r@email.com', 'Verifie aussi que tu as bien lance php artisan config:clear apres la modif.'),
        (1, 4, 'laurie#1234 sur Discord', 'Il faut aller dans Settings > Environment Variables et les ajouter manuellement.')");

    $pdo->exec("INSERT IGNORE INTO validation (commentaire, points_attribues, id_utilisateur, id_demande) VALUES
        ('Super reponse, ca a resolu mon probleme !', 150, 2, 1),
        ('Merci beaucoup, c etait bien ca !', 100, 1, 4)");

    echo '<h2 style="color:green">✅ Données de test insérées !</h2>';
    echo '<p>Supprime seed.php maintenant.</p>';

} catch (PDOException $e) {
    echo '<h2 style="color:red">❌ ' . $e->getMessage() . '</h2>';
}