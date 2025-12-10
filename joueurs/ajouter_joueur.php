<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<h2>Ajouter joueur</h2>

<form method="POST">
    <input name="nom" placeholder="Nom" required>
    <input name="poste" placeholder="Poste" required>
    <input name="taille" placeholder="Taille">
    <input name="poids" placeholder="Poids">
    <button type="submit">Ajouter</button>
</form>

<?php
if ($_POST) {
    $sql = "INSERT INTO joueurs (nom, poste, taille, poids, actif)
            VALUES (?, ?, ?, ?, 1)";
    $pdo->prepare($sql)->execute([$_POST["nom"], $_POST["poste"], $_POST["taille"], $_POST["poids"]]);
    header("Location: liste_joueurs.php");
}
include("../includes/footer.php");
