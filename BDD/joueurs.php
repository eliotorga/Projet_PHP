<?php
/**
 * Fonctions pour la gestion des joueurs
 */

/**
 * Récupère tous les joueurs
 */
function getAllJoueurs($filtreStatut = null) {
    $sql = "SELECT * FROM joueur";
    $params = [];
    
    if ($filtreStatut) {
        $sql .= " WHERE statut = ?";
        $params[] = $filtreStatut;
    }
    
    $sql .= " ORDER BY nom, prenom";
    
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Récupère un joueur par son ID
 */
function getJoueurById($id) {
    $sql = "SELECT * FROM joueur WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    if ($stmt) {
        return $stmt->fetch();
    }
    return false;
}

/**
 * Ajoute un nouveau joueur
 */
function addJoueur($data) {
    $sql = "INSERT INTO joueur (nom, prenom, numero_licence, date_naissance, taille_cm, poids_kg, statut) 
            VALUES (:nom, :prenom, :numero_licence, :date_naissance, :taille_cm, :poids_kg, :statut)";
    
    return executeQuery($sql, [
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'numero_licence' => $data['numero_licence'],
        'date_naissance' => $data['date_naissance'],
        'taille_cm' => $data['taille_cm'],
        'poids_kg' => $data['poids_kg'],
        'statut' => $data['statut']
    ]);
}

/**
 * Met à jour un joueur
 */
function updateJoueur($id, $data) {
    $sql = "UPDATE joueur SET 
            nom = :nom,
            prenom = :prenom,
            numero_licence = :numero_licence,
            date_naissance = :date_naissance,
            taille_cm = :taille_cm,
            poids_kg = :poids_kg,
            statut = :statut
            WHERE id = :id";
    
    $params = [
        'id' => $id,
        'nom' => $data['nom'],
        'prenom' => $data['prenom'],
        'numero_licence' => $data['numero_licence'],
        'date_naissance' => $data['date_naissance'],
        'taille_cm' => $data['taille_cm'],
        'poids_kg' => $data['poids_kg'],
        'statut' => $data['statut']
    ];
    
    return executeQuery($sql, $params);
}

/**
 * Supprime un joueur
 */
function deleteJoueur($id) {
    $sql = "DELETE FROM joueur WHERE id = ?";
    return executeQuery($sql, [$id]);
}

/**
 * Récupère les commentaires d'un joueur
 */
function getCommentairesJoueur($joueur_id) {
    $sql = "SELECT * FROM commentaire WHERE joueur_id = ? ORDER BY date_creation DESC";
    $stmt = executeQuery($sql, [$joueur_id]);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Ajoute un commentaire à un joueur
 */
function addCommentaire($joueur_id, $contenu) {
    $sql = "INSERT INTO commentaire (joueur_id, contenu) VALUES (?, ?)";
    return executeQuery($sql, [$joueur_id, $contenu]);
}

/**
 * Récupère les joueurs actifs
 */
function getJoueursActifs() {
    return getAllJoueurs('Actif');
}

/**
 * Compte le nombre de joueurs par statut
 */
function countJoueursByStatut($statut) {
    $sql = "SELECT COUNT(*) as count FROM joueur WHERE statut = ?";
    $stmt = executeQuery($sql, [$statut]);
    if ($stmt) {
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    return 0;
}
?>