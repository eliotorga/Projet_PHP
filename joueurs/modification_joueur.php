<?php include("../includes/header.php"); include("../BDD/config.php");

$id = $_GET["id"];
$joueur = $pdo->query("SELECT * FROM joueurs WHERE id=$id")->fetch();
?>

<form method="POST">
    <input name="nom" value="<?= $joueur['nom'] ?>">
    <input name="poste" value="<?= $joueur['poste'] ?>">
    <input name="taille" value="<?= $joueur['taille'] ?>">
    <input name="poids" value="<?= $joueur['poids'] ?>">
    <select name="actif">
        <option value="1" <?= $joueur['actif'] ? "selected" : "" ?>>Actif</option>
        <option value="0" <?= !$joueur['actif'] ? "selected" : "" ?>>Inactif</option>
    </select>
    <button>Modifier</button>
</form>

<?php
if ($_POST) {
    $sql = "UPDATE joueurs SET nom=?, poste=?, taille=?, poids=?, actif=? WHERE id=?";
    $pdo->prepare($sql)->execute([
        $_POST["nom"], $_POST["poste"], $_POST["taille"], $_POST["poids"], $_POST["actif"], $id
    ]);
    header("Location: liste_joueurs.php");
}
include("../includes/footer.php");
