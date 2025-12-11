<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';
require_once __DIR__ . '/../bdd/config.php';

if ($_POST) {
    ajouterJoueur($_POST);
    header("Location: liste_joueurs.php");
    exit;
}

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Ajouter un joueur</h2>

<form method="POST">

    <label>Nom</label>
    <input name="nom" required>

    <label>Prénom</label>
    <input name="prenom" required>

    <label>Numéro de licence</label>
    <input name="num_licence" required>

    <label>Date de naissance</label>
    <input type="date" name="date_naissance" required>

    <label>Taille (cm)</label>
    <input type="number" name="taille_cm">

    <label>Poids (kg)</label>
    <input type="number" step="0.1" name="poids_kg">

    <label>Statut</label>
    <select name="id_statut">
        <option value="1">Actif</option>
        <option value="2">Blessé</option>
        <option value="3">Suspendu</option>
        <option value="4">Absent</option>
    </select>

    <button type="submit" class="btn">Ajouter</button>

</form>

<?php include "../includes/footer.php"; ?>
