<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* Vérification ID match */
if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}

$id_match = (int) $_GET["id_match"];

/* Infos du match */
$stmt = $gestion_sportive->prepare("
    SELECT date_heure, adversaire, lieu, resultat
    FROM matchs
    WHERE id_match = ?
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match introuvable.");
}

/* Récupération de la composition */
$stmt = $gestion_sportive->prepare("
    SELECT 
        j.nom,
        j.prenom,
        p.role,
        p.evaluation,
        po.libelle AS poste
    FROM participation p
    JOIN joueur j ON j.id_joueur = p.id_joueur
    LEFT JOIN poste po ON po.id_poste = p.id_poste
    WHERE p.id_match = ?
    ORDER BY p.role DESC, po.libelle
");
$stmt->execute([$id_match]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<h1>👀 Feuille de match</h1>

<p>
    <strong>Date :</strong>
    <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?><br>

    <strong>Adversaire :</strong>
    <?= htmlspecialchars($match["adversaire"]) ?><br>

    <strong>Lieu :</strong>
    <?= htmlspecialchars($match["lieu"]) ?><br>

    <strong>Résultat :</strong>
    <?= $match["resultat"] ?? "Non joué" ?>
</p>

<hr>

<h2>🟢 Titulaires</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Poste</th>
        <th>Joueur</th>
        <th>Évaluation</th>
    </tr>

<?php foreach ($participants as $p): ?>
    <?php if ($p["role"] === "TITULAIRE"): ?>
    <tr>
        <td><?= htmlspecialchars($p["poste"]) ?></td>
        <td><?= htmlspecialchars($p["nom"] . " " . $p["prenom"]) ?></td>
        <td><?= $p["evaluation"] ?? "-" ?></td>
    </tr>
    <?php endif; ?>
<?php endforeach; ?>
</table>

<h2>🟡 Remplaçants</h2>

<table border="1" cellpadding="8">
    <tr>
        <th>Poste</th>
        <th>Joueur</th>
        <th>Évaluation</th>
    </tr>

<?php foreach ($participants as $p): ?>
    <?php if ($p["role"] === "REMPLACANT"): ?>
    <tr>
        <td><?= htmlspecialchars($p["poste"]) ?></td>
        <td><?= htmlspecialchars($p["nom"] . " " . $p["prenom"]) ?></td>
        <td><?= $p["evaluation"] ?? "-" ?></td>
    </tr>
    <?php endif; ?>
<?php endforeach; ?>
</table>

<br>

<a href="../matchs/liste_matchs.php" class="btn">⬅ Retour aux matchs</a>

<?php include "../includes/footer.php"; ?>
