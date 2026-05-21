<?php
require_once 'config/db.php';

// ── Couleurs par technologie ───────────────────────────────
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
        'dotnet'     => ['#ede9fe','#5b21b6','#ddd6fe'],
        'swift'      => ['#fff7ed','#c2410c','#fed7aa'],
    ];
    $key = strtolower($nom);
    foreach ($map as $k => $c) {
        if (str_contains($key, $k)) {
            return "background:{$c[0]};color:{$c[1]};border:1px solid {$c[2]}";
        }
    }
    // Fallback palette
    $palettes = [
        ['#f3e8ff','#7e22ce','#e9d5ff'],
        ['#fce7f3','#9d174d','#fbcfe8'],
        ['#ecfdf5','#065f46','#a7f3d0'],
        ['#eff6ff','#1e40af','#bfdbfe'],
        ['#fef9c3','#854d0e','#fde68a'],
    ];
    $c = $palettes[abs(crc32($nom)) % count($palettes)];
    return "background:{$c[0]};color:{$c[1]};border:1px solid {$c[2]}";
}

$filtre = isset($_GET['techno']) ? (int) $_GET['techno'] : 0;

$stats = $pdo->query("
    SELECT
        SUM(statut = 'ouverte')  AS ouvertes,
        SUM(statut = 'terminee') AS terminees,
        COUNT(*)                 AS total
    FROM demande
")->fetch();

$technos = $pdo->query("SELECT * FROM technologie ORDER BY nom_technologie")->fetchAll();

$sql = "
    SELECT d.*, u.nom AS nom_demandeur, t.nom_technologie,
           (SELECT COUNT(*) FROM reponse r WHERE r.id_demande = d.id_demande) AS nb_reponses
    FROM demande d
    JOIN utilisateur u ON d.id_demandeur = u.id_utilisateur
    JOIN technologie t ON d.id_technologie = t.id_technologie
";
if ($filtre) $sql .= " WHERE d.id_technologie = :filtre";
$sql .= " ORDER BY d.date_creation DESC";

$stmt = $pdo->prepare($sql);
if ($filtre) $stmt->bindValue(':filtre', $filtre, PDO::PARAM_INT);
$stmt->execute();
$demandes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PeerLink — Demandes d'aide</title>
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
            transition: all 0.15s;
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
            transition: background 0.15s;
        }
        .btn-orange:hover { background: #1d4ed8; color: #fff; }

        /* MAIN WRAPPER */
        .wrapper {
            max-width: 780px;
            margin: 32px auto;
            padding: 0 24px;
        }

        /* STATS */
        .stats-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 12px;
            margin-bottom: 28px;
        }
        .stat-card {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 14px;
            padding: 18px 20px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
        }
        .stat-card .lbl {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #a8a29e;
            margin-bottom: 6px;
        }
        .stat-card .num {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: -1px;
            color: #1c1917;
        }

        /* FILTERS */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 14px;
        }
        .section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1c1917;
        }
        .chips {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }
        .chip {
            display: inline-block;
            padding: 5px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 500;
            border: 1px solid #e2ddd7;
            background: #fff;
            color: #78716c;
            text-decoration: none;
            transition: all 0.15s;
            cursor: pointer;
        }
        .chip:hover { border-color: #2563eb; color: #2563eb; }
        .chip.active { background: #2563eb; border-color: #2563eb; color: #fff; font-weight: 600; }

        /* DEMANDE CARDS */
        a.demande-card {
            display: block;
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 10px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 2px 6px rgba(0,0,0,0.04);
            transition: transform 0.15s, box-shadow 0.15s, border-color 0.15s;
        }
        a.demande-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            border-color: #bfdbfe;
        }
        .card-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 8px;
            gap: 12px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 600;
            color: #1c1917;
            line-height: 1.4;
        }
        .card-desc {
            font-size: 13px;
            color: #78716c;
            margin-bottom: 12px;
            line-height: 1.5;
        }
        .card-meta {
            display: flex;
            align-items: center;
            gap: 14px;
            flex-wrap: wrap;
            font-size: 12px;
            color: #a8a29e;
        }

        /* TAGS */
        .tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            white-space: nowrap;
        }
        .tag-tech     { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .tag-ouverte  { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .tag-en_cours { background: #fffbeb; color: #92400e; border: 1px solid #fde68a; }
        .tag-terminee { background: #f5f5f4; color: #57534e; border: 1px solid #d6d3d1; }

        /* EMPTY */
        .empty-state {
            background: #fff;
            border: 1px solid #e2ddd7;
            border-radius: 14px;
            padding: 48px;
            text-align: center;
            color: #a8a29e;
            font-size: 14px;
        }
        .empty-state a { color: #2563eb; text-decoration: none; }
        .empty-state a:hover { text-decoration: underline; }
    </style>
</head>
<body>

<!-- NAVBAR -->
<nav class="nav">
    <a href="browse.php" class="nav-brand">PeerLink</a>
    <div class="nav-actions">
        <a href="dashboard.php?id=1" class="btn-ghost">Mon profil</a>
        <a href="create_request.php" class="btn-orange">Poster une demande</a>
    </div>
</nav>

<div class="wrapper">

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card green">
            <div class="lbl">Demandes ouvertes</div>
            <div class="num"><?= $stats['ouvertes'] ?></div>
        </div>
        <div class="stat-card blue">
            <div class="lbl">Résolues</div>
            <div class="num"><?= $stats['terminees'] ?></div>
        </div>
        <div class="stat-card orange">
            <div class="lbl">Total demandes</div>
            <div class="num"><?= $stats['total'] ?></div>
        </div>
    </div>

    <!-- Header + filtres -->
    <div class="section-header">
        <span class="section-title">Demandes d'aide</span>
        <a href="create_request.php" class="btn-orange">+ Poster une demande</a>
    </div>

    <div class="chips">
        <a href="browse.php" class="chip <?= !$filtre ? 'active' : '' ?>">Toutes</a>
        <?php foreach ($technos as $t) : ?>
            <a href="browse.php?techno=<?= $t['id_technologie'] ?>"
               class="chip <?= $filtre === (int)$t['id_technologie'] ? 'active' : '' ?>">
                <?= htmlspecialchars($t['nom_technologie']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Liste -->
    <?php if (empty($demandes)) : ?>
        <div class="empty-state">
            Aucune demande pour l'instant.<br>
            <a href="create_request.php">Sois le premier à en poster une</a>
        </div>
    <?php else : ?>
        <?php foreach ($demandes as $d) : ?>
        <a href="demande.php?id=<?= $d['id_demande'] ?>" class="demande-card">
            <div class="card-top">
                <div class="card-title"><?= htmlspecialchars($d['titre']) ?></div>
                <span class="tag tag-<?= $d['statut'] ?>">
                    <?= match($d['statut']) { 'ouverte' => 'Ouverte', 'en_cours' => 'En cours', default => 'Résolue' } ?>
                </span>
            </div>
            <div class="card-desc"><?= htmlspecialchars(mb_substr($d['description'], 0, 120)) ?>…</div>
            <div class="card-meta">
                <span class="tag" style="<?= techStyle($d['nom_technologie']) ?>"><?= htmlspecialchars($d['nom_technologie']) ?></span>
                <span><?= htmlspecialchars($d['nom_demandeur']) ?></span>
                <span><?= date('d/m/Y', strtotime($d['date_creation'])) ?></span>
                <span><?= $d['nb_reponses'] ?> réponse<?= $d['nb_reponses'] > 1 ? 's' : '' ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    <?php endif; ?>

</div>
</body>
</html>