<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";

$id_match = intval($_POST["id_match"]);
$titulaires = $_POST["titulaire"] ?? [];
$remplacants = $_POST["remplacants"] ?? [];
$poste_remp = $_POST["poste_remplacant"] ?? [];

// 1) Effacer ancienne compo
clearParticipation($gestion_sportive, $id_match);

// 2) Ajouter les titulaires
foreach ($titulaires as $id_poste => $id_joueur) {
    if (!empty($id_joueur)) {
        addParticipation($gestion_sportive, $id_match, $id_joueur, "TITULAIRE", $id_poste);
    }
}

// 3) Ajouter les remplaçants
foreach ($remplacants as $id_joueur) {
    $id_poste = $poste_remp[$id_joueur] ?? null;
    addParticipation($gestion_sportive, $id_match, $id_joueur, "REMPLACANT", $id_poste);
}

header("Location: ../matchs/modifier_match.php?id_match=" . $id_match);
exit;
