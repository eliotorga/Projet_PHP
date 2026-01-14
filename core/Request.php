<?php
/**
 * Classe Request - Wrapper pour les requêtes HTTP
 * Simplifie l'accès aux données GET/POST
 */
class Request {
    /**
     * Récupère une valeur depuis $_GET
     * @param string $key Clé à récupérer
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur récupérée ou valeur par défaut
     */
    public function get(string $key, $default = null) {
        return $_GET[$key] ?? $default;
    }

    /**
     * Récupère une valeur depuis $_POST
     * @param string $key Clé à récupérer
     * @param mixed $default Valeur par défaut si la clé n'existe pas
     * @return mixed Valeur récupérée ou valeur par défaut
     */
    public function post(string $key, $default = null) {
        return $_POST[$key] ?? $default;
    }

    /**
     * Vérifie si la requête est de type POST
     * @return bool True si POST, false sinon
     */
    public function isPost(): bool {
        return $_SERVER["REQUEST_METHOD"] === "POST";
    }

    /**
     * Vérifie si la requête est de type GET
     * @return bool True si GET, false sinon
     */
    public function isGet(): bool {
        return $_SERVER["REQUEST_METHOD"] === "GET";
    }

    /**
     * Récupère la méthode HTTP de la requête
     * @return string Méthode HTTP (GET, POST, etc.)
     */
    public function getMethod(): string {
        return $_SERVER["REQUEST_METHOD"];
    }

    /**
     * Récupère toutes les données GET et POST fusionnées
     * @return array Tableau associatif de toutes les données
     */
    public function all(): array {
        return array_merge($_GET, $_POST);
    }

    /**
     * Récupère toutes les données GET
     * @return array Tableau associatif des données GET
     */
    public function allGet(): array {
        return $_GET;
    }

    /**
     * Récupère toutes les données POST
     * @return array Tableau associatif des données POST
     */
    public function allPost(): array {
        return $_POST;
    }

    /**
     * Vérifie si une clé existe dans $_GET
     * @param string $key Clé à vérifier
     * @return bool True si la clé existe, false sinon
     */
    public function hasGet(string $key): bool {
        return isset($_GET[$key]);
    }

    /**
     * Vérifie si une clé existe dans $_POST
     * @param string $key Clé à vérifier
     * @return bool True si la clé existe, false sinon
     */
    public function hasPost(string $key): bool {
        return isset($_POST[$key]);
    }
}
