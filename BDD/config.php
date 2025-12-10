<?php
$host = 'localhost';
$dbname = 'projet_php';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // On essaie de sélectionner la base
    $pdo->exec("USE $dbname");
} catch (Exception $e) {
    // PAS D'ERREUR FATALE — l'app continue !
    $pdo = null;
}
