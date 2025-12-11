<?php
// bdd/utilisateurs.php
require_once __DIR__ . "/config.php";

function getUtilisateurParLogin(string $login): ?array {
    $pdo = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}
