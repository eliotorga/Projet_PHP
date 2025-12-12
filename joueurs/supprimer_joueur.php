<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* ==========================
   VÉRIFICATION ID
========================== */
if (!isset($_GET["id_joueur"]) || !is_numeric($_GET["id_joueur"])) {
    die("ID joueur manquant ou invalide");
}

$id_joueur = (int) $_GET["id_joueur"];

try {
    $gestion_sportive->beginTransaction();

    /* 1️⃣ Commentaires */
    $stmt = $gestion_sportive->prepare("
        DELETE FROM commentaire
        WHERE id_joueur = ?
    ");
    $stmt->execute([$id_joueur]);

    /* 2️⃣ Participations */
    $stmt = $gestion_sportive->prepare("
        DELETE FROM participation
        WHERE id_joueur = ?
    ");
    $stmt->execute([$id_joueur]);

    /* 3️⃣ Joueur */
    $stmt = $gestion_sportive->prepare("
        DELETE FROM joueur
        WHERE id_joueur = ?
    ");
    $stmt->execute([$id_joueur]);

    $gestion_sportive->commit();

} catch (Exception $e) {
    $gestion_sportive->rollBack();
    die("Erreur lors de la suppression du joueur.");
}

/* ==========================
   REDIRECTION
========================== */
header("Location: liste_joueurs.php");
exit;
