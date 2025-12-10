<?php include("../includes/header.php"); include("../BDD/config.php");

$id = $_GET["id"];
$pdo->query("DELETE FROM joueurs WHERE id=$id");
header("Location: liste_joueurs.php");
