<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_participation.php";

$id_match = intval($_POST["id_match"] ?? 0);
$titulaires = $_POST["titulaire"] ?? [];

/* ================= VALIDATION SERVEUR ================= */
$nbJoueurs = 0;
$hasGK = false;

foreach ($titulaires as $poste => $id_joueur) {
    if (!empty($id_joueur)) {
        $nbJoueurs++;
        if (strpos($poste, "GK") !== false) {
            $hasGK = true;
        }
    }
}

if ($nbJoueurs < 11 || !$hasGK) {
    die("Composition invalide : 11 joueurs minimum avec un gardien obligatoire.");
}

/* ================= SAUVEGARDE ================= */
deleteParticipationByMatch($gestion_sportive, $id_match);

foreach ($titulaires as $poste => $id_joueur) {
    if (!empty($id_joueur)) {
        addParticipation(
            $gestion_sportive,
            $id_match,
            $id_joueur,
            null,
            "TITULAIRE",
            null
        );
    }
}

header("Location: ../matchs/liste_matchs.php");
exit;
