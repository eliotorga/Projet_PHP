<?php
// affiche les statistiques completes de l'equipe et des joueurs
// calcule un score d'impact pour chaque joueur base sur plusieurs criteres

session_start();
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
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
   CALCUL SCORE D'IMPACT
======================= */
function calculerScoreImpact($joueur_id, $gestion_sportive) {
    $score = 0;
    $facteurs = [];
    
    // 1. Performance globale (25%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            AVG(evaluation) as moyenne,
            COUNT(*) as nb_matchs,
            STDDEV(evaluation) as ecart_type
        FROM participation
        WHERE id_joueur = ? 
        AND evaluation IS NOT NULL
        AND evaluation > 0
    ");
    $stmt->execute([$joueur_id]);
    $eval = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($eval['moyenne'] && $eval['nb_matchs'] > 0) {
        // Poids de la note basé sur le nombre de matchs (plus de matchs = plus fiable)
        $poids_matchs = min(1, $eval['nb_matchs'] / 10);
        $note_score = ($eval['moyenne'] / 6) * 25 * $poids_matchs;
        $score += $note_score;
        $facteurs['performance'] = round($note_score, 1);
        
        // Pénalité pour l'inconstance (écart-type élevé)
        if ($eval['ecart_type'] > 1.0) {
            $penalite = min(5, ($eval['ecart_type'] - 1.0) * 5);
            $score -= $penalite;
            $facteurs['penalite_inconstance'] = round(-$penalite, 1);
        }
    }
    
    // 2. Forme récente (25%) - Derniers 5 matchs
    $stmt = $gestion_sportive->prepare("
        SELECT AVG(p.evaluation) as moyenne_recente
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? 
        AND p.evaluation IS NOT NULL
        AND m.etat = 'JOUE'
        ORDER BY m.date_heure DESC
        LIMIT 5
    ");
    $stmt->execute([$joueur_id]);
    $forme_recente = $stmt->fetchColumn();
    
    if ($forme_recente) {
        $forme_score = ($forme_recente / 6) * 25;
        $score += $forme_score;
        $facteurs['forme_recente'] = round($forme_score, 1);
    }
    
    // 3. Impact sur le résultat (20%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            COUNT(DISTINCT m.id_match) as total,
            SUM(CASE 
                WHEN m.resultat = 'VICTOIRE' AND p.evaluation >= 4 THEN 1 
                WHEN m.resultat = 'NUL' AND p.evaluation >= 3.5 THEN 1
                WHEN m.resultat = 'DEFAITE' AND p.evaluation <= 2.5 THEN 0
                ELSE 0.5 
            END) as impact_positif
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? 
        AND m.etat = 'JOUE'
        AND p.evaluation IS NOT NULL
    ");
    $stmt->execute([$joueur_id]);
    $impact_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($impact_data['total'] > 0) {
        $impact_score = ($impact_data['impact_positif'] / $impact_data['total']) * 20;
        $score += $impact_score;
        $facteurs['impact'] = round($impact_score, 1);
    }
    
    // 4. Performance par poste (15%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            po.libelle as poste,
            AVG(p.evaluation) as moyenne_poste,
            COUNT(*) as nb_matchs_poste,
            (SELECT AVG(evaluation) FROM participation WHERE id_poste = p.id_poste) as moyenne_generale_poste
        FROM participation p
        JOIN poste po ON po.id_poste = p.id_poste
        WHERE p.id_joueur = ? 
        AND p.evaluation IS NOT NULL
        GROUP BY p.id_poste, po.libelle
        ORDER BY AVG(p.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$joueur_id]);
    $poste_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($poste_data && $poste_data['moyenne_poste'] > 0) {
        // Bonus si le joueur est au-dessus de la moyenne à son poste
        $bonus_poste = $poste_data['moyenne_poste'] > $poste_data['moyenne_generale_poste'] ? 2 : 0;
        $poste_score = (($poste_data['moyenne_poste'] / 6) * 13) + $bonus_poste;
        $score += $poste_score;
        $facteurs['poste'] = round($poste_score, 1);
    }
    
    // 5. Expérience et régularité (15%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            COUNT(*) as total_matchs,
            DATEDIFF(NOW(), MIN(m.date_heure)) as jours_premier_match,
            COUNT(DISTINCT DATE_FORMAT(m.date_heure, '%Y-%m')) as mois_actifs
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ?
    ");
    $stmt->execute([$joueur_id]);
    $experience_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($experience_data['total_matchs'] > 0) {
        // Score d'expérience basé sur le nombre de matchs (max 7.5 points)
        $exp_matchs = min(7.5, $experience_data['total_matchs'] * 0.5);
        
        // Score de régularité basé sur la fréquence de jeu (max 7.5 points)
        $mois_actifs = $experience_data['mois_actifs'];
        $mois_total = $experience_data['jours_premier_match'] > 0 ? 
            min(24, ceil($experience_data['jours_premier_match'] / 30)) : 1;
        $regularite = $mois_actifs / $mois_total;
        $regularite_score = $regularite * 7.5;
        
        $experience_score = $exp_matchs + $regularite_score;
        $score += $experience_score;
        $facteurs['experience'] = round($experience_score, 1);
    }
    
    return [
        'score_total' => round(min($score, 100), 1),
        'facteurs' => $facteurs,
        'pourcentage' => round(min($score, 100))
    ];
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
$stats_matchs = $gestion_sportive->query("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires,
        SUM(CASE WHEN resultat = 'DEFAITE' THEN 1 ELSE 0 END) as defaites,
        SUM(CASE WHEN resultat = 'NUL' THEN 1 ELSE 0 END) as nuls,
        SUM(CASE WHEN etat = 'JOUE' THEN 1 ELSE 0 END) as joues,
        SUM(CASE WHEN etat IN ('A_PREPARER', 'PREPARE') THEN 1 ELSE 0 END) as a_venir
    FROM matchs
")->fetch(PDO::FETCH_ASSOC);

// Performances moyennes
$performance_moyenne = $gestion_sportive->query("
    SELECT ROUND(AVG(evaluation), 2) as moyenne
    FROM participation
    WHERE evaluation IS NOT NULL
")->fetchColumn();

// Joueurs par statut (pour le filtre)
$joueurs_statut = $gestion_sportive->query("
    SELECT 
        s.libelle as statut,
        s.code as statut_code,
        COUNT(j.id_joueur) as nb_joueurs
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    GROUP BY s.id_statut, s.libelle, s.code
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   SECTION SCORE D'IMPACT
======================= */

// Données pour l'explication des facteurs
$facteurs_explication = [
    [
        'nom' => 'Performance globale',
        'poids' => '25%',
        'description' => "Note moyenne pondérée par le nombre de matchs. Pénalité pour inconstance (écart-type élevé)",
        'icon' => '📊',
        'color' => '#3498db'
    ],
    [
        'nom' => 'Forme récente',
        'poids' => '25%',
        'description' => 'Performance sur les 5 derniers matchs joués',
        'icon' => '📈',
        'color' => '#2ecc71'
    ],
    [
        'nom' => 'Impact sur résultats',
        'poids' => '20%',
        'description' => 'Capacité à influencer positivement le résultat des matchs',
        'icon' => '⚽',
        'color' => '#e74c3c'
    ],
    [
        'nom' => 'Performance par poste',
        'poids' => '15%',
        'description' => 'Adéquation avec le poste + bonus si au-dessus de la moyenne générale',
        'icon' => '🎯',
        'color' => '#f39c12'
    ],
    [
        'nom' => 'Expérience & régularité',
        'poids' => '15%',
        'description' => 'Nombre de matchs + fréquence de jeu sur la période',
        'icon' => '📅',
        'color' => '#9b59b6'
    ]
];

// Plages de score avec interprétations
$interpretations_score = [
    [
        'min' => 90,
        'max' => 100,
        'label' => 'Exceptionnel',
        'description' => 'Joueur clé, impact maximal',
        'color' => '#27ae60',
        'icon' => '🏆'
    ],
    [
        'min' => 75,
        'max' => 89,
        'label' => 'Excellent',
        'description' => 'Performance très élevée et régulière',
        'color' => '#2ecc71',
        'icon' => '⭐'
    ],
    [
        'min' => 60,
        'max' => 74,
        'label' => 'Bon',
        'description' => 'Contribution solide et fiable',
        'color' => '#3498db',
        'icon' => '✓'
    ],
    [
        'min' => 45,
        'max' => 59,
        'label' => 'Moyen',
        'description' => 'Performance acceptable avec marges de progression',
        'color' => '#f1c40f',
        'icon' => '↔️'
    ],
    [
        'min' => 30,
        'max' => 44,
        'label' => 'À améliorer',
        'description' => 'Impact limité, besoin de progression',
        'color' => '#e67e22',
        'icon' => '📉'
    ],
    [
        'min' => 0,
        'max' => 29,
        'label' => 'Faible',
        'description' => 'Impact insuffisant sur le collectif',
        'color' => '#e74c3c',
        'icon' => '⚠️'
    ]
];

/* =======================
   TOP PERFORMERS AVEC SCORE D'IMPACT
======================= */
$top_performers = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        ROUND(AVG(p.evaluation), 2) as moyenne,
        COUNT(DISTINCT p.id_match) as nb_matchs,
        s.libelle as statut
    FROM joueur j
    JOIN participation p ON p.id_joueur = j.id_joueur
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE p.evaluation IS NOT NULL
    GROUP BY j.id_joueur, j.nom, j.prenom, s.libelle
    ORDER BY moyenne DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Ajouter le score d'impact aux top performers
foreach ($top_performers as &$joueur) {
    $score_impact = calculerScoreImpact($joueur['id_joueur'], $gestion_sportive);
    $joueur['score_impact'] = $score_impact['score_total'];
    $joueur['pourcentage_impact'] = $score_impact['pourcentage'];
}
unset($joueur);

/* =======================
   STATISTIQUES DÉTAILLÉES DES JOUEURS
======================= */
$joueurs_stats = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        s.libelle AS statut,
        s.code AS statut_code,
        COUNT(DISTINCT p.id_match) AS nb_matchs,
        SUM(CASE WHEN p.role = 'TITULAIRE' THEN 1 ELSE 0 END) AS titularisations,
        SUM(CASE WHEN p.role = 'REMPLACANT' THEN 1 ELSE 0 END) AS remplacements,
        ROUND(AVG(p.evaluation), 2) AS moyenne_notes,
        COUNT(p.evaluation) AS nb_evaluations
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    LEFT JOIN participation p ON p.id_joueur = j.id_joueur
    GROUP BY j.id_joueur, j.nom, j.prenom, s.libelle, s.code
")->fetchAll(PDO::FETCH_ASSOC);

// Ajout des statistiques supplémentaires pour chaque joueur
foreach ($joueurs_stats as &$joueur) {
    $id = $joueur['id_joueur'];
    
    // Poste préféré
    $stmt = $gestion_sportive->prepare("
        SELECT po.libelle
        FROM participation pa
        JOIN poste po ON po.id_poste = pa.id_poste
        WHERE pa.id_joueur = ? AND pa.evaluation IS NOT NULL
        GROUP BY pa.id_poste, po.libelle
        ORDER BY COUNT(*) DESC, AVG(pa.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $joueur['poste_prefere'] = $stmt->fetchColumn() ?: "—";
    
    // Pourcentage de victoires
    $stmt = $gestion_sportive->prepare("
        SELECT 
            COUNT(DISTINCT m.id_match) as total,
            SUM(CASE WHEN m.resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur = ? AND m.etat = 'JOUE'
    ");
    $stmt->execute([$id]);
    $win_stats = $stmt->fetch(PDO::FETCH_ASSOC);
    $joueur['pct_victoires'] = $win_stats['total'] > 0 ? 
        pct($win_stats['victoires'], $win_stats['total']) : 0;
    
    // Sélections consécutives
    $matchsJoues = $gestion_sportive->query("
        SELECT id_match
        FROM matchs
        WHERE etat='JOUE'
        ORDER BY date_heure DESC
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    $consecutifs = 0;
    foreach ($matchsJoues as $mid) {
        $stmt = $gestion_sportive->prepare("
            SELECT COUNT(*) FROM participation
            WHERE id_match=? AND id_joueur=?
        ");
        $stmt->execute([$mid, $id]);
        if ($stmt->fetchColumn() > 0) $consecutifs++;
        else break;
    }
    $joueur['selections_consecutives'] = $consecutifs;
    
    // CALCUL DU SCORE D'IMPACT
    $score_impact = calculerScoreImpact($id, $gestion_sportive);
    $joueur['score_impact'] = $score_impact['score_total'];
    $joueur['pourcentage_impact'] = $score_impact['pourcentage'];
    $joueur['facteurs_impact'] = $score_impact['facteurs'];
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
        case 'impact_desc':
            return $b['score_impact'] <=> $a['score_impact'];
        case 'impact_asc':
            return $a['score_impact'] <=> $b['score_impact'];
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