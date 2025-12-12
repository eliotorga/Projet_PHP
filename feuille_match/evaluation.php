<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";

if (!isset($_GET["id_match"])) die("Match non spécifié.");
$id_match = intval($_GET["id_match"]);

// Match doit être passé
$stmt = $gestion_sportive->prepare("SELECT * FROM matchs WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();

if ($match["date_heure"] > date("Y-m-d H:i:s")) {
    die("❌ Ce match n'a pas encore eu lieu.");
}

$participants = getParticipationByMatch($gestion_sportive, $id_match);

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Évaluation des joueurs — vs <?= $match["equipe_adverse"] ?></h2>

<form method="POST" action="save_evaluation.php">

    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <table border="1" cellpadding="8">
        <thead>
            <tr>
                <th>Joueur</th>
                <th>Poste</th>
                <th>Rôle</th>
                <th>Note</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach ($participants as $p): ?>
            <tr>
                <td><?= $p["nom"] . " " . $p["prenom"] ?></td>
                <td><?= $p["poste_libelle"] ?></td>
                <td><?= $p["role"] ?></td>
                <td>
                    <select name="note[<?= $p['id_joueur'] ?>]">
                        <option value="">-- Note --</option>
                        <?php for ($i=1; $i<=5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> ⭐</option>
                        <?php endfor; ?>
                    </select>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <button class="btn">💾 Enregistrer</button>
</form>

<?php include "../includes/footer.php"; ?>
