<?php
require_once 'config/db.php';

$sql = "
CREATE TABLE IF NOT EXISTS utilisateur (
    id_utilisateur   INT           PRIMARY KEY AUTO_INCREMENT,
    nom              VARCHAR(100)  NOT NULL,
    email            VARCHAR(150)  NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255)  NOT NULL,
    points           INT           NOT NULL DEFAULT 0,
    role             ENUM('etudiant','formateur') NOT NULL DEFAULT 'etudiant',
    date_inscription DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS technologie (
    id_technologie  INT          PRIMARY KEY AUTO_INCREMENT,
    nom_technologie VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE IF NOT EXISTS demande (
    id_demande     INT          PRIMARY KEY AUTO_INCREMENT,
    titre          VARCHAR(200) NOT NULL,
    description    TEXT         NOT NULL,
    statut         ENUM('ouverte','en_cours','terminee') NOT NULL DEFAULT 'ouverte',
    date_creation  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_demandeur   INT          NOT NULL,
    id_technologie INT          NOT NULL,
    FOREIGN KEY (id_demandeur)   REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_technologie) REFERENCES technologie(id_technologie) ON DELETE RESTRICT
);

CREATE TABLE IF NOT EXISTS reponse (
    id_utilisateur INT          NOT NULL,
    id_demande     INT          NOT NULL,
    date_reponse   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    contact        VARCHAR(100) NOT NULL,
    message        TEXT         NOT NULL,
    PRIMARY KEY (id_utilisateur, id_demande),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_demande)     REFERENCES demande(id_demande)         ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS validation (
    id_validation    INT      PRIMARY KEY AUTO_INCREMENT,
    commentaire      TEXT,
    points_attribues INT      NOT NULL DEFAULT 0,
    date_validation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur   INT      NOT NULL,
    id_demande       INT      NOT NULL,
    FOREIGN KEY (id_utilisateur, id_demande) REFERENCES reponse(id_utilisateur, id_demande) ON DELETE CASCADE
);
";

$inserts = [
    "INSERT IGNORE INTO technologie (nom_technologie) VALUES ('Laravel'),('PHP'),('MySQL'),('React'),('JavaScript'),('C++'),('HTML/CSS'),('Node.js')",
    "INSERT IGNORE INTO utilisateur (nom, email, mot_de_passe, points, role) VALUES
        ('Laurie Dupont','laurie@test.com','\$2y\$10\$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz',320,'etudiant'),
        ('Jules Martin','jules@test.com','\$2y\$10\$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz',150,'etudiant'),
        ('Sara Renaud','sara@test.com','\$2y\$10\$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz',80,'etudiant'),
        ('Marc Formateur','marc@test.com','\$2y\$10\$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz',0,'formateur')",
];

try {
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);

    foreach (explode(';', $sql) as $query) {
        $query = trim($query);
        if ($query) $pdo->exec($query);
    }

    foreach ($inserts as $insert) {
        $pdo->exec($insert);
    }

    echo '<h2 style="color:green">✅ Base de données installée avec succès !</h2>';
    echo '<p>Supprime ce fichier install.php maintenant.</p>';

} catch (PDOException $e) {
    echo '<h2 style="color:red">❌ Erreur : ' . $e->getMessage() . '</h2>';
}