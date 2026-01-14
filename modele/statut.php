<?php
// fonctions pour gerer les statuts des joueurs (actif, blesse, suspendu)
// operations crud sur la table statut

function getAllStatuts(PDO $db) {
    $sql = "SELECT * FROM statut ORDER BY libelle";
    return $db->query($sql)->fetchAll();
}

function getStatutById(PDO $db, int $id_statut) {
    $stmt = $db->prepare("SELECT * FROM statut WHERE id_statut = ?");
    $stmt->execute([$id_statut]);
    return $stmt->fetch();
}

function insertStatut(PDO $db, string $code, string $libelle) {
    $stmt = $db->prepare("INSERT INTO statut (code, libelle) VALUES (?, ?)");
    return $stmt->execute([$code, $libelle]);
}

function updateStatut(PDO $db, int $id_statut, string $code, string $libelle) {
    $stmt = $db->prepare("UPDATE statut SET code = ?, libelle = ? WHERE id_statut = ?");
    return $stmt->execute([$code, $libelle, $id_statut]);
}

function deleteStatut(PDO $db, int $id_statut) {
    $stmt = $db->prepare("DELETE FROM statut WHERE id_statut = ?");
    return $stmt->execute([$id_statut]);
}
