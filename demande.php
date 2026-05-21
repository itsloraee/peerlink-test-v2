<?php
require_once 'config/db.php';

function techStyle(string $nom): string {
    $map = [
        'php'        => ['#dbeafe','#1d4ed8','#bfdbfe'],
        'laravel'    => ['#fee2e2','#b91c1c','#fecaca'],
        'javascript' => ['#fef9c3','#854d0e','#fde68a'],
        'js'         => ['#fef9c3','#854d0e','#fde68a'],
        'typescript' => ['#dbeafe','#1e40af','#bfdbfe'],
        'react'      => ['#cffafe','#0e7490','#a5f3fc'],
        'vue'        => ['#dcfce7','#15803d','#bbf7d0'],
        'python'     => ['#e0f2fe','#0369a1','#bae6fd'],
        'css'        => ['#ede9fe','#6d28d9','#ddd6fe'],
        'html'       => ['#ffedd5','#c2410c','#fed7aa'],
        'node'       => ['#dcfce7','#166534','#bbf7d0'],
        'mysql'      => ['#fff7ed','#c2410c','#fed7aa'],
        'sql'        => ['#f0fdf4','#166534','#bbf7d0'],
        'docker'     => ['#dbeafe','#1e40af','#bfdbfe'],
        'git'        => ['#fee2e2','#991b1b','#fecaca'],
        'symfony'    => ['#f5f3ff','#5b21b6','#ede9fe'],
        'angular'    => ['#fee2e2','#9f1239','#fecaca'],
        'java'       => ['#fff7ed','#9a3412','#fed7aa'],
        'spring'     => ['#dcfce7','#166534','#bbf7d0'],
        'go'         => ['#cffafe','#164e63','#a5f3fc'],
        'rust'       => ['#ffedd5','#7c2d12','#fed7aa'],
        'c#'         => ['#ede9fe','#5b21b6','#ddd6fe'],
        'swift'      => ['#fff7ed','#c2410c','#fed7aa'],
    ];
    $key = strtolower($nom);
    foreach ($map as $k => $c) {
        if (str_contains($key, $k)) return "background:{$c[0]};color:{$c[1]};border:1px solid {$c[2]}";
    }
    $palettes = [['#f3e8ff','#7e22ce','#e9d5ff'],['#fce7f3','#9d174d','#fbcfe8'],['#ecfdf5','#065f46','#a7f3d0'],['#eff6ff','#1e40af','#bfdbfe'],['#fef9c3','#854d0e','#fde68a']];
    $c = $palettes[abs(crc32($nom)) % count($palettes)];
    return "background:{$c[0]};color:{$c[1]};border:1px solid {$c[2]}";
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: browse.php'); exit; }

// ── Actions POST ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Supprimer la demande
    if ($action === 'delete_demande') {
        $pdo->prepare("DELETE FROM demande WHERE id_demande = :id")->execute([':id' => $id]);
        header('Location: browse.php');
        exit;
    }

    // Modifier la demande
    if ($action === 'edit_demande') {
        $titre       = trim($_POST['titre'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $id_techno   = (int) ($_POST['id_technologie'] ?? 0);
        if ($titre && $description && $id_techno) {
            $pdo->prepare("UPDATE demande SET titre=:t, description=:d, id_technologie=:tech WHERE id_demande=:id")
                ->execute([':t' => $titre, ':d' => $description, ':tech' => $id_techno, ':id' => $id]);
        }
        header("Location: demande.php?id=$id");
        exit;
    }

    // Accepter une réponse → résoudre la demande + attribuer des points
    if ($action === 'accept_reponse') {
        $id_user = (int) ($_POST['id_utilisateur'] ?? 0);
        $points  = 100;
        if ($id_user) {
            // Marquer la demande comme terminée
            $pdo->prepare("UPDATE demande SET statut='terminee' WHERE id_demande=:id")
                ->execute([':id' => $id]);
            // Insérer ou mettre à jour la validation
            $pdo->prepare("
                INSERT INTO validation (id_utilisateur, id_demande, points_attribues)
                VALUES (:u, :d, :pts)
                ON DUPLICATE KEY UPDATE points_attribues = :pts
            ")->execute([':u' => $id_user, ':d' => $id, ':pts' => $points]);
            // Ajouter les points à l'utilisateur
            $pdo->prepare("UPDATE utilisateur SET points = points + :pts WHERE id_utilisateur = :u")
                ->execute([':pts' => $points, ':u' => $id_user]);
        }
        header("Location: demande.php?id=$id");
        exit;
    }

    // Supprimer une réponse
    if ($action === 'delete_reponse') {
        $id_user = (int) ($_POST['id_utilisateur'] ?? 0);
        $pdo->prepare("DELETE FROM reponse WHERE id_utilisateur=:u AND id_demande=:d")
            ->execute([':u' => $id_user, ':d' => $id]);
        header("Location: demande.php?id=$id");
        exit;
    }

    // Nouvelle réponse
    if ($action === 'new_reponse') {
        $message = trim($_POST['message'] ?? '');
        $contact = trim($_POST['contact'] ?? '');
        $nom     = trim($_POST['nom']     ?? '');
        if ($message && $contact && $nom) {
            $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe) VALUES (:nom, :email, :mdp)");
            $stmt->execute([':nom' => $nom, ':email' => $nom . '_' . uniqid() . '@anonymous.local', ':mdp' => password_hash(uniqid(), PASSWORD_BCRYPT)]);
            $id_user = $pdo->lastInsertId();
            $pdo->prepare("INSERT INTO reponse (id_utilisateur, id_demande, contact, message) VALUES (:u, :d, :c, :m)")
                ->execute([':u' => $id_user, ':d' => $id, ':c' => $contact, ':m' => $message]);
            $pdo->prepare("UPDATE demande SET statut='en_cours' WHERE id_demande=:id AND statut='ouverte'")->execute([':id' => $id]);
            header("Location: demande.php?id=$id");
            exit;
        }
    }
}

// ── Données ───────────────────────────────────────────────
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

$stmtR = $pdo->prepare("
    SELECT r.*, u.nom AS nom_aidant, v.points_attribues
    FROM reponse r
    JOIN utilisateur u ON r.id_utilisateur = u.id_utilisateur
    LEFT JOIN validation v ON v.id_utilisateur = r.id_utilisateur AND v.id_demande = r.id_demande
    WHERE r.id_demande = :id
    ORDER BY r.date_reponse ASC
");
$stmtR->execute([':id' => $id]);
$reponses = $stmtR->fetchAll();

$technos    = $pdo->query("SELECT * FROM technologie ORDER BY nom_technologie")->fetchAll();
$badgeLabel = match($demande['statut']) { 'ouverte' => 'Ouverte', 'en_cours' => 'En cours', default => 'Résolue' };
$editMode   = isset($_GET['edit']);
$initiales  = substr(strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($demande['nom_demandeur']))))), 0, 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — <?= htmlspecialchars($demande['titre']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f8f8f8; color: #1c1917; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; }

        .nav { background: #fff; border-bottom: 1px solid #e2ddd7; height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 100; }
        .nav-brand { font-weight: 800; font-size: 16px; color: #1c1917; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-brand::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #2563eb; display: inline-block; }
        .btn-ghost { background: none; border: 1px solid #e2ddd7; border-radius: 999px; padding: 6px 16px; font-size: 13px; color: #78716c; text-decoration: none; transition: all 0.15s; }
        .btn-ghost:hover { border-color: #2563eb; color: #2563eb; }
        .btn-orange { background: #2563eb; border: none; border-radius: 999px; padding: 7px 18px; font-size: 13px; font-weight: 600; color: #fff; text-decoration: none; transition: background 0.15s; cursor: pointer; }
        .btn-orange:hover { background: #1d4ed8; color: #fff; }

        .wrapper { max-width: 760px; margin: 32px auto; padding: 0 24px; }
        .back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #78716c; text-decoration: none; margin-bottom: 20px; }
        .back-link:hover { color: #2563eb; }

        .card { background: #fff; border: 1px solid #e2ddd7; border-radius: 16px; padding: 24px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); margin-bottom: 14px; }

        /* DEMANDE */
        .demande-meta { display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px; flex-wrap: wrap; gap: 8px; }
        .demande-title { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; margin: 10px 0 12px; line-height: 1.3; }
        .demande-info { display: flex; gap: 16px; font-size: 12px; color: #a8a29e; margin-bottom: 16px; flex-wrap: wrap; }
        .demande-body { background: #faf9f7; border: 1px solid #f0ede8; border-radius: 10px; padding: 16px; font-size: 13px; line-height: 1.7; color: #44403c; }

        /* ACTION BUTTONS */
        .action-btns { display: flex; gap: 8px; align-items: center; }
        .btn-edit { background: none; border: 1px solid #e2ddd7; border-radius: 999px; padding: 5px 14px; font-size: 12px; color: #78716c; cursor: pointer; text-decoration: none; transition: all 0.15s; }
        .btn-edit:hover { border-color: #2563eb; color: #2563eb; }
        .btn-delete { background: none; border: 1px solid #fecaca; border-radius: 999px; padding: 5px 14px; font-size: 12px; color: #dc2626; cursor: pointer; transition: all 0.15s; }
        .btn-delete:hover { background: #fef2f2; }

        /* EDIT FORM */
        .edit-form { margin-top: 16px; border-top: 1px solid #f0ede8; padding-top: 16px; }
        .field { margin-bottom: 14px; }
        .field label { display: block; font-size: 12px; font-weight: 600; color: #78716c; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 0.4px; }
        .field input, .field select, .field textarea {
            width: 100%; padding: 9px 13px; border: 1px solid #e2ddd7; border-radius: 10px;
            font-size: 13px; color: #1c1917; background: #fff; font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus, .field select:focus, .field textarea:focus { outline: none; border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,0.1); }
        .field textarea { resize: vertical; min-height: 100px; line-height: 1.5; }
        .edit-actions { display: flex; gap: 8px; margin-top: 16px; }
        .btn-save { background: #2563eb; border: none; border-radius: 999px; padding: 8px 20px; font-size: 13px; font-weight: 600; color: #fff; cursor: pointer; }
        .btn-save:hover { background: #1d4ed8; }
        .btn-cancel-edit { background: none; border: 1px solid #e2ddd7; border-radius: 999px; padding: 8px 16px; font-size: 13px; color: #78716c; text-decoration: none; }
        .btn-cancel-edit:hover { border-color: #78716c; color: #1c1917; }

        /* TAGS */
        .tag { display: inline-block; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }
        .tag-tech     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .tag-ouverte  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .tag-en_cours { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .tag-terminee { background: #f5f5f4; color: #57534e; border: 1px solid #d6d3d1; }
        .tag-validee  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; font-size: 11px; font-weight: 600; padding: 3px 10px; border-radius: 999px; }

        .section-title { font-size: 13px; font-weight: 700; color: #78716c; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 14px; }

        /* REPONSES */
        .reponse-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; flex-wrap: wrap; gap: 8px; }
        .aidant-info { display: flex; align-items: center; gap: 10px; }
        .avatar-sm { width: 36px; height: 36px; border-radius: 8px; background: #eff6ff; border: 1px solid #bfdbfe; color: #2563eb; font-size: 12px; font-weight: 700; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .aidant-name { font-size: 13px; font-weight: 600; color: #1c1917; }
        .aidant-date { font-size: 11px; color: #a8a29e; margin-top: 1px; }
        .reponse-body { font-size: 13px; line-height: 1.7; color: #44403c; margin-bottom: 12px; }
        .contact-line { font-size: 12px; color: #78716c; padding: 8px 12px; background: #faf9f7; border-radius: 8px; border: 1px solid #f0ede8; }
        .pts-badge { font-size: 12px; font-weight: 600; color: #2563eb; }

        /* FORM RÉPONSE */
        .form-title { font-size: 15px; font-weight: 700; margin-bottom: 20px; color: #1c1917; }
        .btn-submit { background: #2563eb; border: none; border-radius: 999px; padding: 10px 24px; font-size: 13px; font-weight: 600; color: #fff; cursor: pointer; }
        .btn-submit:hover { background: #1d4ed8; }
        .empty { text-align: center; padding: 32px; color: #a8a29e; font-size: 13px; }

        /* CONFIRM DELETE */
        .confirm-delete { display: none; background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 14px 16px; margin-top: 12px; font-size: 13px; color: #991b1b; }
        .confirm-delete.show { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }

        /* ACCEPT BUTTON */
        .btn-accept { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 999px; padding: 5px 14px; font-size: 12px; font-weight: 600; color: #065f46; cursor: pointer; transition: all 0.15s; }
        .btn-accept:hover { background: #065f46; color: #fff; border-color: #065f46; }
        .badge-resolved { display: inline-flex; align-items: center; gap: 5px; background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 10px 14px; margin-top: 12px; font-size: 13px; color: #065f46; font-weight: 500; width: 100%; }
        .badge-resolved svg { flex-shrink: 0; }
    </style>
</head>
<body>

<nav class="nav">
    <a href="browse.php" class="nav-brand">PeerLink</a>
    <div style="display:flex;gap:8px">
        <a href="dashboard.php?id=1" class="btn-ghost">Mon profil</a>
        <a href="create_request.php" class="btn-orange">Poster une demande</a>
    </div>
</nav>

<div class="wrapper">
    <a href="browse.php" class="back-link">← Retour aux demandes</a>

    <!-- DEMANDE -->
    <div class="card">
        <div class="demande-meta">
            <span class="tag" style="<?= techStyle($demande['nom_technologie']) ?>"><?= htmlspecialchars($demande['nom_technologie']) ?></span>
            <div class="action-btns">
                <span class="tag tag-<?= $demande['statut'] ?>"><?= $badgeLabel ?></span>
                <a href="demande.php?id=<?= $id ?><?= $editMode ? '' : '&edit' ?>" class="btn-edit">
                    <?= $editMode ? 'Annuler' : 'Modifier' ?>
                </a>
                <button class="btn-delete" type="button" onclick="toggleConfirm()">Supprimer</button>
            </div>
        </div>

        <?php if ($editMode) : ?>
            <!-- FORMULAIRE ÉDITION -->
            <form method="POST" class="edit-form">
                <input type="hidden" name="action" value="edit_demande" />
                <div class="field">
                    <label>Technologie</label>
                    <select name="id_technologie">
                        <?php foreach ($technos as $t) : ?>
                            <option value="<?= $t['id_technologie'] ?>" <?= $t['id_technologie'] == $demande['id_technologie'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['nom_technologie']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field">
                    <label>Titre</label>
                    <input type="text" name="titre" value="<?= htmlspecialchars($demande['titre']) ?>" required />
                </div>
                <div class="field">
                    <label>Description</label>
                    <textarea name="description" required><?= htmlspecialchars($demande['description']) ?></textarea>
                </div>
                <div class="edit-actions">
                    <button type="submit" class="btn-save">Enregistrer</button>
                    <a href="demande.php?id=<?= $id ?>" class="btn-cancel-edit">Annuler</a>
                </div>
            </form>
        <?php else : ?>
            <div class="demande-title"><?= htmlspecialchars($demande['titre']) ?></div>
            <div class="demande-info">
                <span><?= htmlspecialchars($demande['nom_demandeur']) ?></span>
                <span><?= date('d/m/Y à H:i', strtotime($demande['date_creation'])) ?></span>
                <span><?= count($reponses) ?> réponse<?= count($reponses) > 1 ? 's' : '' ?></span>
            </div>
            <div class="demande-body"><?= nl2br(htmlspecialchars($demande['description'])) ?></div>
        <?php endif; ?>

        <!-- Confirmation suppression -->
        <div class="confirm-delete" id="confirmDelete">
            <span>Supprimer définitivement cette demande et toutes ses réponses ?</span>
            <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete_demande" />
                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn-delete">Confirmer</button>
                    <button type="button" class="btn-edit" onclick="toggleConfirm()">Annuler</button>
                </div>
            </form>
        </div>
    </div>

    <!-- REPONSES -->
    <div class="section-title"><?= count($reponses) ?> réponse<?= count($reponses) > 1 ? 's' : '' ?></div>

    <?php if (empty($reponses)) : ?>
        <div class="card empty">Aucune réponse pour l'instant — sois le premier à aider !</div>
    <?php else : ?>
        <?php foreach ($reponses as $r) :
            $init2 = substr(strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($r['nom_aidant']))))), 0, 2);
        ?>
        <div class="card">
            <div class="reponse-header">
                <div class="aidant-info">
                    <div class="avatar-sm"><?= htmlspecialchars($init2) ?></div>
                    <div>
                        <div class="aidant-name"><?= htmlspecialchars($r['nom_aidant']) ?></div>
                        <div class="aidant-date">
                            <?= date('d/m/Y à H:i', strtotime($r['date_reponse'])) ?>
                            <?php if ($r['points_attribues']) : ?>
                                · <span class="pts-badge">+<?= $r['points_attribues'] ?> pts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="action-btns">
                    <?php if ($r['points_attribues']) : ?>
                        <span class="tag-validee">✓ Acceptée</span>
                    <?php elseif ($demande['statut'] !== 'terminee') : ?>
                        <!-- Accepter cette réponse -->
                        <form method="POST" onsubmit="return confirm('Marquer cette réponse comme solution ? La demande sera résolue et l\'aidant recevra 100 pts.')">
                            <input type="hidden" name="action" value="accept_reponse" />
                            <input type="hidden" name="id_utilisateur" value="<?= $r['id_utilisateur'] ?>" />
                            <button type="submit" class="btn-accept">✓ Accepter</button>
                        </form>
                    <?php endif; ?>
                    <!-- Supprimer réponse -->
                    <form method="POST" onsubmit="return confirm('Supprimer cette réponse ?')">
                        <input type="hidden" name="action" value="delete_reponse" />
                        <input type="hidden" name="id_utilisateur" value="<?= $r['id_utilisateur'] ?>" />
                        <button type="submit" class="btn-delete">Supprimer</button>
                    </form>
                </div>
            </div>
            <div class="reponse-body"><?= nl2br(htmlspecialchars($r['message'])) ?></div>
            <div class="contact-line">Contact : <?= htmlspecialchars($r['contact']) ?></div>
            <?php if ($r['points_attribues']) : ?>
                <div class="badge-resolved">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                    Solution acceptée · +<?= $r['points_attribues'] ?> pts attribués
                </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- FORMULAIRE RÉPONSE -->
    <?php if ($demande['statut'] !== 'terminee') : ?>
    <div class="card" style="margin-top:8px">
        <div class="form-title">Apporter ton aide</div>
        <form method="POST">
            <input type="hidden" name="action" value="new_reponse" />
            <div class="field">
                <label>Ton pseudo</label>
                <input type="text" name="nom" placeholder="ton_pseudo" required />
            </div>
            <div class="field">
                <label>Ta réponse</label>
                <textarea name="message" placeholder="Décris ta solution ou ta piste..." required></textarea>
            </div>
            <div class="field">
                <label>Comment te contacter ?</label>
                <input type="text" name="contact" placeholder="Discord, email, GitHub..." required />
            </div>
            <button type="submit" class="btn-submit">Envoyer ma réponse</button>
        </form>
    </div>
    <?php endif; ?>

</div>

<script>
function toggleConfirm() {
    const el = document.getElementById('confirmDelete');
    el.classList.toggle('show');
}
</script>

</body>
</html>