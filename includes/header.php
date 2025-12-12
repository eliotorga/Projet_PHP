<?php
// header.php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion d’équipe – Coach</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
    /* =====================
       RESET + BASE
    ===================== */
    * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
        font-family: "Segoe UI", Roboto, Arial, sans-serif;
    }

    body {
        background-color: #f4f6f8;
        color: #222;
    }

    a {
        text-decoration: none;
        color: inherit;
    }

    /* =====================
       HEADER GLOBAL
    ===================== */
    header {
        background: linear-gradient(135deg, #0b3d1e, #1e7c3a);
        padding: 18px 30px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: 0 6px 20px rgba(0,0,0,0.25);
        position: sticky;
        top: 0;
        z-index: 1000;
    }

    /* LOGO */
    .logo {
        display: flex;
        align-items: center;
        gap: 12px;
        color: #fff;
    }

    .logo-icon {
        background: rgba(255,255,255,0.15);
        padding: 10px;
        border-radius: 12px;
        font-size: 1.5em;
    }

    .logo-text {
        font-size: 1.3em;
        font-weight: bold;
        line-height: 1.1;
    }

    .logo-text span {
        font-size: 0.75em;
        font-weight: normal;
        opacity: 0.85;
    }

    /* =====================
       MENU
    ===================== */
    nav {
        display: flex;
        gap: 18px;
        align-items: center;
    }

    nav a {
        color: #fff;
        padding: 10px 16px;
        border-radius: 12px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: background 0.2s, transform 0.2s;
    }

    nav a:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-2px);
    }

    nav a.active {
        background: rgba(255,255,255,0.25);
    }

    /* =====================
       PROFIL / LOGOUT
    ===================== */
    .profile {
        display: flex;
        align-items: center;
        gap: 15px;
        color: #fff;
    }

    .profile .coach {
        text-align: right;
        font-size: 0.9em;
        opacity: 0.9;
    }

    .logout {
        background: rgba(0,0,0,0.25);
        padding: 10px 14px;
        border-radius: 10px;
        font-weight: bold;
        transition: background 0.2s;
    }

    .logout:hover {
        background: rgba(0,0,0,0.4);
    }

    /* =====================
       CONTENU
    ===================== */
    main {
        padding: 30px;
    }

    /* =====================
       RESPONSIVE
    ===================== */
    @media (max-width: 900px) {
        nav {
            gap: 8px;
        }

        nav a span {
            display: none;
        }
    }
    </style>
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
