<?php
// Controller: affiche l'historique de toutes les compositions de matchs passes
// liste les joueurs avec leurs postes, roles et evaluations pour chaque match

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../modele/match.php";
require_once "../modele/participation.php";

/* Récupération de tous les matchs */
$matches = getAllMatches($gestion_sportive);

include "../includes/header.php";

// Inclure la vue
include "../vues/historique_feuille_view.php";

include "../includes/footer.php";
