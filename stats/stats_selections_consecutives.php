<?php
// calcule le nombre de matchs consecutifs ou chaque joueur a participe
// compte a partir du match le plus recent jusqu'a la premiere absence

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

include __DIR__ . "/../includes/header.php";

/* =========================
   RÉCUPÉRATION DES JOUEURS
========================= */
$joueurs = $gestion_sportive->query("
    SELECT id_joueur, nom, prenom
    FROM joueur
    ORDER BY nom, prenom
")->fetchAll(PDO::FETCH_ASSOC);


  //RÉCUPÉRATION DES MATCHS JOUÉS
  // (triés du plus récent au plus ancien)

$matchs = $gestion_sportive->query("
    SELECT id_match
    FROM matchs
    WHERE resultat IS NOT NULL
    ORDER BY date_heure DESC
")->fetchAll(PDO::FETCH_COLUMN);
?>

<h2>📊 Sélections consécutives à date</h2>

<p>
    Cette statistique correspond au nombre de matchs <strong>consécutifs</strong>
    auxquels le joueur a participé, en partant du match le plus récent.
</p>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Joueur</th>
    <th>Sélections consécutives</th>
</tr>
</thead>
<tbody>

<?php foreach ($joueurs as $j): ?>
<?php
$id = $j["id_joueur"];
$consecutifs = 0;

/* =========================
   CALCUL DES CONSÉCUTIFS
========================= */
foreach ($matchs as $id_match) {
    $stmt = $gestion_sportive->prepare("
        SELECT COUNT(*)
        FROM participation
        WHERE id_match = ? AND id_joueur = ?
    ");
    $stmt->execute([$id_match, $id]);

    if ($stmt->fetchColumn() > 0) {
        $consecutifs++;
    } else {
        break; // première absence → on s'arrête
    }
}
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
    <td><?= $consecutifs ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
