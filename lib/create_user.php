<?php
require_once 'lib/db.php';

$login = 'coach';
$plain_password = '1234'; // mot de passe choisi

$hash = password_hash($plain_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO utilisateur (login, mot_de_passe_hash) VALUES (?, ?)");
$stmt->execute([$login, $hash]);

echo "Utilisateur créé.";