<?php include("../includes/header.php"); include("../BDD/config.php");

$id_match = $_GET["id"];
$joueurs = getJoueursActifs($pdo);
?>

<h2>Sélection du match</h2>

<form method="POST">

<h3>Titulaires</h3>
<?php foreach (["Gardien","Défense","Milieu","Attaque"] as $poste) : ?>
    <label><?= $poste ?> :</label>
    <select name="titulaire_<?= strtolower($poste) ?>">
        <?php foreach ($joueurs as $j): ?>
            <option value="<?= $j['id'] ?>"><?= $j['nom'] ?></option>
        <?php endforeach; ?>
    </select><br>
<?php endforeach; ?>

<h3>Remplaçants</h3>
<?php foreach ($joueurs as $j): ?>
    <input type="checkbox" name="rempla[]"
           value="<?= $j['id'] ?>"> <?= $j['nom'] ?><br>
<?php endforeach; ?>

<button>Valider</button>
</form>
