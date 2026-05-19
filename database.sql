-- ============================================================
--  PeerLink — Schéma + données de test
--  Hackathon CDA x DW — Mai 2026
-- ============================================================

CREATE DATABASE IF NOT EXISTS peerlink
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE peerlink;

CREATE TABLE utilisateur (
    id_utilisateur   INT           PRIMARY KEY AUTO_INCREMENT,
    nom              VARCHAR(100)  NOT NULL,
    email            VARCHAR(150)  NOT NULL UNIQUE,
    mot_de_passe     VARCHAR(255)  NOT NULL,
    points           INT           NOT NULL DEFAULT 0,
    role             ENUM('etudiant','formateur') NOT NULL DEFAULT 'etudiant',
    date_inscription DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE technologie (
    id_technologie  INT          PRIMARY KEY AUTO_INCREMENT,
    nom_technologie VARCHAR(100) NOT NULL UNIQUE
);

CREATE TABLE demande (
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

CREATE TABLE reponse (
    id_utilisateur INT          NOT NULL,
    id_demande     INT          NOT NULL,
    date_reponse   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    contact        VARCHAR(100) NOT NULL,
    message        TEXT         NOT NULL,
    PRIMARY KEY (id_utilisateur, id_demande),
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur(id_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (id_demande)     REFERENCES demande(id_demande)         ON DELETE CASCADE
);

CREATE TABLE validation (
    id_validation    INT      PRIMARY KEY AUTO_INCREMENT,
    commentaire      TEXT,
    points_attribues INT      NOT NULL DEFAULT 0,
    date_validation  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    id_utilisateur   INT      NOT NULL,
    id_demande       INT      NOT NULL,
    FOREIGN KEY (id_utilisateur, id_demande) REFERENCES reponse(id_utilisateur, id_demande) ON DELETE CASCADE
);

-- ── Données de test ──────────────────────────────────────

INSERT INTO technologie (nom_technologie) VALUES
    ('Laravel'),('PHP'),('MySQL'),('React'),('JavaScript'),('C++'),('HTML/CSS'),('Node.js');

INSERT INTO utilisateur (nom, email, mot_de_passe, points, role) VALUES
    ('Laurie Dupont',  'laurie@test.com',  '$2y$10$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz', 320, 'etudiant'),
    ('Jules Martin',   'jules@test.com',   '$2y$10$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz', 150, 'etudiant'),
    ('Sara Renaud',    'sara@test.com',    '$2y$10$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz', 80,  'etudiant'),
    ('Marc Formateur', 'marc@test.com',    '$2y$10$abcdefghijklmnopqrstuuVGZzQpIb5wAkFgHjKlMnOpQrStUvWxYz', 0,   'formateur');

INSERT INTO demande (titre, description, statut, id_demandeur, id_technologie) VALUES
    ('Token Sanctum expire trop vite',        'Mon token expire en quelques minutes alors que j'ai mis 60 dans sanctum.php.', 'en_cours', 1, 1),
    ('LEFT JOIN avec lignes dupliquées',       'Je fais un LEFT JOIN sur 3 tables et j'obtiens des doublons inexpliqués.',      'ouverte',  2, 3),
    ('Pointeur NULL en C++ — segfault',        'Mon programme crash avec segfault dès que j'accède à mon pointeur.',             'ouverte',  3, 6),
    ('Déploiement Railway — variables d'env', 'Mes variables d'environnement ne sont pas lues en production sur Railway.',      'terminee', 1, 2);

INSERT INTO reponse (id_utilisateur, id_demande, contact, message) VALUES
    (2, 1, 'jules#4521 sur Discord', 'Tu dois utiliser le header Authorization: Bearer au lieu du cookie. Postman n'envoie pas les cookies de session par défaut.'),
    (3, 1, 'sara.r@email.com',       'Vérifie aussi que tu as bien lancé php artisan config:clear après la modif.'),
    (1, 4, 'laurie#1234 sur Discord','Il faut aller dans Settings > Environment Variables sur Railway et les ajouter manuellement.');

INSERT INTO validation (commentaire, points_attribues, id_utilisateur, id_demande) VALUES
    ('Super réponse, ça a résolu mon problème !', 150, 2, 1),
    ('Merci beaucoup, c'était bien ça !',          100, 1, 4);
