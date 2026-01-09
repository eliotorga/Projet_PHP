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
