<?php include("../includes/header.php"); include("../BDD/config.php");

$id = $_GET["id"];
$pdo->query("DELETE FROM matchs WHERE id=$id");
header("Location: liste_matchs.php");
