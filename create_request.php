<?php
require_once 'config/db.php';

$erreurs = [];
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $titre       = trim($_POST['titre']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_techno   = (int) ($_POST['id_technologie'] ?? 0);
    $nom         = trim($_POST['nom']         ?? '');
    $email       = trim($_POST['email']       ?? '');

    // Validation
    if (!$titre)       $erreurs[] = 'Le titre est obligatoire.';
    if (!$description) $erreurs[] = 'La description est obligatoire.';
    if (!$id_techno)   $erreurs[] = 'Choisis une technologie.';
    if (!$nom)         $erreurs[] = 'Le pseudo est obligatoire.';

    if (empty($erreurs)) {
        // Récupérer ou créer l'utilisateur selon l'email
        if ($email) {
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existant = $stmt->fetch();

            if ($existant) {
                $id_user = $existant['id_utilisateur'];
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO utilisateur (nom, email, mot_de_passe)
                    VALUES (:nom, :email, :mdp)
                ");
                $stmt->execute([
                    ':nom'   => $nom,
                    ':email' => $email,
                    ':mdp'   => password_hash(uniqid(), PASSWORD_BCRYPT),
                ]);
                $id_user = $pdo->lastInsertId();
            }
        } else {
            // Sans email : créer un utilisateur anonyme avec le pseudo
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
        }

        // Insérer la demande
        $stmt = $pdo->prepare("
            INSERT INTO demande (titre, description, id_demandeur, id_technologie)
            VALUES (:titre, :description, :id_user, :id_techno)
        ");
        $stmt->execute([
            ':titre'       => $titre,
            ':description' => $description,
            ':id_user'     => $id_user,
            ':id_techno'   => $id_techno,
        ]);

        $id_demande = $pdo->lastInsertId();
        header("Location: demande.php?id=$id_demande");
        exit;
    }
}

// Technos pour le select
$technos = $pdo->query("SELECT * FROM technologie ORDER BY nom_technologie")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — Poster une demande</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <link rel="stylesheet" href="css/style.css" />
</head>
<body>

<nav class="navbar navbar-light bg-white border-bottom px-4">
    <a class="navbar-brand fw-semibold" href="browse.php">
        <span class="logo-dot"></span>PeerLink
    </a>
</nav>

<div class="container py-4" style="max-width:560px">

    <a href="browse.php" class="btn btn-sm btn-outline-secondary mb-3">← Retour</a>
    <h1 class="h5 mb-4">Poster une demande d'aide</h1>

    <?php if (!empty($erreurs)) : ?>
        <div class="alert alert-danger" style="font-size:13px">
            <?php foreach ($erreurs as $e) echo "<div>• $e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="peer-card">

        <div class="mb-3">
            <label class="form-label fw-500" style="font-size:13px;font-weight:500">
                Technologie concernée
            </label>
            <select name="id_technologie" class="form-select form-select-sm" required>
                <option value="">Choisir une technologie...</option>
                <?php foreach ($technos as $t) : ?>
                    <option value="<?= $t['id_technologie'] ?>"
                        <?= (int)($_POST['id_technologie'] ?? 0) === (int)$t['id_technologie'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nom_technologie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label" style="font-size:13px;font-weight:500">Titre de la demande</label>
            <div class="text-secondary mb-1" style="font-size:12px">Sois précis — ex : "Token Sanctum expire trop vite"</div>
            <input type="text" name="titre" class="form-control form-control-sm"
                   placeholder="Mon problème en une phrase..."
                   value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required />
        </div>

        <div class="mb-3">
            <label class="form-label" style="font-size:13px;font-weight:500">Description</label>
            <div class="text-secondary mb-1" style="font-size:12px">Contexte, ce que tu as essayé, comportement attendu.</div>
            <textarea name="description" class="form-control form-control-sm" rows="5"
                      placeholder="J'essaie de... mais j'obtiens... J'ai déjà tenté..." required><?=
                htmlspecialchars($_POST['description'] ?? '')
            ?></textarea>
        </div>

        <hr class="my-3" />
        <div class="text-secondary mb-3" style="font-size:12px;font-weight:500">Ton identité</div>

        <div class="row g-3 mb-3">
            <div class="col-6">
                <label class="form-label" style="font-size:13px;font-weight:500">Pseudo</label>
                <input type="text" name="nom" class="form-control form-control-sm"
                       placeholder="ton_pseudo"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required />
            </div>
            <div class="col-6">
                <label class="form-label" style="font-size:13px;font-weight:500">
                    Email <span class="text-secondary fw-normal">(optionnel)</span>
                </label>
                <input type="email" name="email" class="form-control form-control-sm"
                       placeholder="pour être notifié"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-peer">Poster la demande</button>
            <a href="browse.php" class="btn btn-sm btn-outline-secondary">Annuler</a>
        </div>

    </form>
</div>
</body>
</html>
