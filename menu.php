<?php
session_start();
require_once __DIR__ . '/lib/auth.php';
require_login();
?>

<nav>
    <a href="/Projet_PHP/menu.php">🏠 Accueil</a>
    <a href="/Projet_PHP/joueurs/liste.php">👥 Joueurs</a>
    <a href="/Projet_PHP/matchs/liste.php">⚽ Matchs</a>
    <a href="/Projet_PHP/logout.php" style="float:right;color:#e84118;">🚪 Déconnexion</a>
</nav>
