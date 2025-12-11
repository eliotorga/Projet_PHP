<?php
require_once __DIR__ . "/includes/config.php";

// Détruire toutes les variables de session
$_SESSION = [];

// Détruire le cookie de session (sécurité navigateur)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Détruire la session elle-même
session_destroy();

// Redirection vers la page de connexion
header("Location: login.php");
exit;
