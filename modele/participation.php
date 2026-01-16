<?php
// fonctions pour gerer les participations aux matchs
// permet de sauvegarder qui a joue a quel poste (titulaire ou remplacant)
// calcule les stats de participations, evaluations et series


/**************************************************************
 * 1️ Récupérer la participation d’un match
 **************************************************************/
function getParticipationByMatch(PDO $db, int $id_match) {

    $sql = "
        SELECT 
            pa.*,
            j.nom, j.prenom, j.taille_cm, j.poids_kg,
            p.libelle AS poste_libelle
        FROM participation pa
        JOIN joueur j ON j.id_joueur = pa.id_joueur
        LEFT JOIN poste p ON p.id_poste = pa.id_poste
        WHERE pa.id_match = ?
        ORDER BY pa.role DESC, p.libelle ASC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_match]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Récupérer les participations d'un match (id_joueur, id_poste, role)
function getParticipationRolesByMatch(PDO $db, int $id_match): array {
    $stmt = $db->prepare("
        SELECT id_joueur, id_poste, role
        FROM participation
        WHERE id_match = ?
    ");
    $stmt->execute([$id_match]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Résumé d'un match avec participation (pour la feuille de match)
function getMatchSummaryWithParticipants(PDO $db, int $id_match): ?array {
    $stmt = $db->prepare("
        SELECT 
            m.id_match,
            m.date_heure,
            m.adversaire,
            m.lieu,
            m.resultat,
            m.score_equipe,
            m.score_adverse,
            m.etat,
            COUNT(DISTINCT p.id_joueur) as nb_joueurs,
            ROUND(AVG(p.evaluation), 1) as moyenne_eval
        FROM matchs m
        LEFT JOIN participation p ON p.id_match = m.id_match
        WHERE m.id_match = ?
        GROUP BY m.id_match
    ");
    $stmt->execute([$id_match]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// Détails des participations d'un match (titulaires/remplaçants)
function getMatchParticipationDetails(PDO $db, int $id_match): array {
    $stmt = $db->prepare("
        SELECT 
            j.id_joueur,
            j.nom,
            j.prenom,
            j.num_licence,
            p.evaluation,
            p.role,
            po.code AS poste_code,
            po.libelle AS poste_libelle,
            s.libelle AS statut
        FROM participation p
        JOIN joueur j ON j.id_joueur = p.id_joueur
        JOIN poste po ON po.id_poste = p.id_poste
        JOIN statut s ON s.id_statut = j.id_statut
        WHERE p.id_match = ?
        ORDER BY 
            FIELD(p.role, 'TITULAIRE', 'REMPLACANT') DESC,
            FIELD(po.code, 'GAR', 'DEF', 'MIL', 'ATT'),
            j.nom
    ");
    $stmt->execute([$id_match]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Statistiques d'évaluations d'un match
function getMatchEvaluationStats(PDO $db, int $id_match): array {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_joueurs,
            ROUND(AVG(evaluation), 1) as moyenne_generale,
            MIN(evaluation) as note_min,
            MAX(evaluation) as note_max,
            SUM(CASE WHEN evaluation >= 4 THEN 1 ELSE 0 END) as excellent,
            SUM(CASE WHEN evaluation = 3 THEN 1 ELSE 0 END) as moyen,
            SUM(CASE WHEN evaluation <= 2 THEN 1 ELSE 0 END) as faible
        FROM participation
        WHERE id_match = ? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$id_match]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Compter les participations d'un match
function getParticipationCountForMatch(PDO $db, int $id_match): int {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM participation WHERE id_match = ?
    ");
    $stmt->execute([$id_match]);
    return (int)$stmt->fetchColumn();
}

// Statistiques synthétiques d'un joueur sur les matchs joués
function getPlayerMatchStats(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_matchs,
            SUM(CASE WHEN p.role = 'TITULAIRE' THEN 1 ELSE 0 END) as matchs_titulaire,
            SUM(CASE WHEN p.role = 'REMPLACANT' THEN 1 ELSE 0 END) as matchs_remplacant,
            AVG(p.evaluation) as moyenne_evaluation,
            COUNT(p.evaluation) as matchs_evalues,
            MIN(p.evaluation) as min_evaluation,
            MAX(p.evaluation) as max_evaluation
        FROM participation p
        INNER JOIN matchs m ON p.id_match = m.id_match
        WHERE p.id_joueur = ? AND m.etat = 'JOUE'
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

// Historique des matchs pour un joueur
function getPlayerMatchHistory(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            m.*,
            p.id_poste,
            po.libelle as poste_libelle,
            p.role,
            p.evaluation,
            CASE 
                WHEN m.resultat = 'VICTOIRE' THEN 'victoire'
                WHEN m.resultat = 'DEFAITE' THEN 'defaite'
                WHEN m.resultat = 'NUL' THEN 'nul'
                ELSE 'indetermine'
            END as resultat_class
        FROM participation p
        INNER JOIN matchs m ON p.id_match = m.id_match
        LEFT JOIN poste po ON p.id_poste = po.id_poste
        WHERE p.id_joueur = ?
        ORDER BY m.date_heure DESC
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Répartition des postes pour un joueur
function getPlayerPosteDistribution(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            po.libelle as poste,
            COUNT(*) as nb_matchs,
            ROUND(AVG(p.evaluation), 2) as moyenne_eval
        FROM participation p
        LEFT JOIN poste po ON p.id_poste = po.id_poste
        WHERE p.id_joueur = ?
        GROUP BY p.id_poste, po.libelle
        ORDER BY nb_matchs DESC
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Participants d'un match pour l'évaluation
function getMatchParticipantsForEvaluation(PDO $db, int $id_match): array {
    $stmt = $db->prepare("
        SELECT 
            p.id_joueur,
            p.role,
            p.evaluation,
            j.nom,
            j.prenom,
            j.num_licence,
            po.libelle AS poste,
            s.code as statut_code,
            s.libelle as statut_libelle
        FROM participation p
        JOIN joueur j ON j.id_joueur = p.id_joueur
        LEFT JOIN poste po ON po.id_poste = p.id_poste
        LEFT JOIN statut s ON j.id_statut = s.id_statut
        WHERE p.id_match = ?
        ORDER BY 
            CASE p.role 
                WHEN 'TITULAIRE' THEN 1 
                WHEN 'REMPLACANT' THEN 2 
                ELSE 3 
            END,
            po.libelle
    ");
    $stmt->execute([$id_match]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


/**************************************************************
 * 2️ Ajouter une participation
 **************************************************************/
function addParticipation(PDO $db, array $data) {

    $sql = "
        INSERT INTO participation (id_match, id_joueur, id_poste, role, evaluation)
        VALUES (:id_match, :id_joueur, :id_poste, :role, :evaluation)
    ";

    $stmt = $db->prepare($sql);

    return $stmt->execute([
        ":id_match"   => $data["id_match"],
        ":id_joueur"  => $data["id_joueur"],
        ":id_poste"   => $data["id_poste"],
        ":role"       => $data["role"],   // 'TITULAIRE' ou 'REMPLACANT'
        ":evaluation" => $data["evaluation"]
    ]);
}


/**************************************************************
 * 3️ Effacer toute la compo d’un match
 **************************************************************/
function clearParticipation(PDO $db, int $id_match) {
    $stmt = $db->prepare("DELETE FROM participation WHERE id_match = ?");
    return $stmt->execute([$id_match]);
}


/**************************************************************
 * 4️ Mettre à jour une évaluation
 **************************************************************/
function updateEvaluation(PDO $db, int $id_match, int $id_joueur, ?int $note) {

    $sql = "
        UPDATE participation
        SET evaluation = :note
        WHERE id_match = :id_match AND id_joueur = :id_joueur
    ";

    $stmt = $db->prepare($sql);

    return $stmt->execute([
        ":note"      => $note,
        ":id_match"  => $id_match,
        ":id_joueur" => $id_joueur
    ]);
}


/**************************************************************
 * 5️ Nombre de titularisations
 **************************************************************/
function getNbTitularisations(PDO $db, int $id_joueur) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM participation
        WHERE id_joueur = ? AND role = 'TITULAIRE'
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchColumn();
}


/**************************************************************
 * 6️ Nombre de remplacements
 **************************************************************/
function getNbRemplacements(PDO $db, int $id_joueur) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM participation
        WHERE id_joueur = ? AND role = 'REMPLACANT'
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchColumn();
}


/**************************************************************
 * 7️ Moyenne des évaluations
 **************************************************************/
function getAvgNote(PDO $db, int $id_joueur) {
    $stmt = $db->prepare("
        SELECT AVG(evaluation)
        FROM participation
        WHERE id_joueur = ? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchColumn();
}


/**************************************************************
 * 8️ Poste préféré (meilleure moyenne en TITU)
 **************************************************************/
function getBestPoste(PDO $db, int $id_joueur) {

    $sql = "
        SELECT p.libelle, AVG(pa.evaluation) AS moyenne
        FROM participation pa
        JOIN poste p ON p.id_poste = pa.id_poste
        WHERE pa.id_joueur = ?
          AND pa.role = 'TITULAIRE'
          AND pa.evaluation IS NOT NULL
        GROUP BY p.id_poste
        ORDER BY moyenne DESC
        LIMIT 1
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}


/**************************************************************
 * 9️ Série de matchs consécutifs
 **************************************************************/
function getSerieConsecutive(PDO $db, int $id_joueur) {

    $sql = "
        SELECT m.resultat
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur = ?
        ORDER BY m.date_heure DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $serie = 0;

    foreach ($rows as $r) {
        if ($r["resultat"] === null) break;
        $serie++;
    }

    return $serie;
}



 //Pourcentage de victoires sur matchs joués
 
function getWinRate(PDO $db, int $id_joueur) {

    $sql = "
        SELECT
            SUM(CASE WHEN m.resultat = 'VICTOIRE' THEN 1 ELSE 0 END) AS wins,
            COUNT(*) AS total
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur = ?
          AND m.resultat IS NOT NULL
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);

    $res = $stmt->fetch(PDO::FETCH_ASSOC);

    return ($res["total"] > 0) ? ($res["wins"] / $res["total"]) * 100 : 0;
}

?>
