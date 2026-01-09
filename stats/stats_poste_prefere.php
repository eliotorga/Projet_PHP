<?php
// calcule et affiche le poste prefere de chaque joueur
// basé sur la meilleure moyenne d'evaluation par poste

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
?>

<h2>📊 Poste préféré des joueurs</h2>

<p>
    Le poste préféré correspond au poste sur lequel le joueur obtient la
    <strong>meilleure moyenne d’évaluation</strong>.
</p>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Joueur</th>
    <th>Poste préféré</th>
    <th>Moyenne des notes</th>
</tr>
</thead>
<tbody>

<?php foreach ($joueurs as $j): ?>
<?php
$id = $j["id_joueur"];

/* =========================
   CALCUL DU POSTE PRÉFÉRÉ
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT p.libelle AS poste,
           ROUND(AVG(pa.evaluation), 2) AS moyenne
    FROM participation pa
    JOIN poste p ON pa.id_poste = p.id_poste
    WHERE pa.id_joueur = ?
      AND pa.evaluation IS NOT NULL
    GROUP BY pa.id_poste
    ORDER BY moyenne DESC
    LIMIT 1
");
$stmt->execute([$id]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
    <td><?= $result ? htmlspecialchars($result["poste"]) : "—" ?></td>
    <td><?= $result ? $result["moyenne"] : "—" ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
