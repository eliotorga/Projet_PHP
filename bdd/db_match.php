<?php
// fonctions pour gerer les matchs
// permet d'ajouter, modifier, supprimer les matchs
// gere aussi les resultats et calcule les stats d'equipe

require_once __DIR__ . "/../includes/config.php";

/****************************************
 * 1) RÉCUPÉRATION DES MATCHS
 ****************************************/

// Tous les matchs
function getAllMatches(PDO $db) {
    $sql = "SELECT * FROM matchs ORDER BY date_heure DESC";
    return $db->query($sql)->fetchAll();
}

// Match par ID
function getMatchById(PDO $db, int $id) {
    $stmt = $db->prepare("SELECT * FROM matchs WHERE id_match = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// Prochains matchs (non joués)
function getUpcomingMatches(PDO $db) {
    $sql = "SELECT * FROM matchs
            WHERE etat != 'JOUE'
            ORDER BY date_heure ASC";
    return $db->query($sql)->fetchAll();
}

// Matchs à préparer
function getMatchesToPrepare(PDO $db) {
    $sql = "SELECT * FROM matchs
            WHERE etat = 'A_PREPARER'
            ORDER BY date_heure ASC";
    return $db->query($sql)->fetchAll();
}

// Matchs déjà préparés mais pas joués
function getPreparedMatches(PDO $db) {
    $sql = "SELECT * FROM matchs
            WHERE etat = 'PREPARE'
            ORDER BY date_heure ASC";
    return $db->query($sql)->fetchAll();
}

// Matchs joués
function getPlayedMatches(PDO $db) {
    $sql = "SELECT * FROM matchs
            WHERE etat = 'JOUE'
            ORDER BY date_heure DESC";
    return $db->query($sql)->fetchAll();
}



/****************************************
 * 2) AJOUT / MODIFICATION / SUPPRESSION
 ****************************************/

// Ajouter un match
function insertMatch(PDO $db, array $data) {
    $sql = "INSERT INTO matchs (date_heure, adversaire, lieu, adresse, score_equipe, score_adverse, resultat, etat)
            VALUES (:dh, :adv, :lieu, :adresse, NULL, NULL, NULL, 'A_PREPARER')";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":dh"      => $data["date_heure"],
        ":adv"     => $data["adversaire"],
        ":lieu"    => $data["lieu"],
        ":adresse"  => $data["adresse"] ?? null
    ]);
}

// Modifier un match (hors résultat)
function updateMatch(PDO $db, int $id, array $data) {
    $sql = "UPDATE matchs
            SET date_heure = :dh,
                adversaire = :adv,
                lieu = :lieu,
                score_equipe = :score_equipe,
                score_adverse = :score_adverse,
                resultat = :resultat,
                etat = :etat
            WHERE id_match = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":dh"   => $data["date_heure"],
        ":adv"  => $data["adversaire"],
        ":lieu" => $data["lieu"],
        ":score_equipe" => $data["score_equipe"],
        ":score_adverse" => $data["score_adverse"],
        ":resultat" => $data["resultat"],
        ":etat" => $data["etat"],
        ":id"   => $id
    ]);
}

// Supprimer un match
function deleteMatch(PDO $db, int $id) {
    $stmt = $db->prepare("DELETE FROM matchs WHERE id_match = ?");
    $stmt->execute([$id]);
}



/****************************************
 * 3) MISE À JOUR DU RÉSULTAT
 ****************************************/

function setMatchResult(PDO $db, int $id_match, int $score_equipe, int $score_adverse) {

    // Détermination du résultat
    if ($score_equipe > $score_adverse) {
        $resultat = "VICTOIRE";
    } elseif ($score_equipe < $score_adverse) {
        $resultat = "DEFAITE";
    } else {
        $resultat = "NUL";
    }

    $sql = "UPDATE matchs
            SET score_equipe = :se,
                score_adverse = :sa,
                resultat = :res,
                etat = 'JOUE'
            WHERE id_match = :id";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ":se"  => $score_equipe,
        ":sa"  => $score_adverse,
        ":res" => $resultat,
        ":id"  => $id_match
    ]);
}



/****************************************
 * 4) STATISTIQUES ÉQUIPE
 ****************************************/

function getMatchStats(PDO $db) {
    $sql = "
        SELECT 
            SUM(resultat = 'VICTOIRE') AS victoires,
            SUM(resultat = 'DEFAITE') AS defaites,
            SUM(resultat = 'NUL') AS nuls,
            COUNT(*) AS total
        FROM matchs
        WHERE resultat IS NOT NULL
    ";
    return $db->query($sql)->fetch();
}

?>
