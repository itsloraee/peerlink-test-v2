<?php
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: browse.php'); exit; }

// Traitement d'une nouvelle réponse
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = trim($_POST['message'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $nom     = trim($_POST['nom']     ?? '');

    if ($message && $contact && $nom) {
        // Créer un utilisateur anonyme pour l'aidant
        $stmt = $pdo->prepare("
            INSERT INTO utilisateur (nom, email, mot_de_passe)
            VALUES (:nom, :email, :mdp)
        ");
        $stmt->execute([
            ':nom'   => $nom,
            ':email' => $nom . '_' . uniqid() . '@anonymous.local',
            ':mdp'   => password_hash(uniqid(), PASSWORD_BCRYPT),
        ]);
        $id_user = $pdo->lastInsertId();

        $stmt = $pdo->prepare("
            INSERT INTO reponse (id_utilisateur, id_demande, contact, message)
            VALUES (:id_user, :id_demande, :contact, :message)
        ");
        $stmt->execute([
            ':id_user'   => $id_user,
            ':id_demande'=> $id,
            ':contact'   => $contact,
            ':message'   => $message,
        ]);

        // Passer la demande en "en_cours" si elle était ouverte
        $pdo->prepare("
            UPDATE demande SET statut = 'en_cours'
            WHERE id_demande = :id AND statut = 'ouverte'
        ")->execute([':id' => $id]);

        header("Location: demande.php?id=$id");
        exit;
    }
}

// Récupérer la demande
$stmtD = $pdo->prepare("
    SELECT d.*, u.nom AS nom_demandeur, t.nom_technologie
    FROM demande d
    JOIN utilisateur u ON d.id_demandeur = u.id_utilisateur
    JOIN technologie t ON d.id_technologie = t.id_technologie
    WHERE d.id_demande = :id
");
$stmtD->execute([':id' => $id]);
$demande = $stmtD->fetch();
if (!$demande) { header('Location: browse.php'); exit; }

// Récupérer les réponses
$stmtR = $pdo->prepare("
    SELECT r.*, u.nom AS nom_aidant,
           v.points_attribues, v.commentaire
    FROM reponse r
    JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur
    LEFT JOIN validation v ON v.id_utilisateur = r.id_utilisateur
                           AND v.id_demande = r.id_demande
    WHERE r.id_demande = :id
    ORDER BY r.date_reponse ASC
");
$stmtR->execute([':id' => $id]);
$reponses = $stmtR->fetchAll();

$badgeClass = match($demande['statut']) {
    'ouverte'  => 'badge-open',
    'en_cours' => 'badge-cours',
    default    => 'badge-done'
};
$badgeLabel = match($demande['statut']) {
    'ouverte'  => 'Ouverte',
    'en_cours' => 'En cours',
    default    => 'Résolue'
};

// Initiales demandeur
$mots      = explode(' ', trim($demande['nom_demandeur']));
$initiales = strtoupper(implode('', array_map(fn($w) => $w[0], $mots)));
$initiales = substr($initiales, 0, 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — <?= htmlspecialchars($demande['titre']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom px-4">
    <a class="navbar-brand fw-semibold" href="browse.php">
        <span class="logo-dot"></span>PeerLink
    </a>
    <a href="create_request.php" class="btn btn-sm btn-peer">+ Poster une demande</a>
</nav>

<div class="container py-4" style="max-width:760px">

    <a href="browse.php" class="btn btn-sm btn-outline-secondary mb-3">← Retour</a>

    <!-- Détail de la demande -->
    <div class="peer-card mb-4">
        <div class="d-flex justify-content-between align-items-start mb-2">
            <span class="badge-tech"><?= htmlspecialchars($demande['nom_technologie']) ?></span>
            <span class="<?= $badgeClass ?>"><?= $badgeLabel ?></span>
        </div>
        <h1 class="h5 my-2"><?= htmlspecialchars($demande['titre']) ?></h1>
        <div class="d-flex gap-3 mb-3" style="font-size:12px;color:#6b7280">
            <span>👤 <?= htmlspecialchars($demande['nom_demandeur']) ?></span>
            <span>🕐 <?= date('d/m/Y à H:i', strtotime($demande['date_creation'])) ?></span>
        </div>
        <div class="p-3 rounded" style="background:#f8f9fa;font-size:13px;line-height:1.6;color:#374151">
            <?= nl2br(htmlspecialchars($demande['description'])) ?>
        </div>
    </div>

    <!-- Réponses -->
    <h2 class="h6 mb-3">
        <?= count($reponses) ?> réponse<?= count($reponses) > 1 ? 's' : '' ?>
    </h2>

    <?php if (empty($reponses)) : ?>
        <div class="peer-card text-secondary text-center py-4" style="font-size:13px">
            Aucune réponse pour l'instant — sois le premier à aider !
        </div>
    <?php else : ?>
        <?php foreach ($reponses as $r) :
            $mots2 = explode(' ', trim($r['nom_aidant']));
            $init2 = strtoupper(implode('', array_map(fn($w) => $w[0], $mots2)));
            $init2 = substr($init2, 0, 2);
        ?>
        <div class="peer-card mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div class="avatar"><?= htmlspecialchars($init2) ?></div>
                    <div>
                        <div style="font-size:13px;font-weight:500">
                            <?= htmlspecialchars($r['nom_aidant']) ?>
                        </div>
                        <div style="font-size:11px;color:#6b7280">
                            <?= date('d/m/Y à H:i', strtotime($r['date_reponse'])) ?>
                            <?php if ($r['points_attribues']) : ?>
                                · <span style="color:#059669">+<?= $r['points_attribues'] ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php if ($r['points_attribues']) : ?>
                    <span class="badge-pts">✅ Validée</span>
                <?php endif; ?>
            </div>
            <p style="font-size:13px;color:#374151;line-height:1.6;margin-bottom:8px">
                <?= nl2br(htmlspecialchars($r['message'])) ?>
            </p>
            <div style="font-size:12px;color:#6b7280">
                📬 <?= htmlspecialchars($r['contact']) ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Formulaire de réponse -->
    <?php if ($demande['statut'] !== 'terminee') : ?>
        <div class="peer-card mt-4">
            <h3 class="h6 mb-3">Apporter ton aide</h3>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;font-weight:500">Ton pseudo</label>
                    <input type="text" name="nom" class="form-control form-control-sm"
                           placeholder="ton_pseudo" required />
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;font-weight:500">Ta réponse</label>
                    <textarea name="message" class="form-control form-control-sm" rows="4"
                              placeholder="Décris ta solution ou ta piste..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" style="font-size:13px;font-weight:500">Comment te contacter ?</label>
                    <input type="text" name="contact" class="form-control form-control-sm"
                           placeholder="Discord, email, GitHub..." required />
                </div>
                <button type="submit" class="btn btn-sm btn-peer">Envoyer ma réponse</button>
            </form>
        </div>
    <?php endif; ?>

</div>
</body>
</html>
