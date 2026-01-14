<?php
// Controller: permet d'evaluer les performances des joueurs apres un match
// formulaire pour saisir le score final et noter chaque joueur de 1 a 5

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once __DIR__ . "/../modele/match.php";
require_once __DIR__ . "/../modele/participation.php";

/* Vérification ID match */
if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}
$id_match = (int) $_GET["id_match"];

/* Récupération du match avec plus de détails */
$match = getMatchWithParticipationStats($gestion_sportive, $id_match);

if (!$match) {
    die("Match introuvable.");
}

/* Récupération des joueurs ayant participé */
$participants = getMatchParticipantsForEvaluation($gestion_sportive, $id_match);

/* Calcul des stats pour affichage */
$nb_notes = 0;
$somme_notes = 0;
$distribution = [0, 0, 0, 0, 0];

foreach ($participants as $p) {
    if ($p['evaluation']) {
        $nb_notes++;
        $somme_notes += $p['evaluation'];
        $distribution[$p['evaluation'] - 1]++;
    }
}
$moyenne_calc = $nb_notes > 0 ? round($somme_notes / $nb_notes, 2) : 0;

$errors = [];

/* Enregistrement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $score_equipe = !empty($_POST["score_equipe"]) ? (int)$_POST["score_equipe"] : null;
    $score_adverse = !empty($_POST["score_adverse"]) ? (int)$_POST["score_adverse"] : null;
    $evaluations = $_POST["evaluation"] ?? [];

    /* Validation */
    if ($score_equipe === null || $score_adverse === null) {
        $errors[] = "Les scores du match sont requis.";
    }

    if ($score_equipe !== null && $score_adverse !== null) {
        if ($score_equipe < 0 || $score_adverse < 0) {
            $errors[] = "Les scores ne peuvent pas être négatifs.";
        }
    }

    $resultat = null;
    if ($score_equipe !== null && $score_adverse !== null) {
        if ($score_equipe > $score_adverse) {
            $resultat = "VICTOIRE";
        } elseif ($score_equipe < $score_adverse) {
            $resultat = "DEFAITE";
        } else {
            $resultat = "NUL";
        }
    }

    /* Validation des notes */
    $notes_valides = true;
    foreach ($evaluations as $id_joueur => $note) {
        if ($note !== "" && ($note < 1 || $note > 5)) {
            $notes_valides = false;
            break;
        }
    }

    if (!$notes_valides) {
        $errors[] = "Les notes doivent être comprises entre 1 et 5.";
    }

    if (empty($errors)) {
        try {
            $gestion_sportive->beginTransaction();

            /* Mise à jour du match */
            setMatchResult($gestion_sportive, $id_match, $score_equipe, $score_adverse);

            /* Mise à jour des évaluations */
            foreach ($evaluations as $id_joueur => $note) {
                $note_value = $note !== "" ? (int)$note : null;
                updateEvaluation($gestion_sportive, $id_match, (int)$id_joueur, $note_value);
            }

            $gestion_sportive->commit();

            $_SESSION['success_message'] = "Évaluations enregistrées avec succès !";
            header("Location: ../matchs/liste_matchs.php");
            exit;

        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
        }
    }
}

include "../includes/header.php";

// Inclure la vue
include "../vues/evaluation_view.php";

include "../includes/footer.php";
