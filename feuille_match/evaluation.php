<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_match.php";
require_once "../bdd/db_participation.php";
require_once "../bdd/db_joueur.php";

// Vérifier id_match
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php");
    exit;
}

$id_match = intval($_GET["id_match"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("❌ Match introuvable.");
}

// Un match doit être JOUE pour être évalué
if ($match["etat"] !== "JOUE") {
    die("❌ Ce match n’a pas encore été joué, impossible d’évaluer.");
}

// Récupérer les joueurs ayant participé
$participation = getParticipationByMatch($gestion_sportive, $id_match);

include "../includes/header.php";
?>

<h2>Évaluation des joueurs — 
    <?= htmlspecialchars($match["adversaire"]) ?>
</h2>

<form method="POST" action="save_evaluation.php">
    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <table class="table">
        <thead>
            <tr>
                <th>Joueur</th>
                <th>Poste</th>
                <th>Rôle</th>
                <th>Note</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($participation as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p["prenom"] . " " . $p["nom"]) ?></td>
                <td><?= $p["poste_libelle"] ?? "-" ?></td>
                <td><?= $p["role"] ?></td>

                <td>
                    <select name="note[<?= $p["id_joueur"] ?>]">
                        <option value="">—</option>
                        <option value="1" <?= $p["evaluation"] == 1 ? "selected" : "" ?>>1</option>
                        <option value="2" <?= $p["evaluation"] == 2 ? "selected" : "" ?>>2</option>
                        <option value="3" <?= $p["evaluation"] == 3 ? "selected" : "" ?>>3</option>
                        <option value="4" <?= $p["evaluation"] == 4 ? "selected" : "" ?>>4</option>
                        <option value="5" <?= $p["evaluation"] == 5 ? "selected" : "" ?>>5</option>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <button type="submit" class="btn-primary">💾 Enregistrer</button>
</form>

<?php include "../includes/footer.php"; ?>
