<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';

$id = $_GET['id'];
supprimerJoueur($id);

header("Location: liste_joueurs.php");
exit;
