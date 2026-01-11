<?php
// affiche les statistiques completes de l'equipe et des joueurs

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_stats.php";
include __DIR__ . "/../includes/header.php";

/* =======================
   FONCTIONS UTILITAIRES
======================= */
function pct($v, $t) {
    return $t > 0 ? round(($v / $t) * 100, 1) : 0;
}

function statutClass($statut) {
    return match ($statut) {
        'Actif' => 'actif',
        'Blessé' => 'blesse',
        'Suspendu' => 'suspendu',
        'Absent' => 'absent',
        default => ''
    };
}

function getPosteColor($poste) {
    return match ($poste) {
        'Gardien' => '#3498db',
        'Défenseur' => '#2ecc71',
        'Milieu' => '#f39c12',
        'Attaquant' => '#e74c3c',
        default => '#95a5a6'
    };
}

/* =======================
   RÉCUPÉRATION DES FILTRES
======================= */
$filtre_statut = $_GET['statut'] ?? '';
$recherche = $_GET['recherche'] ?? '';
$tri = $_GET['tri'] ?? 'nom';

/* =======================
   STATISTIQUES GÉNÉRALES
======================= */
// Matchs
$stats_matchs = getTeamMatchStats($gestion_sportive);

// Performances moyennes
$performance_moyenne = getAverageEvaluation($gestion_sportive);

// Joueurs par statut (pour le filtre)
$joueurs_statut = getPlayersByStatut($gestion_sportive);

/* =======================
   STATISTIQUES DÉTAILLÉES DES JOUEURS
======================= */
$joueurs_stats = getPlayersStatsDetailed($gestion_sportive);

// Ajout des statistiques supplémentaires pour chaque joueur
$matchsJoues = getPlayedMatchIds($gestion_sportive);
foreach ($joueurs_stats as &$joueur) {
    $id = $joueur['id_joueur'];
    
    // Poste préféré
    $joueur['poste_prefere'] = getPlayerPreferredPoste($gestion_sportive, $id) ?: "—";
    
    // Pourcentage de victoires
    $win_stats = getPlayerWinStats($gestion_sportive, $id);
    $joueur['pct_victoires'] = $win_stats['total'] > 0 ? 
        pct($win_stats['victoires'], $win_stats['total']) : 0;
    
    // Sélections consécutives
    $consecutifs = 0;
    foreach ($matchsJoues as $mid) {
        if (getPlayerParticipationCountForMatch($gestion_sportive, (int)$mid, $id) > 0) {
            $consecutifs++;
        }
        else break;
    }
    $joueur['selections_consecutives'] = $consecutifs;
}
unset($joueur);

/* =======================
   FILTRAGE ET TRI DES DONNÉES
======================= */
$joueurs_filtres = $joueurs_stats;

// Filtre par statut
if (!empty($filtre_statut)) {
    $joueurs_filtres = array_filter($joueurs_filtres, function($j) use ($filtre_statut) {
        return $j['statut_code'] == $filtre_statut;
    });
}

// Filtre par recherche (nom/prenom)
if (!empty($recherche)) {
    $recherche_lower = strtolower(trim($recherche));
    $joueurs_filtres = array_filter($joueurs_filtres, function($j) use ($recherche_lower) {
        $nom_complet = strtolower($j['prenom'] . ' ' . $j['nom']);
        return strpos($nom_complet, $recherche_lower) !== false;
    });
}

// Tri des joueurs
usort($joueurs_filtres, function($a, $b) use ($tri) {
    switch ($tri) {
        case 'moyenne_desc':
            $moyenne_a = $a['moyenne_notes'] ?? 0;
            $moyenne_b = $b['moyenne_notes'] ?? 0;
            return $moyenne_b <=> $moyenne_a;
        case 'victoires_desc':
            return $b['pct_victoires'] <=> $a['pct_victoires'];
        case 'matchs_desc':
            return $b['nb_matchs'] <=> $a['nb_matchs'];
        case 'consecutifs_desc':
            return $b['selections_consecutives'] <=> $a['selections_consecutives'];
        default: // 'nom' (ordre alphabétique)
            $nom_a = $a['nom'] . $a['prenom'];
            $nom_b = $b['nom'] . $b['prenom'];
            return strcmp($nom_a, $nom_b);
    }
});

// Nombre total de joueurs (pour affichage)
$total_joueurs = count($joueurs_stats);
$joueurs_filtres_count = count($joueurs_filtres);

// Inclure la vue
include __DIR__ . "/../vues/stats_view.php";

include __DIR__ . "/../includes/footer.php";
?>
