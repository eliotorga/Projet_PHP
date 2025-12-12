<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

// Chargement correct des bibliothèques
require_once "../bdd/db_joueur.php";
require_once "../bdd/db_match.php";      // 🔥 CORRECTION ICI
require_once "../bdd/db_participation.php";


include "../includes/header.php";


// ----------------------------------------------------
// 1️⃣  STATISTIQUES GLOBALES SUR LES MATCHS
// ----------------------------------------------------
$matches = getAllMatches($gestion_sportive);

$total = count(array_filter($matches, fn($m) => $m["resultat"] !== null));
$nb_victoires = 0;
$nb_defaites  = 0;
$nb_nuls      = 0;

foreach ($matches as $m) {
    if ($m["resultat"] === "VICTOIRE") $nb_victoires++;
    if ($m["resultat"] === "DEFAITE")  $nb_defaites++;
    if ($m["resultat"] === "NUL")      $nb_nuls++;
}

function pct($v, $t) {
    return $t > 0 ? round(($v / $t) * 100, 1) : 0;
}

?>

<h2>📊 Statistiques de l'équipe</h2>

<div class="card">
    <h3>📈 Résultats des matchs</h3>

    <p>Total de matchs joués : <strong><?= $total ?></strong></p>

    <ul>
        <li>🏆 Victoires : <strong><?= $nb_victoires ?></strong> (<?= pct($nb_victoires, $total) ?>%)</li>
        <li>❌ Défaites : <strong><?= $nb_defaites ?></strong> (<?= pct($nb_defaites, $total) ?>%)</li>
        <li>➖ Nuls : <strong><?= $nb_nuls ?></strong> (<?= pct($nb_nuls, $total) ?>%)</li>
    </ul>
</div>

<br>

<?php
// ----------------------------------------------------
// 2️⃣ STATISTIQUES PAR JOUEUR
// ----------------------------------------------------
$joueurs = getAllPlayers($gestion_sportive);
?>

<h3>👥 Statistiques par joueur</h3>

<table class="table">
    <thead>
        <tr>
            <th>Joueur</th>
            <th>Statut</th>
            <th>Poste préféré</th>
            <th>Titularisations</th>
            <th>Remplacements</th>
            <th>Moy. Notes</th>
            <th>Série actuelle</th>
            <th>% Victoires</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($joueurs as $j): ?>
            <?php
                $id = $j["id_joueur"];

                // TITULARISATIONS
                $nbTit = getNbTitularisations($gestion_sportive, $id);

                // REMPLACEMENTS
                $nbRemp = getNbRemplacements($gestion_sportive, $id);

                // MOYENNE DE NOTES
                $avgNote = getAvgNote($gestion_sportive, $id);
                $avgNote = $avgNote ? round($avgNote, 2) : "-";

                // POSTE PRÉFÉRÉ
                $postePref = getBestPoste($gestion_sportive, $id);
                $postePref = $postePref["libelle"] ?? "-";

                // MATCHS CONSÉCUTIFS
                $serie = getSerieConsecutive($gestion_sportive, $id);

                // % VICTOIRES SUR LES MATCHS JOUEURS
                $winRate = getWinRate($gestion_sportive, $id);
                $winRate = $winRate ? round($winRate, 1) . "%" : "0%";
            ?>

            <tr>
                <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
                <td><?= htmlspecialchars($j["statut_libelle"]) ?></td>
                <td><?= htmlspecialchars($postePref) ?></td>
                <td><?= $nbTit ?></td>
                <td><?= $nbRemp ?></td>
                <td><?= $avgNote ?></td>
                <td><?= $serie ?> match(s)</td>
                <td><?= $winRate ?></td>
            </tr>

        <?php endforeach; ?>
    </tbody>
</table>

<?php include "../includes/footer.php"; ?>
