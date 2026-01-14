<?php
// Controller: affiche le profil detaille d'un joueur specifique
// stats personnelles, historique des matchs et commentaires

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once __DIR__ . "/../modele/joueur.php";
require_once __DIR__ . "/../modele/participation.php";

// Vérifier si l'ID du joueur est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: liste_joueurs.php");
    exit;
}

$id_joueur = intval($_GET['id']);

// Récupérer les informations du joueur
$joueur = getPlayerProfile($gestion_sportive, $id_joueur);

if (!$joueur) {
    $_SESSION['error_message'] = "Joueur non trouvé.";
    header("Location: liste_joueurs.php");
    exit;
}

// Récupérer les statistiques des matchs du joueur
$stats = getPlayerMatchStats($gestion_sportive, $id_joueur);

// Récupérer l'historique des matchs du joueur
$matchs_joueur = getPlayerMatchHistory($gestion_sportive, $id_joueur);

// Récupérer les commentaires du joueur
$commentaires = getComments($gestion_sportive, $id_joueur);

// Récupérer la répartition des postes
$postes = getPlayerPosteDistribution($gestion_sportive, $id_joueur);

include "../includes/header.php";

// Inclure la vue
include "../vues/joueur_perso_view.php";

include "../includes/footer.php";
