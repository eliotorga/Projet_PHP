<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Gestion sportive</title>
    <link rel="stylesheet" href="/Projet_PHP/assets/css/style.css">
</head>
<body>

<nav style="background:#333; padding:10px;">
    <a href="/Projet_PHP/index.php" style="color:white; margin-right:15px;">🏠 Accueil</a>
    <a href="/Projet_PHP/joueurs/liste_joueurs.php" style="color:white; margin-right:15px;">👥 Joueurs</a>
    <a href="/Projet_PHP/matchs/liste_matchs.php" style="color:white; margin-right:15px;">📅 Matchs</a>
    <a href="/Projet_PHP/feuille_match/historique_feuille.php?id=1" style="color:white; margin-right:15px;">📝 Feuille de match</a>
    <a href="/Projet_PHP/stats/stats_equipe.php" style="color:white; margin-right:15px;">📊 Statistiques</a>

    <?php if (isset($_SESSION["user_id"])): ?>
        <a href="/Projet_PHP/logout.php" style="color:#ff8080;">🚪 Déconnexion</a>
    <?php endif; ?>
</nav>

<div class="container" style="padding:20px;">
