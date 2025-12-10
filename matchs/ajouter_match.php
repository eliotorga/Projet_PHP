<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<form method="POST">
    <input type="date" name="date_match" required>
    <button>Ajouter</button>
</form>

<?php
if ($_POST) {
    $pdo->prepare("INSERT INTO matchs (date_match) VALUES (?)")
        ->execute([$_POST["date_match"]]);
    header("Location: liste_matchs.php");
}
include("../includes/footer.php");
