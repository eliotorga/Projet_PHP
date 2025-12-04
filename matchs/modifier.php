<?php
session_start();
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/match.php';

if (!isset($_GET['id'])) {
    header("Location: liste.php");
    exit;
}

$id = (int) $_GET['id'];
$match = get_match($id);

if (!$match) {
    die("Match introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_match($id, $_POST);
    header("Location: liste.php");
    exit;
}

include "../header.php";
include "../menu.php";
?>

<h1>Modifier un match</h1>

<form method="post">
    Date et heure :
    <input type="datetime-local"
           name="date_heure"
           value="<?= str_replace(' ', 'T', $match['date_heure']) ?>"><br>

    Équipe adverse :
    <input type="text" name="equipe_adverse" value="<?= htmlspecialchars($match['equipe_adverse']) ?>"><br>

    Lieu :
    <select name="lieu">
        <option value="Domicile" <?= ($match['lieu'] === "Domicile") ? "selected" : "" ?>>Domicile</option>
        <option value="Extérieur" <?= ($match['lieu'] === "Extérieur") ? "selected" : "" ?>>Extérieur</option>
    </select><br>

    Résultat :
    <select name="resultat">
        <option value="">Non joué</option>
        <option value="Victoire" <?= ($match['resultat'] === "Victoire") ? "selected" : "" ?>>Victoire</option>
        <option value="Nul" <?= ($match['resultat'] === "Nul") ? "selected" : "" ?>>Nul</option>
        <option value="Défaite" <?= ($match['resultat'] === "Défaite") ? "selected" : "" ?>>
            Défaite
        </option>
    </select><br>

    <button type="submit" class="btn">Enregistrer</button>
</form>

<?php include "../footer.php"; ?>
