<?php
// page de connexion
// verifie le login et mot de passe puis redirige vers le dashboard

session_start();
require_once __DIR__ . '/includes/config.php';

$error = "";

// Si déjà connecté, on redirige
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}


// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    // Vérification simple des identifiants
    if ($login === $AUTH_LOGIN && password_verify($password, $AUTH_PASSWORD_HASH)) {
        // Connexion réussie
        $_SESSION["user_id"] = $login;
        $_SESSION["last_activity"] = time();
        
        // Protection basique
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        
        header("Location: index.php");
        exit;
    } else {
        $error = "Identifiant ou mot de passe incorrect.";
        // Délai pour ralentir les attaques par force brute
        usleep(500000); // 0.5 seconde
    }
}

// Inclure la vue HTML
require __DIR__ . '/vues/login_view.php';
?>
