<?php
// affiche les statistiques simples de tous les joueurs
// tableau avec titularisations, remplacements et moyenne des evaluations

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

include __DIR__ . "/../includes/header.php";

/* =========================
   RÉCUPÉRATION DES JOUEURS
========================= */
$joueurs = $gestion_sportive->query("
    SELECT j.id_joueur, j.nom, j.prenom, s.libelle AS statut
    FROM joueur j
    JOIN statut s ON j.id_statut = s.id_statut
    ORDER BY j.nom, j.prenom
")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>📊 Statistiques joueurs (version simple)</h2>

<table border="1" cellpadding="6" cellspacing="0">
<thead>
<tr>
    <th>Joueur</th>
    <th>Statut</th>
    <th>Titularisations</th>
    <th>Remplacements</th>
    <th>Moyenne des notes</th>
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
        SUM(role = 'REMPLACANT') AS remplacants
    FROM participation
    WHERE id_joueur = ?
");
$stmt->execute([$id]);
$roles = $stmt->fetch(PDO::FETCH_ASSOC);

/* =========================
   MOYENNE DES ÉVALUATIONS
========================= */
$stmt = $gestion_sportive->prepare("
    SELECT ROUND(AVG(evaluation), 2)
    FROM participation
    WHERE id_joueur = ? AND evaluation IS NOT NULL
");
$stmt->execute([$id]);
$moyenne = $stmt->fetchColumn();
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>
    <td><?= htmlspecialchars($j["statut"]) ?></td>
    <td><?= $roles["titulaires"] ?? 0 ?></td>
    <td><?= $roles["remplacants"] ?? 0 ?></td>
    <td><?= $moyenne !== null ? $moyenne : "—" ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
