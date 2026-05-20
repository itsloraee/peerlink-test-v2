<?php
require_once 'config/db.php';

$erreurs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $id_techno   = (int) ($_POST['id_technologie'] ?? 0);
    $nom         = trim($_POST['nom']         ?? '');
    $email       = trim($_POST['email']       ?? '');

    if (!$titre)       $erreurs[] = 'Le titre est obligatoire.';
    if (!$description) $erreurs[] = 'La description est obligatoire.';
    if (!$id_techno)   $erreurs[] = 'Choisis une technologie.';
    if (!$nom)         $erreurs[] = 'Le pseudo est obligatoire.';

    if (empty($erreurs)) {
        if ($email) {
            $stmt = $pdo->prepare("SELECT id_utilisateur FROM utilisateur WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $existant = $stmt->fetch();
            if ($existant) {
                $id_user = $existant['id_utilisateur'];
            } else {
                $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe) VALUES (:nom, :email, :mdp)");
                $stmt->execute([':nom' => $nom, ':email' => $email, ':mdp' => password_hash(uniqid(), PASSWORD_BCRYPT)]);
                $id_user = $pdo->lastInsertId();
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe) VALUES (:nom, :email, :mdp)");
            $stmt->execute([':nom' => $nom, ':email' => $nom . '_' . uniqid() . '@anonymous.local', ':mdp' => password_hash(uniqid(), PASSWORD_BCRYPT)]);
            $id_user = $pdo->lastInsertId();
        }

        $stmt = $pdo->prepare("INSERT INTO demande (titre, description, id_demandeur, id_technologie) VALUES (:titre, :description, :id_user, :id_techno)");
        $stmt->execute([':titre' => $titre, ':description' => $description, ':id_user' => $id_user, ':id_techno' => $id_techno]);
        header("Location: demande.php?id=" . $pdo->lastInsertId());
        exit;
    }
}

$technos = $pdo->query("SELECT * FROM technologie ORDER BY nom_technologie")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — Poster une demande</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f0ede8;
            color: #1c1917;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-size: 14px;
        }

        /* NAVBAR */
        .nav {
            background: #fff;
            border-bottom: 1px solid #e2ddd7;
            height: 56px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .nav-brand {
            font-weight: 800;
            font-size: 16px;
            color: #1c1917;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .nav-brand::before {
            content: '';
            width: 8px; height: 8px;
            border-radius: 50%;
            background: #ea580c;
            display: inline-block;
        }
        .btn-ghost {
            background: none;
            border: 1px solid #e2ddd7;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 13px;
            color: #78716c;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-ghost:hover { border-color: #ea580c; color: #ea580c; }

        /* WRAPPER */
        .wrapper {
            max-width: 560px;
            margin: 36px auto;
            padding: 0 24px;
        }

        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 13px;
            color: #78716c;
            text-decoration: none;
            margin-bottom: 20px;
        }
        .back-link:hover { color: #ea580c; }

        h1 {
            font-size: 20px;
            font-weight: 800;
            letter-spacing: -0.5px;
            margin-bottom: 24px;
        }

        /* FORM CARD */
        .form-card {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 16px;
            padding: 28px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        /* ERREURS */
        .erreurs {
            background: #fef2f2;
            border: 1px solid #fecaca;
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #991b1b;
        }
        .erreurs div { margin-bottom: 2px; }

        /* FIELDS */
        .field { margin-bottom: 20px; }
        .field label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1c1917;
            margin-bottom: 4px;
        }
        .field .hint {
            font-size: 12px;
            color: #a8a29e;
            margin-bottom: 6px;
        }
        .field input,
        .field select,
        .field textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2ddd7;
            border-radius: 10px;
            font-size: 13px;
            color: #1c1917;
            background: #fff;
            transition: border-color 0.15s, box-shadow 0.15s;
            font-family: inherit;
        }
        .field input:focus,
        .field select:focus,
        .field textarea:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234,88,12,0.1);
        }
        .field textarea { resize: vertical; min-height: 120px; line-height: 1.5; }

        /* DIVIDER */
        .divider {
            border: none;
            border-top: 1px solid #f0ede8;
            margin: 24px 0 16px;
        }
        .divider-label {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a8a29e;
            margin-bottom: 16px;
        }

        /* TWO COLS */
        .two-cols { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }

        /* OPTIONAL LABEL */
        .optional { font-weight: 400; color: #a8a29e; font-size: 12px; }

        /* ACTIONS */
        .actions { display: flex; gap: 10px; margin-top: 24px; }
        .btn-submit {
            background: #ea580c;
            border: none;
            border-radius: 999px;
            padding: 10px 24px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-submit:hover { background: #c2410c; }
        .btn-cancel {
            background: none;
            border: 1px solid #e2ddd7;
            border-radius: 999px;
            padding: 10px 20px;
            font-size: 13px;
            color: #78716c;
            text-decoration: none;
            transition: all 0.15s;
        }
        .btn-cancel:hover { border-color: #78716c; color: #1c1917; }
    </style>
</head>
<body>

<nav class="nav">
    <a href="browse.php" class="nav-brand">PeerLink</a>
    <a href="browse.php" class="btn-ghost">← Retour</a>
</nav>

<div class="wrapper">
    <a href="browse.php" class="back-link">← Retour aux demandes</a>
    <h1>Poster une demande d'aide</h1>

    <?php if (!empty($erreurs)) : ?>
        <div class="erreurs">
            <?php foreach ($erreurs as $e) echo "<div>• $e</div>"; ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-card">

        <div class="field">
            <label>Technologie concernée</label>
            <select name="id_technologie" required>
                <option value="">Choisir une technologie...</option>
                <?php foreach ($technos as $t) : ?>
                    <option value="<?= $t['id_technologie'] ?>"
                        <?= (int)($_POST['id_technologie'] ?? 0) === (int)$t['id_technologie'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['nom_technologie']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="field">
            <label>Titre de la demande</label>
            <div class="hint">Sois précis — ex : "Token Sanctum expire trop vite"</div>
            <input type="text" name="titre"
                   placeholder="Mon problème en une phrase..."
                   value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>" required />
        </div>

        <div class="field">
            <label>Description</label>
            <div class="hint">Contexte, ce que tu as essayé, comportement attendu.</div>
            <textarea name="description"
                      placeholder="J'essaie de... mais j'obtiens... J'ai déjà tenté..." required><?=
                htmlspecialchars($_POST['description'] ?? '')
            ?></textarea>
        </div>

        <hr class="divider" />
        <div class="divider-label">Ton identité</div>

        <div class="two-cols">
            <div class="field">
                <label>Pseudo</label>
                <input type="text" name="nom" placeholder="ton_pseudo"
                       value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>" required />
            </div>
            <div class="field">
                <label>Email <span class="optional">(optionnel)</span></label>
                <input type="email" name="email" placeholder="pour être notifié"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
        </div>

        <div class="actions">
            <button type="submit" class="btn-submit">Poster la demande</button>
            <a href="browse.php" class="btn-cancel">Annuler</a>
        </div>

    </form>
</div>
</body>
</html>