<?php
// fonctions pour gerer les joueurs
// permet d'ajouter, modifier, supprimer et recuperer les joueurs
// calcule aussi les stats comme les participations et moyennes

require_once __DIR__ . "/../includes/config.php";

/****************************************
 * 1) RÉCUPÉRATIONS DE BASE
 ****************************************/

// Récupérer tous les joueurs
function getAllPlayers(PDO $db) {
    $sql = "SELECT j.*, s.libelle AS statut_libelle
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            ORDER BY j.nom, j.prenom";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}

// Récupérer un joueur par son ID
function getPlayerById(PDO $db, int $id) {
    $sql = "SELECT * FROM joueur WHERE id_joueur = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch(); 
}

// Récupérer tous les statuts possibles
function getAllStatuts(PDO $db) {
    $stmt = $db->query("SELECT * FROM statut ORDER BY libelle");
    return $stmt->fetchAll();
}

// Récupérer uniquement les joueurs actifs
function getActivePlayers(PDO $db) {
    $sql = "SELECT j.*, s.libelle AS statut_libelle
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            WHERE s.code = 'ACTIF'
            ORDER BY j.nom, j.prenom";
    $stmt = $db->query($sql);
    return $stmt->fetchAll();
}


/****************************************
 * 2) AJOUT / MODIFICATION / SUPPRESSION
 ****************************************/

// Ajouter un joueur
function insertPlayer(PDO $db, array $data) {
    $sql = "INSERT INTO joueur (nom, prenom, num_licence, date_naissance, taille_cm, poids_kg, id_statut)
            VALUES (:nom, :prenom, :licence, :ddn, :taille, :poids, :statut)";
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":nom"     => $data["nom"],
        ":prenom"  => $data["prenom"],
        ":licence" => $data["num_licence"],
        ":ddn"     => $data["date_naissance"],
        ":taille"  => $data["taille_cm"],
        ":poids"   => $data["poids_kg"],
        ":statut"  => $data["id_statut"]
    ]);
}

// Modifier un joueur
function updatePlayer(PDO $db, int $id, array $data) {
    $sql = "UPDATE joueur 
            SET nom = :nom,
                prenom = :prenom,
                num_licence = :licence,
                date_naissance = :ddn,
                taille_cm = :taille,
                poids_kg = :poids,
                id_statut = :statut
            WHERE id_joueur = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":nom"     => $data["nom"],
        ":prenom"  => $data["prenom"],
        ":licence" => $data["num_licence"],
        ":ddn"     => $data["date_naissance"],
        ":taille"  => $data["taille_cm"],
        ":poids"   => $data["poids_kg"],
        ":statut"  => $data["id_statut"],
        ":id"      => $id
    ]);
}

// Supprimer un joueur
function deletePlayer(PDO $db, int $id) {
    $sql = "DELETE FROM joueur WHERE id_joueur = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
}



/****************************************
 * 3) COMMENTAIRES SUR JOUEURS
 ****************************************/

// Ajouter un commentaire sur un joueur
function addComment(PDO $db, int $id_joueur, string $texte) {
    $sql = "INSERT INTO commentaire (id_joueur, texte, date_commentaire)
            VALUES (?, ?, NOW())";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur, $texte]);
}

// Récupérer l'historique des commentaires pour un joueur
function getComments(PDO $db, int $id_joueur) {
    $sql = "SELECT * FROM commentaire 
            WHERE id_joueur = ?
            ORDER BY date_commentaire DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll();
}



/****************************************
 * 4) STATS LÉGÈRES (UTILISÉ POUR PRÉ-VISUALISATION)
 ****************************************/

// Récupérer le nombre de sélections (participation)
function getNbParticipations(PDO $db, int $id_joueur) {
    $sql = "SELECT COUNT(*) AS total
            FROM participation
            WHERE id_joueur = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch()["total"];
}

// Moyenne des évaluations pour un joueur
function getAvgEvaluation(PDO $db, int $id_joueur) {
    $sql = "SELECT AVG(evaluation) AS moyenne
            FROM participation
            WHERE id_joueur = ? AND evaluation IS NOT NULL";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch()["moyenne"];
}
// Récupère infos avancées pour la feuille de match
function getPlayerExtraInfo(PDO $db, int $id_joueur) {
    
    // Moyenne des évaluations
    $stmt = $db->prepare("
        SELECT AVG(evaluation) AS moyenne
        FROM participation
        WHERE id_joueur = ? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$id_joueur]);
    $moyenne = $stmt->fetch()["moyenne"];

    // 5 dernières évaluations
    $stmt = $db->prepare("
        SELECT p.evaluation, m.date_heure
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? AND p.evaluation IS NOT NULL
        ORDER BY m.date_heure DESC
        LIMIT 5
    ");
    $stmt->execute([$id_joueur]);
    $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Commentaires (si table existe)
    $stmt = $db->prepare("
        SELECT commentaire, date_commentaire
        FROM joueur_commentaire
        WHERE id_joueur = ?
        ORDER BY date_commentaire DESC
        LIMIT 3
    ");
    $stmt->execute([$id_joueur]);
    $commentaires = $stmt->fetchAll();

    return [
        "moyenne" => $moyenne ? round($moyenne, 2) : null,
        "evaluations" => $evaluations,
        "commentaires" => $commentaires
    ];
}


?>
