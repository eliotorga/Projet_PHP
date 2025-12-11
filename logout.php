<?php
// Déconnexion
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

session_unset();
session_destroy();

// Redémarrer une session pour le message
session_start();
$_SESSION['message'] = "Vous avez été déconnecté avec succès.";
$_SESSION['message_type'] = "success";

header('Location: login.php');
exit();
?>