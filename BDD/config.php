<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestion_equipe');
define('DB_USER', 'root');
define('DB_PASS', '');

// Variable globale pour l'état de la connexion
$GLOBALS['db_connected'] = false;
$GLOBALS['db_error_message'] = '';

/**
 * Vérifie si la base de données est accessible
 */
function checkDatabaseConnection() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_SILENT,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        $GLOBALS['db_connected'] = true;
        return $pdo;
        
    } catch (PDOException $e) {
        $GLOBALS['db_connected'] = false;
        $GLOBALS['db_error_message'] = "MySQL non disponible";
        return false;
    }
}

/**
 * Vérifie si la BDD est connectée
 */
function isDatabaseConnected() {
    if (!isset($GLOBALS['db_connected'])) {
        checkDatabaseConnection();
    }
    return $GLOBALS['db_connected'];
}

/**
 * Retourne le message d'erreur de connexion
 */
function getDatabaseError() {
    return $GLOBALS['db_error_message'] ?? 'Inconnu';
}

// Vérifier la connexion au chargement
checkDatabaseConnection();
?>