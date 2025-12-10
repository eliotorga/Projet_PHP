<?php include("../includes/header.php"); include("../BDD/config.php");

$id = $_GET["id"];
$match = $pdo->query("SELECT * FROM matchs WHERE id=$id")->fetch();
?>

<form method="POST">
    <label>Résultat</label>
    <select name="resultat">
        <option value="gagne">Gagné</option>
        <option value="perdu">Perdu</option>
        <option value="nul">Nul</option>
    </select>

    <label>Évaluation (0-10)</label>
    <input name="note" type="number" min="0" max="10">

    <button>Modifier</button>
</form>

<?php
if ($_POST) {
    $sql = "UPDATE matchs SET resultat=?, note=? WHERE id=?";
    $pdo->prepare($sql)->execute([$_POST["resultat"], $_POST["note"], $id]);
    header("Location: liste_matchs.php");
}
include("../includes/footer.php");
