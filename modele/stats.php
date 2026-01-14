<?php
// fonctions pour centraliser les requetes SQL des pages statistiques

require_once __DIR__ . "/../includes/config.php";

function getPlayerEvaluationSummary(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            AVG(evaluation) as moyenne,
            COUNT(*) as nb_matchs,
            STDDEV(evaluation) as ecart_type
        FROM participation
        WHERE id_joueur = ? 
          AND evaluation IS NOT NULL
          AND evaluation > 0
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getPlayerRecentForm(PDO $db, int $id_joueur): ?float {
    $stmt = $db->prepare("
        SELECT AVG(p.evaluation) as moyenne_recente
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? 
          AND p.evaluation IS NOT NULL
          AND m.etat = 'JOUE'
        ORDER BY m.date_heure DESC
        LIMIT 5
    ");
    $stmt->execute([$id_joueur]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (float)$val : null;
}

function getPlayerImpactData(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT m.id_match) as total,
            SUM(CASE 
                WHEN m.resultat = 'VICTOIRE' AND p.evaluation >= 4 THEN 1 
                WHEN m.resultat = 'NUL' AND p.evaluation >= 3.5 THEN 1
                WHEN m.resultat = 'DEFAITE' AND p.evaluation <= 2.5 THEN 0
                ELSE 0.5 
            END) as impact_positif
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? 
          AND m.etat = 'JOUE'
          AND p.evaluation IS NOT NULL
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getPlayerBestPostePerformance(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            po.libelle as poste,
            AVG(p.evaluation) as moyenne_poste,
            COUNT(*) as nb_matchs_poste,
            (SELECT AVG(evaluation) FROM participation WHERE id_poste = p.id_poste) as moyenne_generale_poste
        FROM participation p
        JOIN poste po ON po.id_poste = p.id_poste
        WHERE p.id_joueur = ? 
          AND p.evaluation IS NOT NULL
        GROUP BY p.id_poste, po.libelle
        ORDER BY AVG(p.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getPlayerExperienceData(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_matchs,
            DATEDIFF(NOW(), MIN(m.date_heure)) as jours_premier_match,
            COUNT(DISTINCT DATE_FORMAT(m.date_heure, '%Y-%m')) as mois_actifs
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ?
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getTeamMatchStats(PDO $db): array {
    $sql = "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires,
            SUM(CASE WHEN resultat = 'DEFAITE' THEN 1 ELSE 0 END) as defaites,
            SUM(CASE WHEN resultat = 'NUL' THEN 1 ELSE 0 END) as nuls,
            SUM(CASE WHEN etat = 'JOUE' THEN 1 ELSE 0 END) as joues,
            SUM(CASE WHEN etat IN ('A_PREPARER', 'PREPARE') THEN 1 ELSE 0 END) as a_venir
        FROM matchs
    ";
    return $db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getMatchStatusCounts(PDO $db): array {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN etat IN ('A_PREPARER', 'PREPARE') THEN 1 ELSE 0 END) as a_venir,
            SUM(CASE WHEN etat = 'JOUE' THEN 1 ELSE 0 END) as joues,
            COUNT(*) as total
        FROM matchs
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getMatchResultCounts(PDO $db): array {
    $stmt = $db->prepare("
        SELECT 
            SUM(CASE WHEN resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires,
            SUM(CASE WHEN resultat = 'NUL' THEN 1 ELSE 0 END) as nuls,
            SUM(CASE WHEN resultat = 'DEFAITE' THEN 1 ELSE 0 END) as defaites,
            COUNT(*) as total
        FROM matchs
        WHERE resultat IS NOT NULL
    ");
    $stmt->execute();
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getAverageEvaluationValue(PDO $db): ?float {
    $stmt = $db->prepare("
        SELECT AVG(evaluation) as moyenne
        FROM participation
        WHERE evaluation IS NOT NULL
    ");
    $stmt->execute();
    $val = $stmt->fetchColumn();
    return $val !== false ? (float)$val : null;
}

function getRecentMatchActivities(PDO $db, int $limit = 3): array {
    $limit = max(1, (int)$limit);
    $stmt = $db->prepare("
        SELECT 
            m.date_heure,
            m.adversaire,
            m.resultat,
            m.lieu,
            COUNT(p.id_joueur) as nb_participants
        FROM matchs m
        LEFT JOIN participation p ON p.id_match = m.id_match
        WHERE m.etat = 'JOUE'
        GROUP BY m.id_match
        ORDER BY m.date_heure DESC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getNextMatchSummary(PDO $db): ?array {
    $stmt = $db->prepare("
        SELECT 
            m.id_match,
            m.date_heure,
            m.adversaire,
            m.lieu,
            COUNT(p.id_joueur) as nb_joueurs
        FROM matchs m
        LEFT JOIN participation p ON p.id_match = m.id_match
        WHERE m.etat IN ('A_PREPARER', 'PREPARE')
        GROUP BY m.id_match
        ORDER BY m.date_heure ASC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getLastPlayedMatchSummary(PDO $db): ?array {
    $stmt = $db->prepare("
        SELECT 
            m.id_match,
            m.date_heure,
            m.adversaire,
            m.resultat,
            m.score_equipe,
            m.score_adverse,
            ROUND(AVG(p.evaluation), 1) as moyenne_eval
        FROM matchs m
        LEFT JOIN participation p ON p.id_match = m.id_match
        WHERE m.resultat IS NOT NULL
        GROUP BY m.id_match
        ORDER BY m.date_heure DESC
        LIMIT 1
    ");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function getPlayersBasicList(PDO $db): array {
    $stmt = $db->prepare("
        SELECT id_joueur, nom, prenom
        FROM joueur
        ORDER BY nom, prenom
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getAverageEvaluation(PDO $db): ?float {
    $stmt = $db->query("
        SELECT ROUND(AVG(evaluation), 2) as moyenne
        FROM participation
        WHERE evaluation IS NOT NULL
    ");
    $val = $stmt->fetchColumn();
    return $val !== false ? (float)$val : null;
}

function getPlayersByStatut(PDO $db): array {
    $sql = "
        SELECT 
            s.libelle as statut,
            s.code as statut_code,
            COUNT(j.id_joueur) as nb_joueurs
        FROM joueur j
        JOIN statut s ON s.id_statut = j.id_statut
        GROUP BY s.id_statut, s.libelle, s.code
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getTopPerformers(PDO $db, int $limit = 5): array {
    $limit = max(1, (int)$limit);
    $sql = "
        SELECT 
            j.id_joueur,
            j.nom,
            j.prenom,
            ROUND(AVG(p.evaluation), 2) as moyenne,
            COUNT(DISTINCT p.id_match) as nb_matchs,
            s.libelle as statut
        FROM joueur j
        JOIN participation p ON p.id_joueur = j.id_joueur
        JOIN statut s ON s.id_statut = j.id_statut
        WHERE p.evaluation IS NOT NULL
        GROUP BY j.id_joueur, j.nom, j.prenom, s.libelle
        ORDER BY moyenne DESC
        LIMIT $limit
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getPlayersStatsDetailed(PDO $db): array {
    $sql = "
        SELECT 
            j.id_joueur,
            j.nom,
            j.prenom,
            s.libelle AS statut,
            s.code AS statut_code,
            COUNT(DISTINCT p.id_match) AS nb_matchs,
            SUM(CASE WHEN p.role = 'TITULAIRE' THEN 1 ELSE 0 END) AS titularisations,
            SUM(CASE WHEN p.role = 'REMPLACANT' THEN 1 ELSE 0 END) AS remplacements,
            ROUND(AVG(p.evaluation), 2) AS moyenne_notes,
            COUNT(p.evaluation) AS nb_evaluations
        FROM joueur j
        JOIN statut s ON s.id_statut = j.id_statut
        LEFT JOIN participation p ON p.id_joueur = j.id_joueur
        GROUP BY j.id_joueur, j.nom, j.prenom, s.libelle, s.code
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getPlayerPreferredPoste(PDO $db, int $id_joueur): ?string {
    $stmt = $db->prepare("
        SELECT po.libelle
        FROM participation pa
        JOIN poste po ON po.id_poste = pa.id_poste
        WHERE pa.id_joueur = ? AND pa.evaluation IS NOT NULL
        GROUP BY pa.id_poste, po.libelle
        ORDER BY COUNT(*) DESC, AVG(pa.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$id_joueur]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : null;
}

function getPlayerWinStats(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT 
            COUNT(DISTINCT m.id_match) as total,
            SUM(CASE WHEN m.resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur = ? AND m.etat = 'JOUE'
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getPlayedMatchIds(PDO $db): array {
    $stmt = $db->query("
        SELECT id_match
        FROM matchs
        WHERE etat='JOUE'
        ORDER BY date_heure DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

function getPlayerParticipationCountForMatch(PDO $db, int $id_match, int $id_joueur): int {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM participation
        WHERE id_match=? AND id_joueur=?
    ");
    $stmt->execute([$id_match, $id_joueur]);
    return (int)$stmt->fetchColumn();
}

function getPlayedMatchesCount(PDO $db): int {
    $stmt = $db->query("SELECT COUNT(*) FROM matchs WHERE etat='JOUE'");
    return (int)$stmt->fetchColumn();
}

function getResultsCountMap(PDO $db): array {
    $stmt = $db->query("
        SELECT resultat, COUNT(*) nb
        FROM matchs
        WHERE etat='JOUE'
        GROUP BY resultat
    ");
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
}

function getPlayersBasicWithStatus(PDO $db): array {
    $sql = "
        SELECT j.id_joueur, j.nom, j.prenom, s.libelle AS statut
        FROM joueur j
        JOIN statut s ON s.id_statut = j.id_statut
    ";
    return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function getPlayerRoleCounts(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT
            SUM(role='TITULAIRE') AS titu,
            SUM(role='REMPLACANT') AS remp
        FROM participation
        WHERE id_joueur=?
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

function getPlayerAvgEvaluation(PDO $db, int $id_joueur): ?float {
    $stmt = $db->prepare("
        SELECT ROUND(AVG(evaluation),2)
        FROM participation
        WHERE id_joueur=? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$id_joueur]);
    $val = $stmt->fetchColumn();
    return $val !== false ? (float)$val : null;
}

function getPlayerBestPosteByEvaluation(PDO $db, int $id_joueur): ?string {
    $stmt = $db->prepare("
        SELECT po.libelle
        FROM participation pa
        JOIN poste po ON po.id_poste = pa.id_poste
        WHERE pa.id_joueur=? AND pa.evaluation IS NOT NULL
        GROUP BY pa.id_poste
        ORDER BY AVG(pa.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$id_joueur]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : null;
}

function getPlayerWinRateData(PDO $db, int $id_joueur): array {
    $stmt = $db->prepare("
        SELECT COUNT(*) total,
           SUM(m.resultat='VICTOIRE') wins
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur=? AND m.etat='JOUE'
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}
