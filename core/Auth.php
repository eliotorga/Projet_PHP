<?php
/**
 * Classe Auth - Gestion de l'authentification
 * Centralise la logique d'authentification et de gestion de session
 */
class Auth {
    private const LOGIN = "admin";
    private const PASSWORD_HASH = '$2y$12$rPJltJboyCt8h9q33Y0Olee7NheJZkO3Cw7y/T7w3ii7uL8FkTFwm';
    private const TIMEOUT = 200; // Timeout en secondes

    /**
     * Vérifie si l'utilisateur est authentifié
     * Redirige vers login.php si non authentifié ou session expirée
     */
    public static function check(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Vérification de l'existence de la session
        if (!isset($_SESSION["user_id"])) {
            header("Location: /Projet_PHP/login.php");
            exit;
        }

        // Vérification du timeout
        if (time() - ($_SESSION["last_activity"] ?? 0) > self::TIMEOUT) {
            session_unset();
            session_destroy();
            header("Location: /Projet_PHP/login.php?expired=1");
            exit;
        }

        // Mise à jour de la dernière activité
        $_SESSION["last_activity"] = time();
    }

    /**
     * Tente d'authentifier un utilisateur
     * @param string $username Nom d'utilisateur
     * @param string $password Mot de passe en clair
     * @return bool True si authentification réussie, false sinon
     */
    public static function login(string $username, string $password): bool {
        if ($username === self::LOGIN && password_verify($password, self::PASSWORD_HASH)) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION["user_id"] = 1;
            $_SESSION["username"] = $username;
            $_SESSION["last_activity"] = time();
            return true;
        }
        return false;
    }

    /**
     * Déconnecte l'utilisateur courant
     */
    public static function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    /**
     * Vérifie si une session est démarrée et la démarre si nécessaire
     */
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Récupère l'ID de l'utilisateur connecté
     * @return int|null ID de l'utilisateur ou null si non connecté
     */
    public static function getUserId(): ?int {
        return $_SESSION["user_id"] ?? null;
    }

    /**
     * Récupère le nom d'utilisateur connecté
     * @return string|null Nom d'utilisateur ou null si non connecté
     */
    public static function getUsername(): ?string {
        return $_SESSION["username"] ?? null;
    }
}
