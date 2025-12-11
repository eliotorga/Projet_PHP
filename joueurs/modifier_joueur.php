<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';

$id = $_GET['id'];
$joueur = getJoueurById($id);

if ($_POST) {
    modifierJoueur($id, $_POST);
    header("Location: liste_joueurs.php");
    exit;
}

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Modifier joueur</h2>

<form method="POST">

    <label>Nom</label>
    <input name="nom" value="<?= $joueur['nom'] ?>">

    <label>Prénom</label>
    <input name="prenom" value="<?= $joueur['prenom'] ?>">

    <label>Numéro licence</label>
    <input name="num_licence" value="<?= $joueur['num_licence'] ?>">

    <label>Date de naissance</label>
    <input type="date" name="date_naissance" value="<?= $joueur['date_naissance'] ?>">

    <label>Taille</label>
    <input name="taille_cm" value="<?= $joueur['taille_cm'] ?>">

    <label>Poids</label>
    <input name="poids_kg" value="<?= $joueur['poids_kg'] ?>">

    <label>Statut</label>
    <select name="id_statut">
        <option value="1" <?= $joueur['id_statut']==1?'selected':'' ?>>Actif</option>
        <option value="2" <?= $joueur['id_statut']==2?'selected':'' ?>>Blessé</option>
        <op
