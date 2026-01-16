<?php
// Controller: affiche la liste complete de tous les matchs avec filtres
// permet de naviguer vers les actions composer, evaluer, modifier et supprimer

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../modele/match.php";


   //RÉCUPÉRATION DES MATCHS AVEC STATISTIQUES ET FILTRES


// Récupération des filtres
$filterEtat = $_GET['etat'] ?? 'all';
$filterResultat = $_GET['resultat'] ?? 'all';
$filterDate = $_GET['date'] ?? 'all';

$matchs = getMatchesWithStats($gestion_sportive, [
    'etat' => $filterEtat,
    'resultat' => $filterResultat,
    'date' => $filterDate
]);

// Calcul des statistiques
$total_matchs = count($matchs);
$matchs_joues = array_filter($matchs, fn($m) => $m['etat'] === 'JOUE');
$victoires = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'VICTOIRE');
$nuls = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'NUL');
$defaites = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'DEFAITE');
$matchs_a_preparer = array_filter($matchs, fn($m) => $m['etat'] === 'A_PREPARER');
$moyenne_eval = round(array_reduce($matchs_joues, function($carry, $m) {
    return $carry + ($m['moyenne_eval'] ?: 0);
}, 0) / max(count($matchs_joues), 1), 1);

$nowDt = new DateTimeImmutable('now');

include "../includes/header.php";

// Inclure la vue
include "../vues/liste_matchs_view.php";

include "../includes/footer.php";
