<?php
/**
 * ------------------------------------------------------------
 *  db_commentaire.php — Fonctions liées aux commentaires joueurs
 * ------------------------------------------------------------
 */

/**
 * Récupère tous les commentaires d’un joueur sous forme de texte simple
 * Retourne un tableau de chaînes
 */
function getCommentairesForJoueur(PDO $db, int $id_joueur): array {

    $sql = "
        SELECT texte
        FROM commentaire
        WHERE id_joueur = ?
        ORDER BY date_commentaire DESC
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $rows ?: [];
}


/**
 * Récupère toutes les évaluations d'un joueur (table participation)
 * Retourne un tableau d'entiers
 */
function getEvaluationsForJoueur(PDO $db, int $id_joueur): array {

    $sql = "
        SELECT evaluation
        FROM participation
        WHERE id_joueur = ? AND evaluation IS NOT NULL
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);

    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    return $rows ?: [];
}
