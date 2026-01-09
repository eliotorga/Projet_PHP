<?php
// fichier de configuration principale
// contient la connexion a la base de donnees et les identifiants de connexion
// ce fichier est inclus partout dans le projet


/* ---------------------------
   CONFIGURATION DE SÉCURITÉ
----------------------------*/

// Configuration du hachage des mots de passe
define('PASSWORD_ALGO', PASSWORD_BCRYPT);
define('PASSWORD_OPTIONS', [
    'cost' => 12  // Coût du hachage (plus élevé = plus sécurisé mais plus lent)
]);


/* ---------------------------
   IDENTIFIANTS DE CONNEXION
   (exigence du professeur : PAS DE TABLE utilisateur)
----------------------------*/

$AUTH_LOGIN = "admin";  // Nom d'utilisateur
$AUTH_PASSWORD = "admin"; // Mot de passe en clair (à ne faire qu'en développement)


/* ---------------------------
   PARAMÈTRES BASE DE DONNÉES
----------------------------*/

$DB_HOST = "localhost";
$DB_NAME = "gestion_equipe";
$DB_USER = "root";
$DB_PASS = "";  // sous XAMPP / WAMP, souvent vide


/* ---------------------------
   CONNEXION PDO SÉCURISÉE
----------------------------*/

try {
    $gestion_sportive = new PDO(
        "mysql:host={$DB_HOST};dbname={$DB_NAME};charset=utf8",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Exceptions sur erreurs
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,   // Résultats en tableau associatif
            PDO::ATTR_EMULATE_PREPARES => false                // Prépare réellement côté MySQL (anti-injection)
        ]
    );
} catch (PDOException $e) {
    die("❌ Erreur de connexion à la base de données : " . $e->getMessage());
}


/* ---------------------------
   SÉCURITÉ SESSION
----------------------------*/

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
