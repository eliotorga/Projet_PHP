<?php
// Controller: permet de modifier les informations d'un match existant
// affiche un formulaire avec les donnees actuelles et les statistiques du match

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../modele/match.php";

// Vérification ID match
if (!isset($_GET["id_match"])) {
    $_SESSION['error_message'] = "ID match manquant.";
    header("Location: liste_matchs.php");
    exit;
}

$id_match = intval($_GET["id_match"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    $_SESSION['error_message'] = "Match introuvable.";
    header("Location: liste_matchs.php");
    exit;
}

// Récupérer les statistiques du match
$stats_match = getMatchStatsSummary($gestion_sportive, $id_match);

// Récupérer les adversaires existants pour l'autocomplete
$adversaires_existants = getDistinctAdversaires($gestion_sportive);

// Calculer le résultat initial
$resultat_initial = $match["resultat"] ?? '';
if (!$resultat_initial && $match["etat"] === "JOUE" && $match["score_equipe"] !== null && $match["score_adverse"] !== null) {
    if ((int)$match["score_equipe"] > (int)$match["score_adverse"]) $resultat_initial = "VICTOIRE";
    elseif ((int)$match["score_equipe"] < (int)$match["score_adverse"]) $resultat_initial = "DEFAITE";
    else $resultat_initial = "NUL";
}

include "../includes/header.php";

// Inclure la vue
include "../vues/modifier_match_view.php";

include "../includes/footer.php";
