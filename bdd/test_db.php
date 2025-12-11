<?php
require_once __DIR__ . "/../includes/config.php";

try {
    $test = $gestion_sportive->query("SELECT 1+1 AS result")->fetch();
    echo "<h2 style='color:green;'>✔ Connexion à la base OK - Résultat : " . $test['result'] . "</h2>";
} 
catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Erreur SQL : " . $e->getMessage() . "</h2>";
}
