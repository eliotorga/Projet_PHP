<?php
// Controller: permet de supprimer un ou plusieurs joueurs de la base de donnees
// affiche la liste des joueurs avec leurs stats et gere la confirmation avant suppression

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../modele/joueur.php";

// Initialiser les messages
$message = "";
$error = "";

// Récupérer tous les joueurs avec leurs statistiques
$joueurs = getPlayersWithStats($gestion_sportive);

// Calculer l'âge pour chaque joueur
foreach ($joueurs as &$joueur) {
    if ($joueur['date_naissance']) {
        $naissance = new DateTime($joueur['date_naissance']);
        $aujourdhui = new DateTime();
        $joueur['age'] = $aujourdhui->diff($naissance)->y;
    } else {
        $joueur['age'] = 'N/A';
    }
}
unset($joueur);

// Traitement de la suppression
$show_confirmation = false;
$players_to_confirm = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Cas 2 : Confirmation finale de la suppression
    if (isset($_POST['confirm_deletion']) && !empty($_POST['ids_to_delete'])) {
        $ids_joueurs = array_map('intval', $_POST['ids_to_delete']);

        try {
            $gestion_sportive->beginTransaction();

            $joueurs_supprimes = [];

            foreach ($ids_joueurs as $id_joueur) {
                $joueur_info = getPlayerNameById($gestion_sportive, $id_joueur);
                if ($joueur_info) {
                    $joueurs_supprimes[] = $joueur_info['prenom'] . ' ' . $joueur_info['nom'];
                }

                deletePlayerCascade($gestion_sportive, $id_joueur);
            }

            $gestion_sportive->commit();

            if (!empty($joueurs_supprimes)) {
                $_SESSION['success_message'] = count($joueurs_supprimes) . " joueur(s) supprimé(s) avec succès :<br>" .
                                               implode(', ', $joueurs_supprimes);
            }

            header("Location: " . $_SERVER['PHP_SELF']);
            exit;

        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
    // Cas 1 : Demande de suppression (premier clic)
    elseif (isset($_POST['supprimer_joueurs'])) {
        if (!empty($_POST['joueurs_selectionnes'])) {
            $show_confirmation = true;
            $ids = array_map('intval', $_POST['joueurs_selectionnes']);
            $players_to_confirm = getPlayersByIds($gestion_sportive, $ids);
        } else {
            $error = "Veuillez sélectionner au moins un joueur à supprimer.";
        }
    }
}

include "../includes/header.php";

// Inclure la vue
include "../vues/supprimer_joueur_view.php";

include "../includes/footer.php";
