<?php
// Script pour générer un hachage sécurisé pour le mot de passe 'admin'
$password = 'admin';
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

echo "Nouveau hachage pour 'admin' : " . $hash;
?>
