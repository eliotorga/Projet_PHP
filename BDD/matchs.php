<?php
/**
 * Fonctions pour la gestion des matchs
 */

/**
 * Récupère tous les matchs
 */
function getAllMatchs($est_termine = null) {
    $sql = "SELECT * FROM `match`";
    $params = [];
    
    if ($est_termine !== null) {
        $sql .= " WHERE est_termine = ?";
        $params[] = $est_termine;
    }
    
    $sql .= " ORDER BY date_heure DESC";
    
    $stmt = executeQuery($sql, $params);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Récupère un match par son ID
 */
function getMatchById($id) {
    $sql = "SELECT * FROM `match` WHERE id = ?";
    $stmt = executeQuery($sql, [$id]);
    if ($stmt) {
        return $stmt->fetch();
    }
    return false;
}

/**
 * Récupère les matchs à venir
 */
function getMatchsAVenir() {
    $sql = "SELECT * FROM `match` WHERE est_termine = FALSE AND date_heure > NOW() ORDER BY date_heure ASC";
    $stmt = executeQuery($sql);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Récupère les matchs terminés
 */
function getMatchsTermines() {
    $sql = "SELECT * FROM `match` WHERE est_termine = TRUE ORDER BY date_heure DESC";
    $stmt = executeQuery($sql);
    if ($stmt) {
        return $stmt->fetchAll();
    }
    return [];
}

/**
 * Ajoute un nouveau match
 */
function addMatch($data) {
    $sql = "INSERT INTO `match` (date_heure, nom_adversaire, lieu, est_termine) 
            VALUES (:date_heure, :nom_adversaire, :lieu, FALSE)";
    
    return executeQuery($sql, [
        'date_heure' => $data['date_heure'],
        'nom_adversaire' => $data['nom_adversaire'],
        'lieu' => $data['lieu']
    ]);
}

/**
 * Met à jour un match
 */
function updateMatch($id, $data) {
    $sql = "UPDATE `match` SET 
            date_heure = :date_heure,
            nom_adversaire = :nom_adversaire,
            lieu = :lieu
            WHERE id = :id";
    
    return executeQuery($sql, [
        'id' => $id,
        'date_heure' => $data['date_heure'],
        'nom_adversaire' => $data['nom_adversaire'],
        'lieu' => $data['lieu']
    ]);
}

/**
 * Enregistre le résultat d'un match
 */
function enregistrerResultatMatch($id, $score_equipe, $score_adversaire) {
    $sql = "UPDATE `match` SET 
            score_equipe = :score_equipe,
            score_adversaire = :score_adversaire,
            est_termine = TRUE
            WHERE id = :id";
    
    return executeQuery($sql, [
        'id' => $id,
        'score_equipe' => $score_equipe,
        'score_adversaire' => $score_adversaire
    ]);
}

/**
 * Supprime un match
 */
function deleteMatch($id) {
    $sql = "DELETE FROM `match` WHERE id = ?";
    return executeQuery($sql, [$id]);
}

/**
 * Récupère les statistiques des matchs
 */
function getStatistiquesMatchs() {
    $stats = [
        'total' => 0,
        'victoires' => 0,
        'defaites' => 0,
        'nuls' => 0
    ];
    
    $sql = "SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN score_equipe > score_adversaire THEN 1 ELSE 0 END) as victoires,
            SUM(CASE WHEN score_equipe < score_adversaire THEN 1 ELSE 0 END) as defaites,
            SUM(CASE WHEN score_equipe = score_adversaire THEN 1 ELSE 0 END) as nuls
            FROM `match` 
            WHERE est_termine = TRUE";
    
    $stmt = executeQuery($sql);
    if ($stmt) {
        $result = $stmt->fetch();
        if ($result) {
            $stats['total'] = $result['total'] ?? 0;
            $stats['victoires'] = $result['victoires'] ?? 0;
            $stats['defaites'] = $result['defaites'] ?? 0;
            $stats['nuls'] = $result['nuls'] ?? 0;
            
            // Calcul des pourcentages
            if ($stats['total'] > 0) {
                $stats['pourcentage_victoires'] = round(($stats['victoires'] / $stats['total']) * 100, 1);
                $stats['pourcentage_defaites'] = round(($stats['defaites'] / $stats['total']) * 100, 1);
                $stats['pourcentage_nuls'] = round(($stats['nuls'] / $stats['total']) * 100, 1);
            }
        }
    }
    
    return $stats;
}

/**
 * Récupère le dernier match joué
 */
function getDernierMatch() {
    $sql = "SELECT * FROM `match` WHERE est_termine = TRUE ORDER BY date_heure DESC LIMIT 1";
    $stmt = executeQuery($sql);
    if ($stmt) {
        return $stmt->fetch();
    }
    return false;
}

/**
 * Compte le nombre de matchs à venir
 */
function countMatchsAVenir() {
    $sql = "SELECT COUNT(*) as count FROM `match` WHERE est_termine = FALSE AND date_heure > NOW()";
    $stmt = executeQuery($sql);
    if ($stmt) {
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    return 0;
}
?>