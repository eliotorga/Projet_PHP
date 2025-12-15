<?php
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
    
    // 1. Moyenne des évaluations (30%)
    $stmt = $gestion_sportive->prepare("
        SELECT AVG(evaluation) as moyenne, COUNT(*) as nb_matchs
        FROM participation
        WHERE id_joueur = ? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$joueur_id]);
    $eval = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($eval['moyenne'] && $eval['nb_matchs'] > 0) {
        $note_score = ($eval['moyenne'] / 6) * 30;
        $score += $note_score;
        $facteurs['evaluation'] = round($note_score, 1);
    }
    
    // 2. Pourcentage de victoires (30%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            COUNT(DISTINCT m.id_match) as total,
            SUM(CASE WHEN m.resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires
        FROM participation p
        JOIN matchs m ON m.id_match = p.id_match
        WHERE p.id_joueur = ? AND m.etat = 'JOUE'
    ");
    $stmt->execute([$joueur_id]);
    $victoires = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($victoires['total'] > 0) {
        $pct_victoire = ($victoires['victoires'] / $victoires['total']) * 100;
        $victoire_score = ($pct_victoire / 100) * 30;
        $score += $victoire_score;
        $facteurs['victoires'] = round($victoire_score, 1);
    }
    
    // 3. Régularité (consécutifs) (20%)
    $stmt = $gestion_sportive->prepare("
        SELECT COUNT(*) as consecutifs
        FROM (
            SELECT m.id_match
            FROM matchs m
            WHERE m.etat = 'JOUE'
            ORDER BY m.date_heure DESC
        ) as matchs_recents
        WHERE EXISTS (
            SELECT 1 FROM participation p 
            WHERE p.id_match = matchs_recents.id_match 
            AND p.id_joueur = ?
        )
    ");
    $stmt->execute([$joueur_id]);
    $consecutifs = $stmt->fetchColumn();
    
    if ($consecutifs > 0) {
        $consecutif_score = min($consecutifs * 2, 20);
        $score += $consecutif_score;
        $facteurs['consecutifs'] = round($consecutif_score, 1);
    }
    
    // 4. Performance par poste (10%)
    $stmt = $gestion_sportive->prepare("
        SELECT 
            po.libelle as poste,
            AVG(p.evaluation) as moyenne_poste,
            COUNT(*) as nb_matchs_poste
        FROM participation p
        JOIN poste po ON po.id_poste = p.id_poste
        WHERE p.id_joueur = ? AND p.evaluation IS NOT NULL
        GROUP BY p.id_poste, po.libelle
        ORDER BY AVG(p.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$joueur_id]);
    $poste_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($poste_data && $poste_data['moyenne_poste'] > 0) {
        $poste_score = ($poste_data['moyenne_poste'] / 6) * 10;
        $score += $poste_score;
        $facteurs['poste'] = round($poste_score, 1);
    }
    
    // 5. Expérience (nombre de matchs) (10%)
    $stmt = $gestion_sportive->prepare("
        SELECT COUNT(*) as total_matchs
        FROM participation
        WHERE id_joueur = ?
    ");
    $stmt->execute([$joueur_id]);
    $total_matchs = $stmt->fetchColumn();
    
    if ($total_matchs > 0) {
        $experience_score = min($total_matchs, 10);
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