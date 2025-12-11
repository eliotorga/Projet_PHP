<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_participation.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

include __DIR__ . "/../includes/header.php";

// Vérification ID du match
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

$participations = getParticipationByMatch($gestion_sportive, $id_match);

if (empty($participations)) {
    die("<p style='color:red; font-weight:bold;'>Aucun joueur n'a été sélectionné pour ce match.</p>");
}

// Le match doit être JOUE pour évaluer
if ($match["etat"] !== "JOUE") {
    echo "<p style='color:red; font-weight:bold;'>⚠️ Vous devez d'abord saisir le résultat avant d'évaluer les joueurs.</p>";
    echo "<a href='../matchs/resultat_match.php?id=$id_match'>➡️ Saisir le résultat</a>";
    include __DIR__ . "/../includes/footer.php";
    exit;
}

$error = "";
$success = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    foreach ($participations as $p) {
        $id_joueur = $p["id_joueur"];
        if (isset($_POST["note"][$id_joueur])) {
            $note = intval($_POST["note"][$id_joueur]);

            if ($note < 1 || $note > 5) {
                $error = "Une note doit être comprise entre 1 et 5.";
                break;
            }

            updateEvaluation($gestion_sportive, $id_match, $id_joueur, $note);
        }
    }

    if (!$error) {
        $success = "Les évaluations ont bien été enregistrées.";
    }
}

?>

<div class="container">

    <h1>⭐ Évaluation des joueurs</h1>

    <p>
        Match du <strong><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></strong><br>
        Adversaire : <strong><?= htmlspecialchars($match["adversaire"]) ?></strong><br>
    </p>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php if ($success): ?>
        <p style="color:green; font-weight:bold;"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST">

        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr style="background:#ddd;">
                <th>Joueur</th>
                <th>Poste</th>
                <th>Rôle</th>
                <th>Note (1 à 5)</th>
            </tr>

            <?php foreach ($participations as $p): ?>
                <tr>
                    <td>
                        <?= htmlspecialchars($p["prenom"]) . " " . htmlspecialchars($p["nom"]) ?>
                    </td>

                    <td><?= htmlspecialchars($p["poste_libelle"] ?? "-") ?></td>

                    <td><?= htmlspecialchars($p["role"]) ?></td>

                    <td>
                        <select name="note[<?= $p["id_joueur"] ?>]" required>
                            <option value="">-- note --</option>

                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <option value="<?= $i ?>"
                                    <?= ($p["evaluation"] == $i) ? "selected" : "" ?>
                                >
                                    <?= $i ?> ⭐
                                </option>
                            <?php endfor; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

        <br>

        <button 
            type="submit"
            style="
                padding:10px 18px; 
                background:#007bff; 
                color:white;
                border:none;
                border-radius:6px;
                cursor:pointer;
            "
        >
            💾 Enregistrer les évaluations
        </button>

        <a href="../matchs/liste_matchs.php"
           style="margin-left:20px; text-decoration:none;"
        >
            ↩️ Retour aux matchs
        </a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
