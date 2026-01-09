<?php
// header du site avec le menu de navigation
// inclus sur toutes les pages
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion d’équipe – Coach</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/Projet_PHP/assets/css/header.css">
</head>

<body>

<header>

    <!-- LOGO -->
    <div class="logo">
        <div class="logo-icon">⚽</div>
        <div class="logo-text">
            Coach Manager<br>
            <span>Gestion d’équipe</span>
        </div>
    </div>

    <!-- MENU -->
    <nav>
        <a href="/Projet_PHP/index.php">
            🏠 <span>Accueil</span>
        </a>
        <a href="/Projet_PHP/joueurs/liste_joueurs.php">
            👥 <span>Joueurs</span>
        </a>
        <a href="/Projet_PHP/matchs/liste_matchs.php">
            📅 <span>Matchs</span>
        </a>
        <a href="/Projet_PHP/stats/stats_equipe.php">
            📊 <span>Statistiques</span>
        </a>
    </nav>

    <!-- PROFIL / LOGOUT -->
    <div class="profile">
        <div class="coach">
            👤 Entraîneur<br>
            <strong>Connecté</strong>
        </div>
        <a href="/Projet_PHP/logout.php" class="logout">
            🚪 Déconnexion
        </a>
    </div>

</header>

<main>
