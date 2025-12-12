<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

include __DIR__ . "/../includes/header.php";

/* =========================
   STATS ÉQUIPE
========================= */

// Total matchs joués
$totalMatchs = $gestion_sportive->query("
    SELECT COUNT(*) FROM matchs
    WHERE resultat IS NOT NULL
")->fetchColumn();

// Victoires / Défaites / Nuls
$statsEquipe = $gestion_sportive->query("
    SELECT resultat, COUNT(*) AS nb
    FROM matchs
    WHERE resultat IS NOT NULL
    GROUP BY resultat
")->fetchAll(PDO::FETCH_KEY_PAIR);

$victoires = $statsEquipe["VICTOIRE"] ?? 0;
$defaites  = $statsEquipe["DEFAITE"] ?? 0;
$nuls      = $statsEquipe["NUL"] ?? 0;

function pct($nb, $total) {
    return $total > 0 ? round(($nb / $total) * 100, 1) : 0;
}

/* =========================
   LISTE DES JOUEURS
========================= */
$joueurs = $gestion_sportive->query("
    SELECT j.id_joueur, j.nom, j.prenom, s.libelle AS statut
    FROM joueur j
    JOIN statut s ON j.id_statut = s.id_statut
    ORDER BY j.nom
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>📊 Statistiques de l'équipe</h2>

<h3>Résultats globaux</h3>
<ul>
    <li>🏆 Victoires : <?= $victoires ?> (<?= pct($victoires, $totalMatchs) ?> %)</li>
    <li>❌ Défaites : <?= $defaites ?> (<?= pct($defaites, $totalMatchs) ?> %)</li>
    <li>🤝 Nuls : <?= $nuls ?> (<?= pct($nuls, $totalMatchs) ?> %)</li>
</ul>

<hr>

<h3>Statistiques par joueur</h3>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Joueur</th>
    <th>Statut</th>
    <th>Poste préféré</th>
    <th>Titularisations</th>
    <th>Remplacements</th>
    <th>Moy. Notes</th>
    <th>Sélections consécutives</th>
    <th>% Victoires</th>
</tr>
</thead>
<tbody>

<?php foreach ($joueurs as $j): ?>
<?php
$id = $j["id_joueur"];

/* =========================
   TITULARISATIONS / REMPLACEMENTS
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT
        SUM(role = 'TITULAIRE') AS titulaires,
        SUM(role = 'REMPLACANT') AS rempla
    FROM participation
    WHERE id_joueur = ?
");
$stmt->execute([$id]);
$roles = $stmt->fetch();

/* =========================
   MOYENNE DES NOTES
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT ROUND(AVG(evaluation),2)
    FROM participation
    WHERE id_joueur = ? AND evaluation IS NOT NULL
");
$stmt->execute([$id]);
$moyenne = $stmt->fetchColumn();

/* =========================
   POSTE PRÉFÉRÉ
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT p.libelle
    FROM participation pa
    JOIN poste p ON pa.id_poste = p.id_poste
    WHERE pa.id_joueur = ? AND pa.evaluation IS NOT NULL
    GROUP BY pa.id_poste
    ORDER BY AVG(pa.evaluation) DESC
    LIMIT 1
");
$stmt->execute([$id]);
$postePref = $stmt->fetchColumn() ?: "—";

/* =========================
   % MATCHS GAGNÉS
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT
        SUM(m.resultat = 'VICTOIRE') AS wins,
        COUNT(*) AS total
    FROM participation pa
    JOIN matchs m ON pa.id_match = m.id_match
    WHERE pa.id_joueur = ? AND m.resultat IS NOT NULL
");
$stmt->execute([$id]);
$winStats = $stmt->fetch();
$pctWins = $winStats["total"] > 0
    ? round(($winStats["wins"] / $winStats["total"]) * 100, 1)
    : 0;

/* =========================
   SÉLECTIONS CONSÉCUTIVES
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT m.id_match
    FROM matchs m
    ORDER BY m.date_heure DESC
");
$stmt->execute();
$matchs = $stmt->fetchAll(PDO::FETCH_COLUMN);

$consecutifs = 0;
foreach ($matchs as $mid) {
    $stmt2 = $gestion_sportive->prepare("
        SELECT COUNT(*) FROM participation
        WHERE id_match = ? AND id_joueur = ?
    ");
    $stmt2->execute([$mid, $id]);

    if ($stmt2->fetchColumn() > 0) {
        $consecutifs++;
    } else {
        break;
    }
}
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></td>
    <td><?= htmlspecialchars($j["statut"]) ?></td>
    <td><?= htmlspecialchars($postePref) ?></td>
    <td><?= $roles["titulaires"] ?? 0 ?></td>
    <td><?= $roles["rempla"] ?? 0 ?></td>
    <td><?= $moyenne ?? "—" ?></td>
    <td><?= $consecutifs ?></td>
    <td><?= $pctWins ?> %</td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
