<?php
/**
 * Classe Database - Singleton pour la connexion PDO
 * Centralise la gestion de la connexion à la base de données
 */
class Database {
    private static ?PDO $instance = null;

    /**
     * Récupère l'instance unique de PDO
     * @return PDO Instance PDO configurée
     */
    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    "mysql:host=localhost;dbname=gestion_equipe;charset=utf8",
                    "root",
                    "",
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    ]
                );
            } catch (PDOException $e) {
                die("❌ Erreur de connexion à la base de données : " . $e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Empêche le clonage de l'instance
     */
    private function __clone() {}

    /**
     * Empêche la désérialisation de l'instance
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
