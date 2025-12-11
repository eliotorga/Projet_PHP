<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

// Vérifier ID
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

// Match déjà joué ?
if ($match["etat"] === "JOUE") {
    die("<p style='color:red; font-weight:bold;'>Le résultat de ce match a déjà été saisi.</p>");
}

$error = "";

// Lorsque le formulaire est envoyé
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $score_equipe  = $_POST["score_equipe"] ?? null;
    $score_adverse = $_POST["score_adverse"] ?? null;

    if ($score_equipe === "" || $score_adverse === "" || !is_numeric($score_equipe) || !is_numeric($score_adverse)) {
        $error = "Veuillez saisir deux scores valides.";
    } else {

        // Mise à jour du résultat + passage du match en JOUE
        setMatchResult($gestion_sportive, $id_match, $score_equipe, $score_adverse);

        header("Location: liste_matchs.php");
        exit;
    }
}
?>

<div class="container">

    <h1>🏆 Résultat du match</h1>

    <p>
        <strong><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></strong><br>
        Adversaire : <strong><?= htmlspecialchars($match["adversaire"]) ?></strong><br>
        Lieu : <?= htmlspecialchars($match["lieu"]) ?><br>
    </p>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Score de notre équipe *</label><br>
        <input type="number" name="score_equipe" min="0" required>
        <br><br>

        <label>Score de l'équipe adverse *</label><br>
        <input type="number" name="score_adverse" min="0" required>
        <br><br>

        <button 
            type="submit"
            style="
                padding:10px 18px; 
                background:#28a745; 
                color:white; 
                border:none;
                border-radius:6px;
                cursor:pointer;
            "
        >
            💾 Enregistrer le résultat
        </button>

        <a href="liste_matchs.php" 
           style="margin-left:20px; text-decoration:none;">↩️ Retour</a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
