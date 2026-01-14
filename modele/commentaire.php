<?php
// fonctions pour gerer les commentaires et evaluations des joueurs
// recupere les commentaires et notes donnes aux joueurs

// recupere tous les commentaires d'un joueur sous forme de texte simple
// retourne un tableau de chaines
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

// recupere un historique des commentaires avec date
function getCommentaireHistory(PDO $db, int $id_joueur, int $limit = 3): array {
    $limit = max(1, (int)$limit);
    $sql = "
        SELECT texte, date_commentaire
        FROM commentaire
        WHERE id_joueur = ?
        ORDER BY date_commentaire DESC
        LIMIT $limit
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}


// recupere toutes les evaluations d'un joueur (table participation)
// retourne un tableau d'entiers
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

// recupere un historique des evaluations avec date et adversaire
function getEvaluationHistory(PDO $db, int $id_joueur, int $limit = 3): array {
    $limit = max(1, (int)$limit);
    $sql = "
        SELECT p.evaluation, m.date_heure, m.adversaire
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? AND p.evaluation IS NOT NULL
        ORDER BY m.date_heure DESC
        LIMIT $limit
    ";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}
