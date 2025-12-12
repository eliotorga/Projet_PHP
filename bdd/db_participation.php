<?php
/***************************************************
 * db_participation.php
 * Fonctions SQL pour la feuille de match,
 * la participation des joueurs et les évaluations
 ***************************************************/

require_once __DIR__ . "/../includes/config.php";





/****************************************
 * 2) PARTICIPATION DES JOUEURS À UN MATCH
 ****************************************/

// Récupérer tous les joueurs sélectionnés pour un match
function getParticipationByMatch(PDO $db, int $id_match) {
    $sql = "SELECT p.*, 
                   j.nom, j.prenom,
                   t.libelle AS poste_libelle
            FROM participation p
            JOIN joueur j ON j.id_joueur = p.id_joueur
            LEFT JOIN poste t ON t.id_poste = p.id_poste
            WHERE p.id_match = ?
            ORDER BY p.role DESC, t.libelle ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_match]);
    return $stmt->fetchAll();
}

// Vérifie si un joueur est déjà sélectionné pour un match
function isPlayerInMatch(PDO $db, int $id_match, int $id_joueur) {
    $sql = "SELECT COUNT(*) AS total
            FROM participation
            WHERE id_match = ? AND id_joueur = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_match, $id_joueur]);
    return $stmt->fetch()["total"] > 0;
}



/****************************************
 * 3) AJOUT DE PARTICIPANTS AU MATCH
 ****************************************/

// Ajouter un joueur comme TITULAIRE ou REMPLACANT
function addParticipation(PDO $db, int $id_match, int $id_joueur, $role, $id_poste = null) {
    $sql = "INSERT INTO participation (id_match, id_joueur, id_poste, role)
            VALUES (:id_match, :id_joueur, :poste, :role)";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":id_match"  => $id_match,
        ":id_joueur" => $id_joueur,
        ":poste"     => $id_poste,
        ":role"      => $role
    ]);
}



// Supprime toutes les participations d'un match (utile pour recharger une feuille)
function clearParticipation(PDO $db, int $id_match) {
    $stmt = $db->prepare("DELETE FROM participation WHERE id_match = ?");
    $stmt->execute([$id_match]);
}



/****************************************
 * 4) ÉVALUATION DES JOUEURS APRÈS MATCH
 ****************************************/

function updateEvaluation(PDO $db, int $id_match, int $id_joueur, int $note) {
    $sql = "UPDATE participation
            SET evaluation = :note
            WHERE id_match = :match AND id_joueur = :joueur";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":note"   => $note,
        ":match"  => $id_match,
        ":joueur" => $id_joueur
    ]);
}



/****************************************
 * 5) STATISTIQUES JOUEUR (utiles pour stats)
 ****************************************/

// Nombre de titularisations
function getNbTitularisations(PDO $db, int $id_joueur) {
    $sql = "SELECT COUNT(*) AS total
            FROM participation
            WHERE id_joueur = ? AND role = 'TITULAIRE'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch()["total"];
}

// Nombre de remplacements
function getNbRemplacements(PDO $db, int $id_joueur) {
    $sql = "SELECT COUNT(*) AS total
            FROM participation
            WHERE id_joueur = ? AND role = 'REMPLACANT'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch()["total"];
}

// Moyenne des évaluations
function getAvgNote(PDO $db, int $id_joueur) {
    $sql = "SELECT AVG(evaluation) AS moyenne
            FROM participation
            WHERE id_joueur = ? AND evaluation IS NOT NULL";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch()["moyenne"];
}

// Nombre de matchs consécutifs joués
function getNbMatchsConsecutifs(PDO $db, int $id_joueur) {
    // On récupère uniquement les matchs joués, triés chronologiquement
    $sql = "SELECT m.id_match, m.etat
            FROM participation p
            JOIN matchs m ON m.id_match = p.id_match
            WHERE p.id_joueur = ? AND m.etat = 'JOUE'
            ORDER BY m.date_heure DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    $matches = $stmt->fetchAll();

    return count($matches); // Simple : tous les matchs joués consécutivement
}

// Pourcentage de victoires quand le joueur participe
function getWinRate(PDO $db, int $id_joueur) {
    $sql = "SELECT 
                SUM(m.resultat = 'VICTOIRE') AS wins,
                COUNT(*) AS total
            FROM participation p
            JOIN matchs m ON m.id_match = p.id_match
            WHERE p.id_joueur = ? AND m.etat = 'JOUE'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    $res = $stmt->fetch();

    if ($res["total"] == 0) return null;
    return ($res["wins"] / $res["total"]) * 100;
}

?>
