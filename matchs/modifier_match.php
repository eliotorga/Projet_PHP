<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}

$id_match = intval($_GET["id_match"]);

// Charger les informations du match
$req = $gestion_sportive->prepare("SELECT * FROM matchs WHERE id_match = ?");
$req->execute([$id_match]);
$match = $req->fetch();

if (!$match) {
    die("Match introuvable.");
}

// Vérifier s’il existe déjà une composition
$reqCompo = $gestion_sportive->prepare("
    SELECT p.*, j.nom, j.prenom, j.taille, j.poids,
           (SELECT ROUND(AVG(evaluation),1)
            FROM participer pp 
            WHERE pp.id_joueur = j.id_joueur AND pp.evaluation IS NOT NULL)
            AS moyenne
    FROM participer p
    INNER JOIN joueurs j ON j.id_joueur = p.id_joueur
    WHERE p.id_match = ?
    ORDER BY p.titulaire DESC, j.nom ASC
");
$reqCompo->execute([$id_match]);
$composition = $reqCompo->fetchAll(PDO::FETCH_ASSOC);

$hasCompo = count($composition) > 0;

// Vérifier si le match est passé
$matchPasse = ($match["date_heure"] < date("Y-m-d H:i:s"));

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Modifier le match : <?= htmlspecialchars($match["equipe_adverse"]) ?></h2>

<p><strong>Date :</strong> <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></p>
<p><strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"]) ?></p>

<hr>

<?php if (!$hasCompo): ?>

    <h3>Aucune composition enregistrée</h3>

    <?php if (!$matchPasse): ?>
        <a href="../feuille_match/composition.php?id_match=<?= $id_match ?>" class="btn">
            ➕ Créer la feuille de match
        </a>
    <?php else: ?>
        <p style="color:red">Ce match est déjà passé, impossible de créer une composition.</p>
    <?php endif; ?>

<?php else: ?>

    <h3>Composition enregistrée</h3>

    <?php if (!$matchPasse): ?>
        <a href="../feuille_match/composition.php?id_match=<?= $id_match ?>" class="btn">
            ✏️ Modifier la composition
        </a>
    <?php endif; ?>

    <table border="1" cellpadding="8" style="margin-top:15px;">
        <thead>
            <tr>
                <th>Joueur</th>
                <th>Poste</th>
                <th>Statut</th>
                <th>Taille</th>
                <th>Poids</th>
                <th>Titulaire ?</th>
                <th>Moyenne</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($composition as $c): ?>
                <tr>
                    <td><?= htmlspecialchars($c["nom"] . " " . $c["prenom"]) ?></td>
                    <td><?= htmlspecialchars($c["poste"]) ?></td>
                    <td><?= $c["titulaire"] ? "Titulaire" : "Remplaçant" ?></td>
                    <td><?= $c["taille"] ?> cm</td>
                    <td><?= $c["poids"] ?> kg</td>
                    <td><?= $c["titulaire"] ? "✔️" : "❌" ?></td>
                    <td><?= $c["moyenne"] ?: "-" ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

<?php endif; ?>

<hr>

<h3>Résultat du match</h3>

<?php if (!$matchPasse): ?>
    <p>Ce match n'a pas encore eu lieu.</p>
<?php else: ?>
    <?php if ($match["resultat"] === null): ?>
        <p>Aucun résultat enregistré.</p>
        <a href="resultat_match.php?id_match=<?= $id_match ?>" class="btn">Saisir le résultat</a>
    <?php else: ?>
        <p><strong>Résultat :</strong> <?= htmlspecialchars($match["resultat"]) ?></p>
        <a href="resultat_match.php?id_match=<?= $id_match ?>" class="btn">Modifier le résultat</a>
    <?php endif; ?>

    <?php if ($hasCompo): ?>
        <br><br>
        <a href="../feuille_match/evaluation.php?id_match=<?= $id_match ?>" class="btn">
            ⭐ Évaluer les joueurs
        </a>
    <?php endif; ?>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
