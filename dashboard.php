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

$id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

$stmtUser = $pdo->prepare("SELECT * FROM utilisateur WHERE id_utilisateur = :id");
$stmtUser->execute([':id' => $id]);
$user = $stmtUser->fetch();
if (!$user) { header('Location: browse.php'); exit; }

$stmtD = $pdo->prepare("SELECT COUNT(*) AS total FROM demande WHERE id_demandeur = :id");
$stmtD->execute([':id' => $id]);
$nbDemandes = $stmtD->fetch()['total'];

$stmtA = $pdo->prepare("SELECT COUNT(*) AS total FROM reponse WHERE id_utilisateur = :id");
$stmtA->execute([':id' => $id]);
$nbAides = $stmtA->fetch()['total'];

$stmtT = $pdo->prepare("SELECT COUNT(*) AS total FROM demande WHERE id_demandeur = :id AND statut = 'terminee'");
$stmtT->execute([':id' => $id]);
$nbTerminees = $stmtT->fetch()['total'];
$tauxResolution = $nbDemandes > 0 ? round(($nbTerminees / $nbDemandes) * 100) : 0;

$stmtMesDemandes = $pdo->prepare("
    SELECT d.id_demande, d.titre, d.statut, d.date_creation, t.nom_technologie,
           COUNT(r.id_utilisateur) AS nb_reponses
    FROM demande d
    JOIN technologie t ON d.id_technologie = t.id_technologie
    LEFT JOIN reponse r ON r.id_demande = d.id_demande
    WHERE d.id_demandeur = :id
    GROUP BY d.id_demande
    ORDER BY d.date_creation DESC
");
$stmtMesDemandes->execute([':id' => $id]);
$mesDemandes = $stmtMesDemandes->fetchAll();

$stmtMesAides = $pdo->prepare("
    SELECT d.id_demande, d.titre, t.nom_technologie, r.date_reponse, v.points_attribues
    FROM reponse r
    JOIN demande d ON r.id_demande = d.id_demande
    JOIN technologie t ON d.id_technologie = t.id_technologie
    LEFT JOIN validation v ON v.id_utilisateur = r.id_utilisateur AND v.id_demande = r.id_demande
    WHERE r.id_utilisateur = :id
    ORDER BY r.date_reponse DESC
");
$stmtMesAides->execute([':id' => $id]);
$mesAides = $stmtMesAides->fetchAll();

$stmtLead = $pdo->query("SELECT id_utilisateur, nom, points FROM utilisateur ORDER BY points DESC LIMIT 5");
$leaderboard = $stmtLead->fetchAll();

$initiales = strtoupper(implode('', array_map(fn($w) => $w[0], explode(' ', trim($user['nom'])))));
$initiales  = substr($initiales, 0, 2);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Dashboard — <?= htmlspecialchars($user['nom']) ?> · PeerLink</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            background: #f8f8f8;
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
            background: #2563eb;
            display: inline-block;
        }
        .nav-actions { display: flex; gap: 8px; align-items: center; }
        .btn-ghost {
            background: none;
            border: 1px solid #e2ddd7;
            border-radius: 999px;
            padding: 6px 16px;
            font-size: 13px;
            color: #78716c;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-ghost:hover { border-color: #2563eb; color: #2563eb; }
        .btn-orange {
            background: #2563eb;
            border: none;
            border-radius: 999px;
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-orange:hover { background: #1d4ed8; color: #fff; }

        /* PROFILE HEADER */
        .profile-bar {
            background: #fff;
            border-bottom: 1px solid #e2ddd7;
            padding: 24px 32px;
        }
        .profile-bar-inner {
            max-width: 1060px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .profile-bar-inner .spacer { flex: 1; }
        .btn-edit-profile {
            background: none;
            border: 1px solid #e2ddd7;
            border-radius: 999px;
            padding: 7px 18px;
            font-size: 13px;
            font-weight: 500;
            color: #78716c;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.15s;
            white-space: nowrap;
        }
        .btn-edit-profile:hover { border-color: #2563eb; color: #2563eb; }
        .avatar {
            width: 56px; height: 56px;
            border-radius: 12px;
            background: #eff6ff;
            border: 2px solid #bfdbfe;
            color: #2563eb;
            font-size: 18px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .profile-name { font-size: 20px; font-weight: 800; letter-spacing: -0.5px; }
        .profile-meta { font-size: 13px; color: #78716c; margin-top: 4px; display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }
        .tag-role {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 600;
            padding: 2px 10px;
        }

        /* MAIN LAYOUT */
        .main {
            max-width: 1060px;
            margin: 24px auto;
            padding: 0 32px;
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 16px;
        }
        .left { display: flex; flex-direction: column; gap: 16px; }

        /* STAT ROW */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .stat-card .num {
            font-size: 38px;
            font-weight: 800;
            letter-spacing: -2px;
            color: #1c1917;
            line-height: 1;
        }
        .stat-card .lbl {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #78716c;
            margin-top: 6px;
        }

        /* SECTION CARD */
        .section {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        .section-head {
            padding: 14px 20px;
            border-bottom: 1px solid #f0ede8;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .section-head span {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #78716c;
        }
        .section-head a { font-size: 12px; color: #2563eb; text-decoration: none; }
        .section-head a:hover { text-decoration: underline; }

        /* TABLE */
        table { width: 100%; border-collapse: collapse; }
        th {
            padding: 10px 20px;
            text-align: left;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            color: #a8a29e;
            background: #fafaf9;
            border-bottom: 1px solid #f0ede8;
        }
        td {
            padding: 13px 20px;
            border-bottom: 1px solid #f5f2ef;
            font-size: 13px;
            color: #1c1917;
            vertical-align: middle;
        }
        tr:last-child td { border-bottom: none; }
        tbody tr:hover td { background: #fdf8f5; }
        td a { color: #1c1917; font-weight: 500; text-decoration: none; }
        td a:hover { color: #2563eb; }

        /* TAGS */
        .tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .tag-tech     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; } /* fallback, overridden by inline */
        .tag-ouverte  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .tag-en_cours { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .tag-terminee { background: #f5f5f4; color: #57534e; border: 1px solid #d6d3d1; }
        .pts          { font-weight: 700; color: #2563eb; }

        /* LEADERBOARD */
        .lead-card {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            align-self: start;
        }
        .lead-head {
            padding: 14px 18px;
            border-bottom: 1px solid #f0ede8;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #78716c;
        }
        .lead-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            border-bottom: 1px solid #f5f2ef;
        }
        .lead-row:last-child { border-bottom: none; }
        .lead-row.me { background: #eff6ff; }
        .rank {
            width: 24px; height: 24px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            background: #f5f5f4;
            color: #78716c;
            border: 1px solid #e2ddd7;
        }
        .rank.r1 { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .rank.r2 { background: #f3f4f6; color: #374151; border-color: #d1d5db; }
        .rank.r3 { background: #eff6ff; color: #9a3412; border-color: #bfdbfe; }
        .lead-name { font-size: 13px; font-weight: 500; color: #1c1917; flex: 1; }
        .you { font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 999px; background: #eff6ff; color: #2563eb; border: 1px solid #bfdbfe; margin-left: 4px; }
        .lead-pts { font-size: 13px; font-weight: 700; color: #2563eb; }

        /* EMPTY */
        .empty { text-align: center; padding: 32px; color: #a8a29e; font-size: 13px; }
        .empty a { color: #2563eb; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav">
    <a href="browse.php" class="nav-brand">PeerLink</a>
    <div class="nav-actions">
        <a href="browse.php" class="btn-ghost">Demandes</a>
        <a href="create_request.php" class="btn-orange">Poster une demande</a>
    </div>
</nav>

<!-- PROFILE BAR -->
<div class="profile-bar">
    <div class="profile-bar-inner">
        <div class="avatar"><?= htmlspecialchars($initiales) ?></div>
        <div>
            <div class="profile-name"><?= htmlspecialchars($user['nom']) ?></div>
            <div class="profile-meta">
                <span class="tag-role"><?= $user['role'] === 'formateur' ? 'Formateur' : 'Etudiant' ?></span>
                <span>Membre depuis <?= date('M Y', strtotime($user['date_inscription'])) ?></span>
                <span><?= htmlspecialchars($user['email']) ?></span>
            </div>
        </div>
        <div class="spacer"></div>
        <a href="edit_profile.php?id=<?= $id ?>" class="btn-edit-profile">Modifier mon profil</a>
    </div>
</div>

<!-- MAIN -->
<div class="main">
    <div class="left">

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-card blue">
                <div class="num counter" data-target="<?= $nbDemandes ?>">0</div>
                <div class="lbl">Demandes postées</div>
            </div>
            <div class="stat-card green">
                <div class="num counter" data-target="<?= $nbAides ?>">0</div>
                <div class="lbl">Aides données</div>
            </div>
            <div class="stat-card orange">
                <div class="num counter" data-target="<?= $user['points'] ?>">0</div>
                <div class="lbl">Points gagnés</div>
            </div>
        </div>

        <!-- Mes demandes -->
        <div class="section">
            <div class="section-head">
                <span>Mes demandes postées</span>
                <a href="create_request.php">+ Nouvelle</a>
            </div>
            <table>
                <thead><tr><th>Titre</th><th>Techno</th><th>Réponses</th><th>Statut</th></tr></thead>
                <tbody>
                <?php if (empty($mesDemandes)) : ?>
                    <tr><td colspan="4" class="empty">Aucune demande. <a href="create_request.php">Poster maintenant</a></td></tr>
                <?php else : foreach ($mesDemandes as $d) : ?>
                    <tr>
                        <td><a href="demande.php?id=<?= $d['id_demande'] ?>"><?= htmlspecialchars($d['titre']) ?></a></td>
                        <td><span class="tag" style="<?= techStyle($d['nom_technologie']) ?>"><?= htmlspecialchars($d['nom_technologie']) ?></span></td>
                        <td style="color:#78716c"><?= $d['nb_reponses'] ?></td>
                        <td><span class="tag tag-<?= $d['statut'] ?>"><?= match($d['statut']) { 'ouverte' => 'Ouverte', 'en_cours' => 'En cours', 'terminee' => 'Terminée', default => $d['statut'] } ?></span></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Mes aides -->
        <div class="section">
            <div class="section-head">
                <span>Mes aides données</span>
                <a href="browse.php">Voir les demandes ouvertes</a>
            </div>
            <table>
                <thead><tr><th>Demande</th><th>Techno</th><th>Date</th><th>Points</th></tr></thead>
                <tbody>
                <?php if (empty($mesAides)) : ?>
                    <tr><td colspan="4" class="empty">Aucune aide. <a href="browse.php">Aider quelqu'un</a></td></tr>
                <?php else : foreach ($mesAides as $a) : ?>
                    <tr>
                        <td><a href="demande.php?id=<?= $a['id_demande'] ?>"><?= htmlspecialchars($a['titre']) ?></a></td>
                        <td><span class="tag" style="<?= techStyle($a['nom_technologie']) ?>"><?= htmlspecialchars($a['nom_technologie']) ?></span></td>
                        <td style="color:#a8a29e;font-size:12px"><?= date('d/m/Y', strtotime($a['date_reponse'])) ?></td>
                        <td><?php if ($a['points_attribues']) : ?><span class="pts">+<?= $a['points_attribues'] ?> pts</span><?php else : ?><span style="color:#a8a29e">—</span><?php endif; ?></td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <!-- LEADERBOARD -->
    <div class="lead-card">
        <div class="lead-head">Classement</div>
        <?php foreach ($leaderboard as $i => $u) :
            $isMe = $u['id_utilisateur'] == $id;
            $rc   = match($i) { 0 => 'r1', 1 => 'r2', 2 => 'r3', default => '' };
        ?>
        <div class="lead-row <?= $isMe ? 'me' : '' ?>">
            <div class="rank <?= $rc ?>"><?= $i + 1 ?></div>
            <div class="lead-name">
                <?= htmlspecialchars($u['nom']) ?>
                <?= $isMe ? '<span class="you">vous</span>' : '' ?>
            </div>
            <div class="lead-pts"><?= $u['points'] ?> pts</div>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<script>
document.querySelectorAll('.counter').forEach(el => {
    const target = parseInt(el.dataset.target);
    if (!target) return;
    let n = 0;
    const step = Math.max(1, Math.ceil(target / 30));
    const t = setInterval(() => { n = Math.min(n + step, target); el.textContent = n; if (n >= target) clearInterval(t); }, 20);
});
</script>

</body>
</html>