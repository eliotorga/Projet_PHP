<?php
// Controller: affiche la feuille de match complete avec les titulaires et remplacants
// visualisation des evaluations et statistiques du match termine

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once __DIR__ . "/../modele/participation.php";

/* =============================
   VÉRIFICATION ID MATCH
============================= */
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php?error=no_match");
    exit();
}

$id_match = intval($_GET["id_match"]);

/* =============================
   INFOS COMPLÈTES DU MATCH
============================= */
$match = getMatchSummaryWithParticipants($gestion_sportive, $id_match);

if (!$match) {
    die("<div class='error-container'><h2>Match introuvable</h2><p>Le match sélectionné n'existe pas.</p></div>");
}

/* =============================
   RÉCUPÉRATION COMPOSITION COMPLÈTE
============================= */
$participations = getMatchParticipationDetails($gestion_sportive, $id_match);

// Séparation titulaires/remplaçants
$titulaires = array_filter($participations, fn($p) => $p['role'] === 'TITULAIRE');
$remplacants = array_filter($participations, fn($p) => $p['role'] === 'REMPLACANT');

// Classement par poste selon l'ordre footballistique
$groupes = [
    'GAR' => [],
    'DEF' => [],
    'MIL' => [],
    'ATT' => []
];

foreach ($titulaires as $joueur) {
    if (isset($groupes[$joueur['poste_code']])) {
        $groupes[$joueur['poste_code']][] = $joueur;
    }
}

// Calcul de la meilleure note
$best_note = 0;
foreach ($participations as $p) {
    if ($p['evaluation'] > $best_note) {
        $best_note = $p['evaluation'];
    }
}

/* =============================
   STATISTIQUES DU MATCH
============================= */
$statistiques = getMatchEvaluationStats($gestion_sportive, $id_match);

include "../includes/header.php";

// Inclure la vue
include "../vues/voir_composition_view.php";

include "../includes/footer.php";
