<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_joueur.php";
require_once __DIR__ . "/../bdd/db_participation.php";

include __DIR__ . "/../includes/header.php";


// --- Stats globales ---
$statsEquipe = getMatchStats($gestion_sportive);

$total = $statsEquipe["total"] ?? 0;
$vic   = $statsEquipe["victoires"] ?? 0;
$def   = $statsEquipe["defaites"] ?? 0;
$nul   = $statsEquipe["nuls"] ?? 0;

$pvic = $total ? round(($vic / $total) * 100, 1) : 0;
$pdef = $total ? round(($def / $total) * 100, 1) : 0;
$pnul = $total ? round(($nul / $total) * 100, 1) : 0;


// --- Stats par joueur ---
$joueurs = getAllPlayers($gestion_sportive);


// Fonction pour calculer le poste préféré d'un joueur
function getPostePrefere(PDO $db, int $id_joueur) {

    $sql = "
        SELECT p.id_poste, t.libelle, AVG(p.evaluation) AS avgNote
        FROM participation p
        JOIN poste t ON t.id_poste = p.id_poste
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? 
          AND p.id_poste IS NOT NULL
          AND p.evaluation IS NOT NULL
          AND m.etat = 'JOUE'
        GROUP BY p.id_poste
        ORDER BY avgNote DESC
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(); // peut être false
}

?>

<div class="container">

    <h1>📊 Statistiques de l’équipe</h1>

    <h2>🏆 Résultats globaux</h2>

    <?php if ($total == 0): ?>
        <p>Aucun match joué pour l’instant.</p>
    <?php else: ?>

        <ul>
            <li>Victoires : <strong><?= $vic ?></strong> (<?= $pvic ?>%)</li>
            <li>Défaites : <strong><?= $def ?></strong> (<?= $pdef ?>%)</li>
            <li>Nuls : <strong><?= $nul ?></strong> (<?= $pnul ?>%)</li>
            <li>Total matchs joués : <strong><?= $total ?></strong></li>
        </ul>

    <?php endif; ?>

    <hr>

    <h2>👥 Statistiques par joueur</h2>

    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse; font-size:14px;">
        <tr style="background:#ddd;">
            <th>Joueur</th>
            <th>Statut</th>
            <th>Poste préféré</th>
            <th>Titularisations</th>
            <th>Remplacements</th>
            <th>Moyenne notes</th>
            <th>Matchs consécutifs</th>
            <th>% Victoires en jouant</th>
        </tr>

        <?php foreach ($joueurs as $j): ?>

            <?php
            $id = $j["id_joueur"];

            $postePref = getPostePrefere($gestion_sportive, $id);
            $nbTit     = getNbTitularisations($gestion_sportive, $id);
            $nbRemp    = getNbRemplacements($gestion_sportive, $id);
            $avgNote   = getAvgNote($gestion_sportive, $id);
            $cons      = getNbMatchsConsecutifs($gestion_sportive, $id);
            $winrate   = getWinRate($gestion_sportive, $id);
            ?>

            <tr>
                <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
                <td><?= htmlspecialchars($j["statut_libelle"]) ?></td>

                <td>
                    <?= $postePref ? htmlspecialchars($postePref["libelle"]) : "-" ?>
                </td>

                <td><?= $nbTit ?></td>
                <td><?= $nbRemp ?></td>

                <td>
                    <?= $avgNote ? round($avgNote, 2) . " ⭐" : "-" ?>
                </td>

                <td><?= $cons ?></td>

                <td>
                    <?= $winrate !== null ? round($winrate, 1) . "%" : "-" ?>
                </td>
            </tr>

        <?php endforeach; ?>
    </table>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
