<?php
// fonctions pour gerer les postes de football (gardien, defenseur, milieu, attaquant)
// operations crud sur la table poste

function getAllPostes(PDO $db) {
    $sql = "SELECT * FROM poste ORDER BY libelle";
    return $db->query($sql)->fetchAll();
}

function getPosteById(PDO $db, int $id_poste) {
    $stmt = $db->prepare("SELECT * FROM poste WHERE id_poste = ?");
    $stmt->execute([$id_poste]);
    return $stmt->fetch();
}

function insertPoste(PDO $db, string $code, string $libelle) {
    $stmt = $db->prepare("INSERT INTO poste (code, libelle) VALUES (?, ?)");
    return $stmt->execute([$code, $libelle]);
}

function updatePoste(PDO $db, int $id_poste, string $code, string $libelle) {
    $stmt = $db->prepare("UPDATE poste SET code = ?, libelle = ? WHERE id_poste = ?");
    return $stmt->execute([$code, $libelle, $id_poste]);
}

function deletePoste(PDO $db, int $id_poste) {
    $stmt = $db->prepare("DELETE FROM poste WHERE id_poste = ?");
    return $stmt->execute([$id_poste]);
}
