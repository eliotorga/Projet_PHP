<?php
/**
 * Classe Response - Gestion des réponses HTTP
 * Centralise les redirections et la gestion des messages flash
 */
class Response {
    /**
     * Redirige vers une URL et termine l'exécution
     * @param string $url URL de destination
     */
    public static function redirect(string $url): void {
        header("Location: $url");
        exit;
    }

    /**
     * Définit un message de succès dans la session
     * @param string $message Message de succès
     */
    public static function setSuccess(string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['success_message'] = $message;
    }

    /**
     * Définit un message d'erreur dans la session
     * @param string $message Message d'erreur
     */
    public static function setError(string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['error_message'] = $message;
    }

    /**
     * Récupère et supprime le message de succès de la session
     * @return string|null Message de succès ou null
     */
    public static function getSuccess(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['success_message'])) {
            $msg = $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            return $msg;
        }
        return null;
    }

    /**
     * Récupère et supprime le message d'erreur de la session
     * @return string|null Message d'erreur ou null
     */
    public static function getError(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['error_message'])) {
            $msg = $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            return $msg;
        }
        return null;
    }

    /**
     * Vérifie si un message de succès existe
     * @return bool True si un message existe, false sinon
     */
    public static function hasSuccess(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['success_message']);
    }

    /**
     * Vérifie si un message d'erreur existe
     * @return bool True si un message existe, false sinon
     */
    public static function hasError(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['error_message']);
    }

    /**
     * Définit un message info dans la session
     * @param string $message Message info
     */
    public static function setInfo(string $message): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['info_message'] = $message;
    }

    /**
     * Récupère et supprime le message info de la session
     * @return string|null Message info ou null
     */
    public static function getInfo(): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (isset($_SESSION['info_message'])) {
            $msg = $_SESSION['info_message'];
            unset($_SESSION['info_message']);
            return $msg;
        }
        return null;
    }
}
