<?php
// Controller: formulaire pour composer l'equipe avant un match
// selection des 11 titulaires par poste et des remplacants

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once __DIR__ . "/../modele/commentaire.php";
require_once __DIR__ . "/../modele/match.php";
require_once __DIR__ . "/../modele/joueur.php";
require_once __DIR__ . "/../modele/poste.php";
require_once __DIR__ . "/../modele/participation.php";

/* Vérification ID match */
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php?error=no_match");
    exit();
}

$id_match = intval($_GET["id_match"]);

/* Récupération du match */
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<div class='error-container'><h2>Match introuvable</h2><p>Le match sélectionné n'existe pas.</p></div>");
}

/* Redirection si match déjà passé ou joué */
$nowDt = new DateTimeImmutable('now');
$dateMatchObj = new DateTimeImmutable($match['date_heure']);
if ($dateMatchObj <= $nowDt || $match['etat'] === 'JOUE') {
    header("Location: voir_composition.php?id_match=$id_match");
    exit();
}

/* Récupération des joueurs actifs avec détails */
$joueurs = getActivePlayersDetailed($gestion_sportive);

/* Historiques des commentaires et évaluations pour chaque joueur */
$commentaire_histories = [];
$evaluation_histories = [];
foreach ($joueurs as $j) {
    $commentaire_histories[$j['id_joueur']] = getCommentaireHistory($gestion_sportive, (int)$j['id_joueur'], 3);
    $evaluation_histories[$j['id_joueur']] = getEvaluationHistory($gestion_sportive, (int)$j['id_joueur'], 3);
}

/* Récupération des postes */
$postes = getAllPostesById($gestion_sportive);
$bench_slots = array_values(array_filter($postes, fn($p) => ($p['code'] ?? '') !== 'REM'));

/* Récupération des participations existantes */
$participations_existantes = getParticipationRolesByMatch($gestion_sportive, $id_match);

/* Gestion du brouillon en session */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$draft_error = null;
$draft_titulaires = null;
$draft_remplacants = null;
if (isset($_SESSION['composition_draft'][$id_match])) {
    $draft = $_SESSION['composition_draft'][$id_match];
    $draft_error = $draft['error'] ?? null;
    $draft_titulaires = $draft['titulaires'] ?? [];
    $draft_remplacants = $draft['remplacants'] ?? [];
    unset($_SESSION['composition_draft'][$id_match]);
}

/* Séparation titulaires/remplaçants existants */
$titulaires_existants = [];
$remplacants_existants = [];
foreach ($participations_existantes as $participation) {
    if ($participation['role'] === 'TITULAIRE') {
        $titulaires_existants[] = $participation['id_joueur'];
    } else {
        $remplacants_existants[] = [
            'id_joueur' => $participation['id_joueur'],
            'id_poste' => $participation['id_poste']
        ];
    }
}

/* Calcul des IDs sélectionnés */
$titulaires_selected_ids = array_values(array_unique(array_filter(
    $draft_titulaires !== null ? $draft_titulaires : $titulaires_existants,
    fn($v) => $v !== null && $v !== ''
)));
$remplacants_values = $draft_remplacants !== null
    ? array_values($draft_remplacants)
    : array_map(fn($p) => $p['id_joueur'], $remplacants_existants);
$remplacants_selected_ids = array_values(array_unique(array_filter(
    $remplacants_values,
    fn($v) => $v !== null && $v !== ''
)));

// Inclure la vue
include "../vues/composition_view.php";
