<?php
// header du site avec le menu de navigation
// inclus sur toutes les pages
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion d'équipe – Coach</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Fichiers CSS globaux -->
    <link rel="stylesheet" href="/assets/css/global.css">
    <link rel="stylesheet" href="/assets/css/header.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        <a href="/index.php">
            🏠 <span>Accueil</span>
        </a>
        <a href="/joueurs/liste_joueurs.php">
            👥 <span>Joueurs</span>
        </a>
        <a href="/matchs/liste_matchs.php">
            📅 <span>Matchs</span>
        </a>
        <a href="/stats/stats_equipe.php">
            📊 <span>Statistiques</span>
        </a>
    </nav>

    <!-- PROFIL / LOGOUT -->
    <div class="profile">
        <div class="coach">
            👤 Entraîneur<br>
            <strong>Connecté</strong>
        </div>
        <a href="/logout.php" class="logout">
            🚪 Déconnexion
        </a>
    </div>

</header>

<main>
