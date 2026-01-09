<?php
// verification de l'authentification
// redirige vers login si pas connecte ou si session expiree
// ce fichier est inclus sur toutes les pages protegees

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Sécurité : si la session n'existe toujours pas → erreur serveur
if (!isset($_SESSION)) {
    die("Erreur session PHP : session non initialisée");
}

// 2 minutes d'inactivité
$LIMIT = 200;

// Pas connecté
if (!isset($_SESSION["user_id"])) {
    header("Location: /Projet_PHP/login.php");
    exit;
}

// Expiration
if (isset($_SESSION["last_activity"])) {
    if (time() - $_SESSION["last_activity"] > $LIMIT) {
        session_unset();
        session_destroy();
        header("Location: /Projet_PHP/login.php?expired=1");
        exit;
    }
}

// Mise à jour activité
$_SESSION["last_activity"] = time();
