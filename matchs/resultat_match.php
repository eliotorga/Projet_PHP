<?php
// permet de saisir le score final d'un match termine
// passe le match a l'etat joue et calcule automatiquement le resultat

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/match.php";

include __DIR__ . "/../includes/header.php";

// Vérifier ID
if (!isset($_GET["id"])) {
    die("<p class='error-message'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p class='error-message'>Match introuvable.</p>");
}

// Match déjà joué ?
if ($match["etat"] === "JOUE") {
    die("<p class='error-message'>Le résultat de ce match a déjà été saisi.</p>");
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
        <p class="error-message"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Score de notre équipe *</label><br>
        <input type="number" name="score_equipe" min="0" required>
        <br><br>

        <label>Score de l'équipe adverse *</label><br>
        <input type="number" name="score_adverse" min="0" required>
        <br><br>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> Enregistrer
        </button>

        <a href="liste_matchs.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Retour
        </a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/resultats.css">
<link rel="stylesheet" href="../assets/css/feuille_match.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
