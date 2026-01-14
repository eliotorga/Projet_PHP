<?php
// Controller: permet de supprimer un ou plusieurs matchs de la base de donnees
// affiche la liste des matchs avec stats et gere la confirmation avant suppression

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/match.php";
require_once __DIR__ . "/../modele/participation.php";

// Suppression directe via GET
if (isset($_GET['id'])) {
    $id_match = intval($_GET['id']);

    try {
        $gestion_sportive->beginTransaction();
        clearParticipation($gestion_sportive, $id_match);
        deleteMatch($gestion_sportive, $id_match);
        $gestion_sportive->commit();

        $_SESSION['success_message'] = "Match supprimé avec succès.";
        header("Location: liste_matchs.php");
        exit;

    } catch (Exception $e) {
        $gestion_sportive->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Initialiser les messages
$message = "";
$error = $error ?? "";

// Récupérer tous les matchs avec leurs statistiques
$matchs = getMatchesWithDeleteStats($gestion_sportive);

$select_all = isset($_GET['select_all']) ? intval($_GET['select_all']) : 0;
$show_confirmation = false;
$matches_to_confirm = [];

// Traitement de la suppression par lot
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['confirm_deletion']) && !empty($_POST['ids_to_delete'])) {
        $ids_matchs = array_map('intval', $_POST['ids_to_delete']);
        try {
            $gestion_sportive->beginTransaction();
            $matchs_supprimes = [];
            $matchs_avec_participations = 0;

            foreach ($ids_matchs as $id_match) {
                $match_info = getMatchBasicInfo($gestion_sportive, $id_match);
                if ($match_info) {
                    $date_format = date("d/m/Y", strtotime($match_info['date_heure']));
                    $matchs_supprimes[] = $match_info['adversaire'] . " (" . $date_format . ")";
                }
                $nb_participations = getParticipationCountForMatch($gestion_sportive, $id_match);
                if ($nb_participations > 0) {
                    $matchs_avec_participations++;
                }
                clearParticipation($gestion_sportive, $id_match);
                deleteMatch($gestion_sportive, $id_match);
            }

            $gestion_sportive->commit();

            if (!empty($matchs_supprimes)) {
                $message = count($matchs_supprimes) . " match(s) supprimé(s) avec succès.";
                if ($matchs_avec_participations > 0) {
                    $message .= "<br><small>(" . $matchs_avec_participations . " match(s) avec évaluations supprimées)</small>";
                }
                $_SESSION['success_message'] = $message;
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } elseif (isset($_POST['demander_confirmation'])) {
        if (!empty($_POST['matchs_selectionnes'])) {
            $ids = array_map('intval', $_POST['matchs_selectionnes']);
            $matches_to_confirm = getMatchesBasicInfoByIds($gestion_sportive, $ids);
            $show_confirmation = true;
        } else {
            $error = "Veuillez sélectionner au moins un match à supprimer.";
        }
    }
}

include __DIR__ . "/../includes/header.php";

// Inclure la vue
include __DIR__ . "/../vues/supprimer_match_view.php";

include __DIR__ . "/../includes/footer.php";
