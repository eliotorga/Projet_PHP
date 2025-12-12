<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";
require_once "../bdd/db_joueur.php";
require_once "../bdd/db_poste.php";

include "../includes/header.php";

/****************************************
 * 1) STATISTIQUES GLOBALES DE L’ÉQUIPE
 ****************************************/

// Matchs joués (où résultat existe)
$req = $gestion_sportive->query("
    SELECT resultat
    FROM matchs
    WHERE resultat IS NOT NULL
");
$rows = $req->fetchAll();

$total = count($rows);
$wins = count(array_filter($rows, fn($r) => $r["resultat"] === "VICTOIRE"));
$loss = count(array_filter($rows, fn($r) => $r["resultat"] === "DEFAITE"));
$draw = count(array_filter($rows, fn($r) => $r["resultat"] === "NUL"));

function pct($value, $total) {
    return $total == 0 ? "0%" : round(($value / $total) * 100, 1) . "%";
}

?>

<h2>Statistiques de l’équipe</h2>

<table border="1" cellpadding="10">
    <tr><th>Total matchs joués</th><td><?= $total ?></td></tr>
    <tr><th>Victoires</th><td><?= $wins ?> (<?= pct($wins,$total) ?>)</td></tr>
    <tr><th>Défaites</th><td><?= $loss ?> (<?= pct($loss,$total) ?>)</td></tr>
    <tr><th>Nuls</th><td><?= $draw ?> (<?= pct($draw,$total) ?>)</td></tr>
</table>

<br><hr><br>

<h2>Statistiques par joueur</h2>

<table border="1" cellpadding="8" width="100%">
    <thead>
        <tr>
            <th>Joueur</th>
            <th>Statut</th>
            <th>Poste préféré</th>
            <th>Titularisations</th>
            <th>Remplacements</th>
            <th>Moyenne des notes</th>
            <th>% victoires quand il joue</th>
            <th>Matchs consécutifs</th>
        </tr>
    </thead>

    <tbody>
        <?php
        $joueurs = getAllPlayers($gestion_sportive);

        foreach ($joueurs as $j):

            $id = $j["id_joueur"];

            // Titularisations
            $tit = getNbTitularisations($gestion_sportive, $id);

            // Remplacements
            $remp = getNbRemplacements($gestion_sportive, $id);

            // Moyenne notes
            $avg = getAvgNote($gestion_sportive, $id);
            $avg = $avg ? round($avg, 2) : "-";

            // Matchs consécutifs
            $cons = getNbMatchsConsecutifs($gestion_sportive, $id);

            // Pourcentage de victoires
            $winrate = getWinRate($gestion_sportive, $id);
            $winrate = $winrate ? round($winrate, 1) . "%" : "-";

            // Poste préféré : celui où il a la meilleure moyenne
            $stmt = $gestion_sportive->prepare("
                SELECT t.libelle, AVG(p.evaluation) AS note
                FROM participation p
                JOIN poste t ON t.id_poste = p.id_poste
                WHERE p.id_joueur = ? AND p.evaluation IS NOT NULL
                GROUP BY t.id_poste
                ORDER BY note DESC
                LIMIT 1
            ");
            $stmt->execute([$id]);
            $bestPoste = $stmt->fetch();
            $poste_pref = $bestPoste["libelle"] ?? "-";

        ?>
        <tr>
            <td><?= $j["nom"] . " " . $j["prenom"] ?></td>
            <td><?= $j["statut_libelle"] ?></td>
            <td><?= $poste_pref ?></td>
            <td><?= $tit ?></td>
            <td><?= $remp ?></td>
            <td><?= $avg ?></td>
            <td><?= $winrate ?></td>
            <td><?= $cons ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include "../includes/footer.php"; ?>
