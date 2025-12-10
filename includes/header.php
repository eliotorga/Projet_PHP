<?php
session_start();
if (!isset($_SESSION["auth"])) {
    header("Location: authentication/login.php");
    exit;
}
?>
<nav>
    <a href="/Projet_PHP/index.php">Accueil</a> |
    <a href="/Projet_PHP/joueurs/liste_joueurs.php">Joueurs</a> |
    <a href="/Projet_PHP/matchs/liste_matchs.php">Matchs</a> |
    <a href="/Projet_PHP/statistiques/stats_equipe.php">Stats</a> |
    <a href="/Projet_PHP/authentication/login.php">Déconnexion</a>
</nav>
<hr>
