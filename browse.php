<?php
require_once 'config/db.php';

// ── Filtre par technologie ────────────────────────────────
$filtre = isset($_GET['techno']) ? (int) $_GET['techno'] : 0;

// Stats globales
$stats = $pdo->query("
    SELECT
        SUM(statut = 'ouverte')  AS ouvertes,
        SUM(statut = 'terminee') AS terminees,
        COUNT(*)                 AS total
    FROM demande
")->fetch();

// Liste des technos pour les filtres
$technos = $pdo->query("SELECT * FROM technologie ORDER BY nom_technologie")->fetchAll();

// Liste des demandes
$sql = "
    SELECT d.*, u.nom AS nom_demandeur, t.nom_technologie,
           (SELECT COUNT(*) FROM reponse r WHERE r.id_demande = d.id_demande) AS nb_reponses
    FROM demande d
    JOIN utilisateur u ON d.id_demandeur = u.id_utilisateur
    JOIN technologie t ON d.id_technologie = t.id_technologie
";
if ($filtre) {
    $sql .= " WHERE d.id_technologie = :filtre";
}
$sql .= " ORDER BY d.date_creation DESC";

$stmtDemandes = $pdo->prepare($sql);
if ($filtre) $stmtDemandes->bindValue(':filtre', $filtre, PDO::PARAM_INT);
$stmtDemandes->execute();
$demandes = $stmtDemandes->fetchAll();

// Visiteurs (compteur simple en session)
session_start();
if (!isset($_SESSION['visite'])) {
    $_SESSION['visite'] = true;
    $pdo->exec("UPDATE technologie SET id_technologie = id_technologie"); // placeholder
    // En prod : INSERT ou UPDATE dans une table compteur_visiteurs
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — Demandes d'aide</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar navbar-light bg-white border-bottom px-4">
    <a class="navbar-brand fw-semibold" href="browse.php">
        <span class="logo-dot"></span>PeerLink
    </a>
    <div class="d-flex gap-2">
        <a href="profile.php?id=1" class="btn btn-sm btn-outline-secondary">Mon profil</a>
        <a href="create_request.php" class="btn btn-sm btn-peer">Poster une demande</a>
    </div>
</nav>

<div class="container py-4" style="max-width:760px">

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Demandes ouvertes</div>
                <div class="stat-value"><?= $stats['ouvertes'] ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Résolues</div>
                <div class="stat-value"><?= $stats['terminees'] ?></div>
            </div>
        </div>
        <div class="col-4">
            <div class="stat-card">
                <div class="stat-label">Total demandes</div>
                <div class="stat-value"><?= $stats['total'] ?></div>
            </div>
        </div>
    </div>

    <!-- Header + filtres -->
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="h6 mb-0">Demandes d'aide</h1>
        <a href="create_request.php" class="btn btn-sm btn-peer">+ Poster une demande</a>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-3">
        <a href="browse.php" class="chip <?= !$filtre ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($technos as $t) : ?>
            <a href="browse.php?techno=<?= $t['id_technologie'] ?>"
               class="chip <?= $filtre === (int)$t['id_technologie'] ? 'active' : '' ?>">
                <?= htmlspecialchars($t['nom_technologie']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Liste des demandes -->
    <?php if (empty($demandes)) : ?>
        <div class="peer-card text-center text-secondary py-5">
            Aucune demande pour l'instant.
            <a href="create_request.php" class="d-block mt-2 text-decoration-none" style="color:#4F46E5">
                Sois le premier à en poster une !
            </a>
        </div>
    <?php else : ?>
        <?php foreach ($demandes as $d) :
            $badgeClass = match($d['statut']) {
                'ouverte'  => 'badge-open',
                'en_cours' => 'badge-cours',
                default    => 'badge-done'
            };
            $badgeLabel = match($d['statut']) {
                'ouverte'  => 'Ouverte',
                'en_cours' => 'En cours',
                default    => 'Résolue'
            };
        ?>
        <a href="demande.php?id=<?= $d['id_demande'] ?>" class="demande-card">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <div class="fw-500" style="font-size:14px;font-weight:500">
                    <?= htmlspecialchars($d['titre']) ?>
                </div>
                <span class="<?= $badgeClass ?> ms-2"><?= $badgeLabel ?></span>
            </div>
            <div class="text-secondary mb-2" style="font-size:13px">
                <?= htmlspecialchars(mb_substr($d['description'], 0, 120)) ?>…
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap" style="font-size:12px;color:#6b7280">
                <span class="badge-tech"><?= htmlspecialchars($d['nom_technologie']) ?></span>
                <span>👤 <?= htmlspecialchars($d['nom_demandeur']) ?></span>
                <span>🕐 <?= date('d/m/Y', strtotime($d['date_creation'])) ?></span>
                <span>💬 <?= $d['nb_reponses'] ?> réponse<?= $d['nb_reponses'] > 1 ? 's' : '' ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>
