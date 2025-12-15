<?php
session_start();

/*
 |------------------------------------------
 | IDENTIFIANTS (en dur, conforme au sujet)
 |------------------------------------------
 */
$AUTH_LOGIN = "admin";
$AUTH_PASSWORD = "admin";

$error = "";

/*
 |------------------------------------------
 | Déjà connecté → redirection
 |------------------------------------------
 */
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

/*
 |------------------------------------------
 | Traitement formulaire
 |------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login !== $AUTH_LOGIN || $password !== $AUTH_PASSWORD) {
        $error = "Identifiant ou mot de passe incorrect.";
    } else {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $login;
        $_SESSION["last_activity"] = time();

        // Redirection avec message de succès
        $_SESSION['login_success'] = true;
        header("Location: index.php");
        exit;
    }
}

// Inclure la vue HTML
require __DIR__ . '/vues/login_view.php';
?>