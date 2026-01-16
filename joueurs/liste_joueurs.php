<?php
// Controller: récupère les données des joueurs et inclut la vue
// affiche la liste complete de tous les joueurs avec leurs statistiques

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../modele/joueur.php";


  // RÉCUPÉRATION JOUEURS AVEC STATISTIQUES

$joueurs = getPlayersWithStats($gestion_sportive);

// Calcul de l'âge pour chaque joueur
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

// Statistiques globales
$stats = getStatutCounts($gestion_sportive);

// Filtrage et Tri PHP
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'nom';

if ($search || $statusFilter !== 'all') {
    $joueurs = array_filter($joueurs, function($j) use ($search, $statusFilter) {
        $matchSearch = true;
        if ($search) {
            $fullName = strtolower($j['nom'] . ' ' . $j['prenom']);
            $matchSearch = strpos($fullName, strtolower($search)) !== false;
        }

        $matchStatus = true;
        if ($statusFilter !== 'all') {
            $matchStatus = $j['statut_code'] === $statusFilter;
        }

        return $matchSearch && $matchStatus;
    });
}

usort($joueurs, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'note_desc':
            return ($b['note_moyenne'] ?? 0) <=> ($a['note_moyenne'] ?? 0);
        case 'matchs_desc':
            return ($b['nb_matchs'] ?? 0) <=> ($a['nb_matchs'] ?? 0);
        case 'age_asc':
            $ageA = is_numeric($a['age']) ? $a['age'] : 999;
            $ageB = is_numeric($b['age']) ? $b['age'] : 999;
            return $ageA <=> $ageB;
        case 'nom':
        default:
            return strcasecmp($a['nom'], $b['nom']);
    }
});

// Joueurs blessés pour notification
$joueurs_blesses = array_filter($joueurs, fn($j) => $j['statut_code'] === 'BLE');

include "../includes/header.php";

// Inclure la vue
include "../vues/liste_joueurs_view.php";

include "../includes/footer.php";
