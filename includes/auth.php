<?php
// Gestion de l'authentification (sans base de données)

// Démarrer la session UNIQUEMENT si pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configuration des identifiants (EN DUR DANS LE CODE)
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('ADMIN_USERNAME', 'entraineur');
define('ADMIN_PASSWORD', 'football2024'); // À changer pour la production

/**
 * Vérifie si l'utilisateur est connecté
 */
function est_connecte() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_nom']);
}

/**
 * Rediriger vers la page de connexion si non connecté
 */
function requerir_authentification() {
    if (!est_connecte()) {
        $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
        header('Location: ../login.php');
        exit();
    }
}

/**
 * Authentifier un utilisateur (vérification en dur)
 */
function authentifier($nom_utilisateur, $mot_de_passe) {
    // Vérifier les identifiants en dur
    if ($nom_utilisateur === ADMIN_USERNAME && $mot_de_passe === ADMIN_PASSWORD) {
        // Authentification réussie
        $_SESSION['user_id'] = 1;
        $_SESSION['user_nom'] = $nom_utilisateur;
        $_SESSION['is_admin'] = true;
        
        return true;
    }
    
    return false;
}

/**
 * Déconnecter l'utilisateur
 */
function deconnecter() {
    session_unset();
    session_destroy();
    session_start();
    $_SESSION['message'] = "Vous avez été déconnecté avec succès.";
    $_SESSION['message_type'] = "success";
}
?>