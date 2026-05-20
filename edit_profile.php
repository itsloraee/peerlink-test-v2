<?php
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if (!$id) { header('Location: browse.php'); exit; }

$stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
$stmt->execute([':id' => $id]);
$user = $stmt->fetch();
if (!$user) { header('Location: browse.php'); exit; }

$erreurs = [];
$succes  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom      = trim($_POST['nom']   ?? '');
    $email    = trim($_POST['email'] ?? '');
    $mdp      = $_POST['mot_de_passe']        ?? '';
    $mdp_conf = $_POST['mot_de_passe_confirm'] ?? '';

    if (!$nom)   $erreurs[] = 'Le pseudo est obligatoire.';
    if (!$email) $erreurs[] = "L'email est obligatoire.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $erreurs[] = 'Email invalide.';

    // Vérifier email unique (sauf pour cet utilisateur)
    if ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmtCheck = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email AND id_utilisateur != :id");
        $stmtCheck->execute([':email' => $email, ':id' => $id]);
        if ($stmtCheck->fetch()) $erreurs[] = 'Cet email est déjà utilisé par un autre compte.';
    }

    if ($mdp && $mdp !== $mdp_conf) $erreurs[] = 'Les mots de passe ne correspondent pas.';

    if (empty($erreurs)) {
        if ($mdp) {
            $pdo->prepare("UPDATE utilisateur SET nom=:nom, email=:email, mot_de_passe=:mdp WHERE id_utilisateur=:id")
                ->execute([':nom' => $nom, ':email' => $email, ':mdp' => password_hash($mdp, PASSWORD_BCRYPT), ':id' => $id]);
        } else {
            $pdo->prepare("UPDATE utilisateur SET nom=:nom, email=:email WHERE id_utilisateur=:id")
                ->execute([':nom' => $nom, ':email' => $email, ':id' => $id]);
        }
        $succes = true;
        // Recharger les données
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch();
    }
}

$initiales = substr(strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($user['nom']))))), 0, 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — Modifier mon profil</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { background: #f0ede8; color: #1c1917; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 14px; }

        /* NAVBAR */
        .nav { background: #fff; border-bottom: 1px solid #e2ddd7; height: 56px; display: flex; align-items: center; justify-content: space-between; padding: 0 32px; position: sticky; top: 0; z-index: 100; }
        .nav-brand { font-weight: 800; font-size: 16px; color: #1c1917; text-decoration: none; display: flex; align-items: center; gap: 8px; }
        .nav-brand::before { content: ''; width: 8px; height: 8px; border-radius: 50%; background: #ea580c; display: inline-block; }
        .btn-ghost { background: none; border: 1px solid #e2ddd7; border-radius: 999px; padding: 6px 16px; font-size: 13px; color: #78716c; text-decoration: none; transition: all 0.15s; }
        .btn-ghost:hover { border-color: #ea580c; color: #ea580c; }

        /* WRAPPER */
        .wrapper { max-width: 520px; margin: 36px auto; padding: 0 24px; }

        .back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #78716c; text-decoration: none; margin-bottom: 20px; }
        .back-link:hover { color: #ea580c; }

        /* AVATAR BLOCK */
        .avatar-block { display: flex; align-items: center; gap: 16px; margin-bottom: 28px; }
        .avatar { width: 56px; height: 56px; border-radius: 12px; background: #fff7ed; border: 2px solid #fed7aa; color: #ea580c; font-size: 18px; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
        .avatar-info .name { font-size: 18px; font-weight: 800; letter-spacing: -0.5px; }
        .avatar-info .sub { font-size: 12px; color: #a8a29e; margin-top: 3px; }

        /* FORM CARD */
        .form-card { background: #fff; border: 1px solid #e2ddd7; border-radius: 16px; padding: 28px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }

        /* MESSAGES */
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #065f46; }
        .alert-error { background: #fef2f2; border: 1px solid #fecaca; border-radius: 10px; padding: 12px 16px; margin-bottom: 20px; font-size: 13px; color: #991b1b; }
        .alert-error div { margin-bottom: 2px; }

        /* FIELDS */
        .divider-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #a8a29e; margin-bottom: 14px; }
        .field { margin-bottom: 18px; }
        .field label { display: block; font-size: 13px; font-weight: 600; color: #1c1917; margin-bottom: 5px; }
        .field .hint { font-size: 12px; color: #a8a29e; margin-bottom: 6px; }
        .field input {
            width: 100%; padding: 10px 14px; border: 1px solid #e2ddd7; border-radius: 10px;
            font-size: 13px; color: #1c1917; background: #fff; font-family: inherit;
            transition: border-color 0.15s, box-shadow 0.15s;
        }
        .field input:focus { outline: none; border-color: #ea580c; box-shadow: 0 0 0 3px rgba(234,88,12,0.1); }

        /* INPUT + BUTTON ROW */
        .input-row { display: flex; gap: 8px; align-items: center; }
        .input-row input { flex: 1; }
        .btn-random {
            flex-shrink: 0;
            background: none;
            border: 1px solid #e2ddd7;
            border-radius: 999px;
            padding: 9px 14px;
            font-size: 12px;
            font-weight: 500;
            color: #78716c;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-random:hover { border-color: #ea580c; color: #ea580c; }

        hr.sep { border: none; border-top: 1px solid #f0ede8; margin: 22px 0; }

        /* ACTIONS */
        .actions { display: flex; gap: 10px; margin-top: 24px; }
        .btn-save { background: #ea580c; border: none; border-radius: 999px; padding: 10px 24px; font-size: 13px; font-weight: 600; color: #fff; cursor: pointer; transition: background 0.15s; }
        .btn-save:hover { background: #c2410c; }
        .btn-cancel { background: none; border: 1px solid #e2ddd7; border-radius: 999px; padding: 10px 20px; font-size: 13px; color: #78716c; text-decoration: none; transition: all 0.15s; }
        .btn-cancel:hover { border-color: #78716c; color: #1c1917; }
    </style>
</head>
<body>

<nav class="nav">
    <a href="browse.php" class="nav-brand">PeerLink</a>
    <a href="dashboard.php?id=<?= $id ?>" class="btn-ghost">← Mon profil</a>
</nav>

<div class="wrapper">
    <a href="dashboard.php?id=<?= $id ?>" class="back-link">← Retour au profil</a>

    <div class="avatar-block">
        <div class="avatar" id="avatarPreview"><?= htmlspecialchars($initiales) ?></div>
        <div class="avatar-info">
            <div class="name" id="namePreview"><?= htmlspecialchars($user['nom']) ?></div>
            <div class="sub"><?= $user['role'] === 'formateur' ? 'Formateur' : 'Étudiant' ?> · <?= $user['points'] ?> pts</div>
        </div>
    </div>

    <?php if ($succes) : ?>
        <div class="alert-success">Profil mis à jour avec succès !</div>
    <?php endif; ?>

    <?php if (!empty($erreurs)) : ?>
        <div class="alert-error">
            <?php foreach ($erreurs as $e) echo "<div>• $e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">

        <div class="divider-label">Informations générales</div>

        <div class="field">
            <label>Pseudo</label>
            <div class="input-row">
                <input type="text" name="nom" id="nomInput"
                       value="<?= htmlspecialchars($_POST['nom'] ?? $user['nom']) ?>"
                       placeholder="ton_pseudo" required />
                <button type="button" class="btn-random" onclick="genererPseudo()">🎲 Aléatoire</button>
            </div>
        </div>

        <div class="field">
            <label>Email</label>
            <input type="email" name="email"
                   value="<?= htmlspecialchars($_POST['email'] ?? $user['email']) ?>"
                   placeholder="ton@email.com" required />
        </div>

        <hr class="sep" />
        <div class="divider-label">Changer le mot de passe <span style="font-weight:400;text-transform:none;font-size:11px;color:#a8a29e">(laisser vide pour ne pas modifier)</span></div>

        <div class="field">
            <label>Nouveau mot de passe</label>
            <input type="password" name="mot_de_passe" placeholder="••••••••" autocomplete="new-password" />
        </div>

        <div class="field">
            <label>Confirmer le mot de passe</label>
            <input type="password" name="mot_de_passe_confirm" placeholder="••••••••" autocomplete="new-password" />
        </div>

        <div class="actions">
            <button type="submit" class="btn-save">Enregistrer</button>
            <a href="dashboard.php?id=<?= $id ?>" class="btn-cancel">Annuler</a>
        </div>

    </form>
</div>

<script>
const nomInput     = document.getElementById('nomInput');
const namePreview  = document.getElementById('namePreview');
const avatarPreview = document.getElementById('avatarPreview');

// Live preview pseudo → avatar
nomInput.addEventListener('input', updatePreview);
function updatePreview() {
    const val = nomInput.value.trim();
    namePreview.textContent = val || '—';
    const initiales = val.split(' ').filter(Boolean).map(w => w[0]).join('').toUpperCase().slice(0, 2);
    avatarPreview.textContent = initiales || '?';
}

// Générateur de pseudo aléatoire
const adjectifs = ['swift','dark','cool','silent','bright','mad','lazy','rusty','epic','sharp','wild','lucky','cosmic','pixel','binary','async','fuzzy','blazing','sneaky','cyber'];
const noms      = ['dev','coder','hacker','wizard','ninja','byte','stack','loop','patch','commit','merge','debug','query','proxy','token','cache','lambda','node','branch','scope'];

function genererPseudo() {
    const adj = adjectifs[Math.floor(Math.random() * adjectifs.length)];
    const nom = noms[Math.floor(Math.random() * noms.length)];
    const num = Math.floor(Math.random() * 999);
    nomInput.value = `${adj}_${nom}${num}`;
    updatePreview();
}
</script>

</body>
</html>