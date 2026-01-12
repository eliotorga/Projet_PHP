<?php
// calcule le pourcentage de victoires pour chaque joueur
// affiche matchs joues, matchs gagnes et pourcentage de reussite

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_stats.php";

include __DIR__ . "/../includes/header.php";

/* =========================
   RÉCUPÉRATION DES JOUEURS
========================= */
$joueurs = getPlayersBasicList($gestion_sportive);
?>

<h2>📊 % de matchs gagnés par joueur</h2>

<p>
    Ce pourcentage correspond au nombre de matchs <strong>gagnés</strong>
    parmi ceux auxquels le joueur a <strong>participé</strong>.
</p>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Joueur</th>
    <th>Matchs joués</th>
    <th>Matchs gagnés</th>
    <th>% Victoires</th>
</tr>
</thead>
<tbody>

<?php foreach ($joueurs as $j): ?>
<?php
$id = $j["id_joueur"];

/* =========================
   MATCHS JOUÉS & GAGNÉS
========================= */
$data = getPlayerWinRateData($gestion_sportive, $id);
$total = $data["total"] ?? 0;
$wins  = $data["wins"] ?? 0;

$pct = ($total > 0) ? round(($wins / $total) * 100, 1) : null;
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
    <td><?= $total ?></td>
    <td><?= $wins ?></td>
    <td><?= $pct !== null ? $pct . " %" : "—" ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
