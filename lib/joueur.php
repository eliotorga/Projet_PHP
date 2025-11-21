<?php
// lib/joueur.php
require_once __DIR__ . '/db.php';

// Récupérer tous les joueurs
function get_all_joueurs(): array {
    global $pdo;
    $sql = "SELECT * FROM joueur ORDER BY nom, prenom";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

// Récupérer un joueur par id
function get_joueur(int $id): ?array {
    global $pdo;
    $sql = "SELECT * FROM joueur WHERE id_joueur = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    $joueur = $stmt->fetch();
    return $joueur ?: null;
}

// Ajouter un joueur
function add_joueur(array $data): void {
    global $pdo;
    $sql = "INSERT INTO joueur 
        (nom, prenom, num_licence, poids_kg, date_naissance, taille_cm, statut, commentaire)
        VALUES (:nom, :prenom, :num_licence, :poids_kg, :date_naissance, :taille_cm, :statut, :commentaire)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom'           => $data['nom'],
        ':prenom'        => $data['prenom'],
        ':num_licence'   => $data['num_licence'],
        ':poids_kg'      => $data['poids_kg'] ?: null,
        ':date_naissance'=> $data['date_naissance'] ?: null,
        ':taille_cm'     => $data['taille_cm'] ?: null,
        ':statut'        => $data['statut'],
        ':commentaire'   => $data['commentaire'] ?? ''
    ]);
}

// Modifier un joueur
function update_joueur(int $id, array $data): void {
    global $pdo;
    $sql = "UPDATE joueur SET
        nom = :nom,
        prenom = :prenom,
        num_licence = :num_licence,
        poids_kg = :poids_kg,
        date_naissance = :date_naissance,
        taille_cm = :taille_cm,
        statut = :statut,
        commentaire = :commentaire
        WHERE id_joueur = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nom'           => $data['nom'],
        ':prenom'        => $data['prenom'],
        ':num_licence'   => $data['num_licence'],
        ':poids_kg'      => $data['poids_kg'] ?: null,
        ':date_naissance'=> $data['date_naissance'] ?: null,
        ':taille_cm'     => $data['taille_cm'] ?: null,
        ':statut'        => $data['statut'],
        ':commentaire'   => $data['commentaire'] ?? '',
        ':id'            => $id
    ]);
}

// Supprimer un joueur
function delete_joueur(int $id): void {
    global $pdo;
    $sql = "DELETE FROM joueur WHERE id_joueur = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
}
