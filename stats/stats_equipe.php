<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =====================
   STATS ÉQUIPE
===================== */
$totalMatchs = $gestion_sportive->query("
    SELECT COUNT(*) FROM matchs WHERE resultat IS NOT NULL
")->fetchColumn();

$statsResultats = $gestion_sportive->query("
    SELECT resultat, COUNT(*) nb
    FROM matchs
    WHERE resultat IS NOT NULL
    GROUP BY resultat
")->fetchAll(PDO::FETCH_KEY_PAIR);

$victoires = $statsResultats["VICTOIRE"] ?? 0;
$defaites  = $statsResultats["DEFAITE"] ?? 0;
$nuls      = $statsResultats["NUL"] ?? 0;

function pct($v, $t) {
    return $t > 0 ? round(($v / $t) * 100, 1) : 0;
}

/* =====================
   STATS JOUEURS
===================== */
$joueurs = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        s.libelle AS statut,

        SUM(p.role = 'TITULAIRE') AS titularisations,
        SUM(p.role = 'REMPLACANT') AS remplacements,

        ROUND(AVG(p.evaluation), 2) AS moyenne_eval,

        ROUND(
            SUM(CASE WHEN m.resultat = 'VICTOIRE' THEN 1 ELSE 0 END)
            / NULLIF(COUNT(m.id_match),0) * 100
        ,1) AS pct_victoires

    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    LEFT JOIN participation p ON p.id_joueur = j.id_joueur
    LEFT JOIN matchs m ON m.id_match = p.id_match
    GROUP BY j.id_joueur
    ORDER BY j.nom
")->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<style>
/* =====================
   PAGE STATS – DA FOOT
===================== */
.page-title {
    margin-bottom: 25px;
}

.page-title h1 {
    font-size: 2.2em;
}

/* =====================
   CARDS ÉQUIPE
===================== */
.cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.card {
    background: #ffffff;
    border-radius: 18px;
    padding: 22px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    text-align: center;
}

.card h2 {
    font-size: 2.4em;
    margin-bottom: 5px;
}

.card span {
    opacity: 0.7;
}

/* =====================
   TABLE STATS JOUEURS
===================== */
.table-container {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #f4f6f8;
}

th, td {
    padding: 14px 16px;
    text-align: center;
}

th {
    font-size: 0.85em;
    color: #555;
}

tbody tr {
    border-top: 1px solid #eee;
}

tbody tr:hover {
    background: #f9fbfc;
}

/* =====================
   BADGES
===================== */
.badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.8em;
    font-weight: bold;
}

.badge.green { background: #e8f5e9; color: #2e7d32; }
.badge.red { background: #ffebee; color: #c62828; }
.badge.grey { background: #eceff1; color: #455a64; }

/* =====================
   FOOTER INFO
===================== */
.info {
    margin-top: 25px;
    padding: 15px;
    background: #f4f6f8;
    border-radius: 12px;
    font-style: italic;
}
</style>

<!-- ================= PAGE ================= -->

<div class="page-title">
    <h1>📊 Statistiques de l’équipe</h1>
    <p>
        Ces statistiques aident l’entraîneur à analyser les performances
        et à prendre des décisions pour les prochains matchs.
    </p>
</div>

<!-- ================= STATS ÉQUIPE ================= -->
<div class="cards">
    <div class="card">
        <h2><?= $totalMatchs ?></h2>
        <span>Matchs joués</span>
    </div>
    <div class="card">
        <h2><?= $victoires ?></h2>
        <span>Victoires (<?= pct($victoires, $totalMatchs) ?>%)</span>
    </div>
    <div class="card">
        <h2><?= $defaites ?></h2>
        <span>Défaites (<?= pct($defaites, $totalMatchs) ?>%)</span>
    </div>
    <div class="card">
        <h2><?= $nuls ?></h2>
        <span>Nuls (<?= pct($nuls, $totalMatchs) ?>%)</span>
    </div>
</div>

<!-- ================= STATS JOUEURS ================= -->
<h2 style="margin-bottom:15px;">👥 Statistiques individuelles des joueurs</h2>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>Joueur</th>
            <th>Statut</th>
            <th>Titularisations</th>
            <th>Remplacements</th>
            <th>Moy. évaluations</th>
            <th>% victoires</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($joueurs as $j): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($j["nom"]) ?></strong><br>
                <span style="opacity:0.7"><?= htmlspecialchars($j["prenom"]) ?></span>
            </td>
            <td><?= htmlspecialchars($j["statut"]) ?></td>
            <td><?= $j["titularisations"] ?? 0 ?></td>
            <td><?= $j["remplacements"] ?? 0 ?></td>
            <td>
                <?= $j["moyenne_eval"] ?? "—" ?>
            </td>
            <td>
                <?php if ($j["pct_victoires"] !== null): ?>
                    <span class="badge <?= $j["pct_victoires"] >= 50 ? 'green' : 'red' ?>">
                        <?= $j["pct_victoires"] ?> %
                    </span>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="info">
💡 Les statistiques sont calculées uniquement à partir des matchs joués et des évaluations saisies par l’entraîneur.
</div>

<?php include "../includes/footer.php"; ?>
