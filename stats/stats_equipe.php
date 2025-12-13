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
   CALCUL SCORE D'IMPACT (Nouveau)
   Pour estimer les chances de gagner avec un joueur
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
        $note_score = ($eval['moyenne'] / 6) * 30; // Normalisé sur 30 points
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
        $victoire_score = ($pct_victoire / 100) * 30; // Normalisé sur 30 points
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
        $consecutif_score = min($consecutifs * 2, 20); // 1 point par match, max 20
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
        $experience_score = min($total_matchs, 10); // 1 point par match, max 10
        $score += $experience_score;
        $facteurs['experience'] = round($experience_score, 1);
    }
    
    return [
        'score_total' => round(min($score, 100), 1), // Max 100
        'facteurs' => $facteurs,
        'pourcentage' => round(min($score, 100))
    ];
}

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

// Joueurs par statut
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
   DISTRIBUTION DES POSTES
======================= */
$distribution_postes = $gestion_sportive->query("
    SELECT 
        po.libelle as poste,
        COUNT(DISTINCT p.id_joueur) as nb_joueurs
    FROM participation p
    JOIN poste po ON po.id_poste = p.id_poste
    GROUP BY po.id_poste, po.libelle
    ORDER BY nb_joueurs DESC
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   STATISTIQUES DÉTAILLÉES DES JOUEURS AVEC SCORE D'IMPACT
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
    ORDER BY j.nom, j.prenom
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
    
    // CALCUL DU SCORE D'IMPACT (Nouveau)
    $score_impact = calculerScoreImpact($id, $gestion_sportive);
    $joueur['score_impact'] = $score_impact['score_total'];
    $joueur['pourcentage_impact'] = $score_impact['pourcentage'];
    $joueur['facteurs_impact'] = $score_impact['facteurs'];
}
unset($joueur); // Détruire la référence

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques de l'Équipe - Score d'Impact</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    /* =============================
       VARIABLES & RESET
    ============================= */
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --secondary: #2ecc71;
        --accent: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --purple: #9b59b6;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.15);
        --radius: 16px;
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        color: var(--dark);
        min-height: 100vh;
    }

    .page-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* =============================
       HEADER
    ============================= */
    .page-header {
        margin-bottom: 40px;
    }

    .page-header h1 {
        font-size: 2.8rem;
        color: var(--dark);
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-header p {
        font-size: 1.2rem;
        color: var(--gray);
        max-width: 800px;
    }

    /* =============================
       CARTE SCORE D'IMPACT (Nouveau)
    ============================= */
    .impact-card {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        margin-bottom: 25px;
        border-left: 5px solid var(--primary);
    }

    .impact-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .impact-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .impact-info h3 {
        font-size: 1.4rem;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .impact-info p {
        color: var(--gray);
        font-size: 0.95rem;
    }

    .score-container {
        display: flex;
        align-items: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .score-circle {
        position: relative;
        width: 120px;
        height: 120px;
    }

    .circle-bg {
        fill: none;
        stroke: #eee;
        stroke-width: 8;
    }

    .circle-progress {
        fill: none;
        stroke-width: 8;
        stroke-linecap: round;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
        transition: stroke-dashoffset 1s ease;
    }

    .circle-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }

    .circle-value {
        font-size: 2rem;
        font-weight: 700;
    }

    .circle-label {
        font-size: 0.9rem;
        color: var(--gray);
    }

    .facteurs-container {
        flex: 1;
        min-width: 300px;
    }

    .facteur-item {
        margin-bottom: 15px;
    }

    .facteur-label {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .facteur-nom {
        color: var(--dark);
    }

    .facteur-valeur {
        font-weight: 600;
        color: var(--primary);
    }

    .facteur-bar {
        height: 8px;
        background: #eee;
        border-radius: 4px;
        overflow: hidden;
    }

    .facteur-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--secondary), var(--primary));
        border-radius: 4px;
        transition: width 0.8s ease;
    }

    /* =============================
       GRID PRINCIPAL
    ============================= */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    /* =============================
       CARTES DE STATISTIQUES
    ============================= */
    .stat-card {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary), var(--primary));
    }

    .stat-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .stat-title {
        font-size: 1.4rem;
        font-weight: 700;
        color: var(--dark);
    }

    /* =============================
       TOP PERFORMERS
    ============================= */
    .top-players {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
    }

    .player-ranking {
        display: flex;
        flex-direction: column;
        gap: 15px;
        margin-top: 20px;
    }

    .player-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: #f8fafc;
        border-radius: 12px;
        transition: var(--transition);
    }

    .player-item:hover {
        background: #e8f5e9;
        transform: translateX(5px);
    }

    .player-rank {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--accent), #e67e22);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.2rem;
        color: white;
    }

    .player-info {
        flex: 1;
    }

    .player-name {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .player-stats {
        display: flex;
        gap: 20px;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .player-rating {
        color: var(--accent);
        font-weight: 700;
    }

    .player-impact {
        display: flex;
        align-items: center;
        gap: 5px;
        background: #e8f5e9;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
        color: var(--primary);
    }

    /* =============================
       TABLEAU DES STATS
    ============================= */
    .table-container {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-top: 40px;
    }

    .table-header {
        padding: 25px;
        border-bottom: 1px solid #f0f3f8;
    }

    .table-header h2 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.5rem;
    }

    .filters-container {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        padding: 20px;
        background: #f8fafc;
        border-bottom: 1px solid #e0e6ed;
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
    }

    .filter-select, .search-input {
        padding: 10px 15px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 500;
        min-width: 180px;
        transition: var(--transition);
    }

    .search-input {
        padding-left: 40px;
        min-width: 250px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%237f8c8d' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: 15px center;
        background-size: 16px;
    }

    .filter-select:focus, .search-input:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }

    .stats-table {
        width: 100%;
        border-collapse: collapse;
    }

    .stats-table thead {
        background: linear-gradient(90deg, var(--primary-dark), var(--primary));
        color: white;
    }

    .stats-table th {
        padding: 18px 15px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        cursor: pointer;
        user-select: none;
        position: relative;
    }

    .stats-table th:hover {
        background: rgba(255,255,255,0.1);
    }

    .stats-table th i {
        margin-left: 8px;
        opacity: 0.6;
        font-size: 0.8rem;
    }

    .stats-table tbody tr {
        border-bottom: 1px solid #f0f3f8;
        transition: var(--transition);
    }

    .stats-table tbody tr:hover {
        background: #f8fafc;
    }

    .stats-table td {
        padding: 16px 15px;
        vertical-align: middle;
    }

    .player-cell {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .player-avatar {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
    }

    /* =============================
       BADGES
    ============================= */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .badge.actif { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
    .badge.blesse { background: linear-gradient(135deg, #fff8e1, #ffe082); color: #f57c00; }
    .badge.suspendu { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }
    .badge.absent { background: linear-gradient(135deg, #eceff1, #cfd8dc); color: #455a64; }

    .poste-badge {
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        display: inline-block;
    }

    /* =============================
       INDICATEUR SCORE D'IMPACT
    ============================= */
    .impact-indicator {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 12px;
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        border-radius: 20px;
        font-weight: 700;
        font-size: 0.9rem;
        color: var(--primary-dark);
    }

    .impact-high { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
    .impact-medium { background: linear-gradient(135deg, #fff8e1, #ffe082); color: #f57c00; }
    .impact-low { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }

    /* =============================
       RATING STARS
    ============================= */
    .rating-stars {
        display: flex;
        align-items: center;
        gap: 3px;
    }

    .star {
        color: #ddd;
        font-size: 0.9rem;
    }

    .star.filled {
        color: var(--accent);
    }

    /* =============================
       ANIMATIONS
    ============================= */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card, .results-card, .top-players, .impact-card {
        animation: fadeInUp 0.5s ease forwards;
    }

    @keyframes progressAnimation {
        from {
            stroke-dashoffset: 314;
        }
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .filters-container {
            flex-direction: column;
        }
        
        .filter-select, .search-input {
            min-width: 100%;
        }
        
        .stats-table {
            display: block;
            overflow-x: auto;
        }
        
        .score-container {
            flex-direction: column;
            align-items: flex-start;
        }
    }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- HEADER -->
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Tableau de Bord Statistiques</h1>
            <p>Analyses détaillées des performances de l'équipe et des joueurs</p>
        </div>

        <!-- NOUVEAU: CARTE SCORE D'IMPACT -->
        <div class="impact-card">
            <div class="impact-header">
                <div class="impact-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="impact-info">
                    <h3>Système de Score d'Impact</h3>
                    <p>Algorithme prédictif pour estimer les chances de gagner avec chaque joueur</p>
                </div>
            </div>
            <div class="score-container">
                <div class="score-circle">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle class="circle-bg" cx="60" cy="60" r="50"></circle>
                        <circle class="circle-progress" cx="60" cy="60" r="50" 
                                style="stroke-dasharray: 314; stroke-dashoffset: <?= 314 - (314 * ($performance_moyenne * 100 / 600)) / 100 ?>; stroke: <?= 
                                    $performance_moyenne >= 4.5 ? '#2ecc71' : 
                                    ($performance_moyenne >= 3 ? '#f39c12' : '#e74c3c') ?>;">
                        </circle>
                    </svg>
                    <div class="circle-text">
                        <div class="circle-value"><?= number_format($performance_moyenne, 1) ?></div>
                        <div class="circle-label">Score global</div>
                    </div>
                </div>
                <div class="facteurs-container">
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Moyenne des évaluations</span>
                            <span class="facteur-valeur">30%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Pourcentage de victoires</span>
                            <span class="facteur-valeur">30%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Régularité (sélections consécutives)</span>
                            <span class="facteur-valeur">20%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Performance au poste</span>
                            <span class="facteur-valeur">10%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Expérience (matchs joués)</span>
                            <span class="facteur-valeur">10%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES GÉNÉRALES -->
        <div class="stats-grid">
            <!-- RÉSULTATS GÉNÉRAUX -->

            <!-- TOP PERFORMERS AVEC SCORE D'IMPACT -->
            <div class="top-players">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h2 class="stat-title">Top Performers & Score d'Impact</h2>
                </div>
                
                <div class="player-ranking">
                    <?php if (!empty($top_performers)): ?>
                        <?php foreach ($top_performers as $index => $joueur): ?>
                            <div class="player-item">
                                <div class="player-rank"><?= $index + 1 ?></div>
                                <div class="player-info">
                                    <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                    <div class="player-stats">
                                        <span class="player-rating">
                                            <i class="fas fa-star"></i> <?= $joueur['moyenne'] ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-gamepad"></i> <?= $joueur['nb_matchs'] ?> matchs
                                        </span>
                                        <span class="player-impact">
                                            <i class="fas fa-bolt"></i> <?= $joueur['pourcentage_impact'] ?>%
                                        </span>
                                    </div>
                                </div>
                                <span class="badge <?= statutClass($joueur['statut']) ?>">
                                    <?= htmlspecialchars($joueur['statut']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px 0; opacity: 0.6;">
                            <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <div>Aucune donnée de performance disponible</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TABLEAU DES STATISTIQUES DÉTAILLÉES AVEC SCORE D'IMPACT -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-table"></i> Statistiques Détaillées par Joueur avec Score d'Impact</h2>
            </div>
            
            <div class="filters-container">
                <div class="filter-group">
                    <label class="filter-label">Filtrer par statut</label>
                    <select class="filter-select" id="filtreStatut">
                        <option value="">Tous les statuts</option>
                        <?php foreach ($joueurs_statut as $stat): ?>
                            <option value="<?= $stat['statut_code'] ?>"><?= htmlspecialchars($stat['statut']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group" style="flex: 1;">
                    <label class="filter-label">Rechercher un joueur</label>
                    <input type="text" class="search-input" id="recherche" placeholder="Nom, prénom...">
                </div>
                
                <div class="filter-group">
                    <label class="filter-label">Trier par</label>
                    <select class="filter-select" id="triStatistique">
                        <option value="nom">Nom A-Z</option>
                        <option value="impact_desc">Score d'impact ▼</option>
                        <option value="impact_asc">Score d'impact ▲</option>
                        <option value="moyenne_desc">Note décroissante</option>
                        <option value="victoires_desc">% victoires</option>
                    </select>
                </div>
            </div>
            
            <table class="stats-table" id="tableStats">
                <thead>
                    <tr>
                        <th data-sort="nom">Joueur <i class="fas fa-sort"></i></th>
                        <th data-sort="statut">Statut <i class="fas fa-sort"></i></th>
                        <th data-sort="poste">Poste préféré <i class="fas fa-sort"></i></th>
                        <th data-sort="moyenne">Moy. notes <i class="fas fa-sort"></i></th>
                        <th data-sort="victoires">% victoires <i class="fas fa-sort"></i></th>
                        <th data-sort="impact">Score d'impact <i class="fas fa-sort"></i></th>
                        <th>Facteurs</th>
                        <th data-sort="consecutifs">Forme <i class="fas fa-sort"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($joueurs_stats as $j): 
                        $impactClass = $j['pourcentage_impact'] >= 70 ? 'impact-high' : 
                                     ($j['pourcentage_impact'] >= 40 ? 'impact-medium' : 'impact-low');
                    ?>
                    <tr data-statut="<?= $j['statut_code'] ?>"
                        data-nom="<?= htmlspecialchars(strtolower($j['prenom'] . ' ' . $j['nom'])) ?>"
                        data-poste="<?= htmlspecialchars(strtolower($j['poste_prefere'])) ?>"
                        data-moyenne="<?= $j['moyenne_notes'] ?? 0 ?>"
                        data-matchs="<?= $j['nb_matchs'] ?? 0 ?>"
                        data-victoires="<?= $j['pct_victoires'] ?>"
                        data-impact="<?= $j['score_impact'] ?>"
                        data-consecutifs="<?= $j['selections_consecutives'] ?>">
                        
                        <td>
                            <div class="player-cell">
                                <div class="player-avatar">
                                    <?= strtoupper(substr($j['prenom'], 0, 1) . substr($j['nom'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 600;"><?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?></div>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        <?= $j['nb_matchs'] ?> match(s)
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <span class="badge <?= statutClass($j['statut']) ?>">
                                <?= htmlspecialchars($j['statut']) ?>
                            </span>
                        </td>
                        
                        <td>
                            <?php if ($j['poste_prefere'] !== '—'): ?>
                                <span class="poste-badge" style="background: <?= getPosteColor($j['poste_prefere']) ?>;">
                                    <?= htmlspecialchars($j['poste_prefere']) ?>
                                </span>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <?php if ($j['moyenne_notes']): ?>
                                <div class="rating-stars">
                                    <?php 
                                    $note = round($j['moyenne_notes']);
                                    for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star star <?= $i <= $note ? 'filled' : '' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--gray); margin-top: 3px;">
                                    <?= number_format($j['moyenne_notes'], 1) ?>
                                </div>
                            <?php else: ?>
                                <span style="opacity: 0.6;">—</span>
                            <?php endif; ?>
                        </td>
                        
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="font-weight: 600; min-width: 50px; text-align: right;">
                                    <?= number_format($j['pct_victoires'], 1) ?>%
                                </div>
                                <div style="flex: 1;">
                                    <div style="height: 6px; background: #e0e6ed; border-radius: 3px; overflow: hidden;">
                                        <div style="height: 100%; width: <?= $j['pct_victoires'] ?>%; 
                                                 background: <?= $j['pct_victoires'] >= 50 ? 'var(--secondary)' : 'var(--danger)' ?>;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div class="impact-indicator <?= $impactClass ?>">
                                    <i class="fas fa-bolt"></i>
                                    <?= $j['pourcentage_impact'] ?>%
                                </div>
                                <div style="font-size: 0.85rem; color: var(--gray);">
                                    /100
                                </div>
                            </div>
                        </td>
                        
                        <td>
                            <div style="font-size: 0.75rem; color: var(--gray); line-height: 1.4;">
                                <?php if (isset($j['facteurs_impact'])): ?>
                                    <?php foreach ($j['facteurs_impact'] as $facteur => $valeur): ?>
                                        <div><?= ucfirst($facteur) ?>: <?= $valeur ?> pts</div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div style="opacity: 0.6;">Données insuffisantes</div>
                                <?php endif; ?>
                            </div>
                        </td>
                        
                        <td>
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <div style="font-weight: 700; font-size: 1.2rem; color: <?= 
                                    $j['selections_consecutives'] >= 3 ? 'var(--secondary)' : 
                                    ($j['selections_consecutives'] >= 1 ? 'var(--accent)' : 'var(--gray)') ?>;">
                                    <?= $j['selections_consecutives'] ?>
                                </div>
                                <div style="font-size: 0.85rem; color: var(--gray);">
                                    match(s) consécutif(s)
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // =============================
    // FILTRES ET TRI
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const filtreStatut = document.getElementById('filtreStatut');
        const recherche = document.getElementById('recherche');
        const triStatistique = document.getElementById('triStatistique');
        const tbody = document.querySelector('#tableStats tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        let sortAscending = true;
        let currentSort = 'nom';
        
        // Fonction d'application des filtres
        function appliquerFiltres() {
            const filtreStatutVal = filtreStatut.value;
            const rechercheVal = recherche.value.toLowerCase();
            
            rows.forEach(row => {
                const statut = row.dataset.statut;
                const nom = row.dataset.nom;
                
                const okStatut = !filtreStatutVal || statut === filtreStatutVal;
                const okRecherche = !rechercheVal || nom.includes(rechercheVal);
                
                row.style.display = (okStatut && okRecherche) ? '' : 'none';
            });
        }
        
        // Fonction de tri
        function trierTableau(colonne) {
            currentSort = colonne;
            const visibleRows = rows.filter(row => row.style.display !== 'none');
            
            visibleRows.sort((a, b) => {
                let valA, valB;
                
                switch(colonne) {
                    case 'nom':
                        valA = a.dataset.nom;
                        valB = b.dataset.nom;
                        break;
                    case 'statut':
                        valA = a.dataset.statut;
                        valB = b.dataset.statut;
                        break;
                    case 'poste':
                        valA = a.dataset.poste;
                        valB = b.dataset.poste;
                        break;
                    case 'moyenne':
                        valA = parseFloat(a.dataset.moyenne) || 0;
                        valB = parseFloat(b.dataset.moyenne) || 0;
                        break;
                    case 'victoires':
                        valA = parseFloat(a.dataset.victoires) || 0;
                        valB = parseFloat(b.dataset.victoires) || 0;
                        break;
                    case 'impact':
                        valA = parseFloat(a.dataset.impact) || 0;
                        valB = parseFloat(b.dataset.impact) || 0;
                        break;
                    case 'consecutifs':
                        valA = parseInt(a.dataset.consecutifs) || 0;
                        valB = parseInt(b.dataset.consecutifs) || 0;
                        break;
                    default:
                        valA = a.dataset.nom;
                        valB = b.dataset.nom;
                }
                
                if (typeof valA === 'number') {
                    return sortAscending ? valA - valB : valB - valA;
                } else {
                    return sortAscending ? valA.localeCompare(valB) : valB.localeCompare(valA);
                }
            });
            
            // Réinsérer dans l'ordre
            visibleRows.forEach(row => tbody.appendChild(row));
        }
        
        // Événements
        filtreStatut.addEventListener('change', appliquerFiltres);
        recherche.addEventListener('input', appliquerFiltres);
        triStatistique.addEventListener('change', function() {
            const [colonne, direction] = this.value.split('_');
            sortAscending = direction !== 'desc';
            trierTableau(colonne);
        });
        
        // Tri au clic sur les en-têtes
        document.querySelectorAll('#tableStats th[data-sort]').forEach(th => {
            th.addEventListener('click', function() {
                const colonne = this.dataset.sort;
                if (currentSort === colonne) {
                    sortAscending = !sortAscending;
                } else {
                    sortAscending = true;
                    currentSort = colonne;
                }
                trierTableau(colonne);
            });
        });
        
        // Animation des cercles de score
        document.querySelectorAll('.circle-progress').forEach(circle => {
            const radius = circle.r.baseVal.value;
            const circumference = radius * 2 * Math.PI;
            const offset = circumference - (circle.style.strokeDashoffset.replace('px', ''));
            circle.style.strokeDasharray = `${circumference} ${circumference}`;
            circle.style.strokeDashoffset = circumference;
            
            setTimeout(() => {
                circle.style.transition = 'stroke-dashoffset 1s ease-in-out';
                circle.style.strokeDashoffset = offset;
            }, 500);
        });
        
        // Animation des barres de facteurs
        document.querySelectorAll('.facteur-fill').forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0%';
            
            setTimeout(() => {
                bar.style.transition = 'width 0.8s ease-in-out';
                bar.style.width = width;
            }, 300);
        });
    });
    
    // =============================
    // ANIMATIONS ET EFFETS
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        // Animation au scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.stat-card, .results-card, .top-players, .impact-card');
            
            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (elementTop < windowHeight - 100) {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Appel initial
        
        // Tooltip pour le score d'impact
        const impactIndicators = document.querySelectorAll('.impact-indicator');
        impactIndicators.forEach(indicator => {
            indicator.addEventListener('mouseenter', function(e) {
                const score = this.textContent.match(/\d+/)[0];
                const tooltip = document.createElement('div');
                tooltip.className = 'impact-tooltip';
                tooltip.innerHTML = `
                    <strong>Score d'Impact: ${score}/100</strong><br>
                    ${score >= 70 ? 'Très haute probabilité de victoire' : 
                      score >= 40 ? 'Probabilité moyenne de victoire' : 
                      'Probabilité faible de victoire'}
                `;
                tooltip.style.cssText = `
                    position: absolute;
                    background: #2c3e50;
                    color: white;
                    padding: 10px;
                    border-radius: 6px;
                    font-size: 0.8rem;
                    z-index: 1000;
                    max-width: 200px;
                    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                `;
                document.body.appendChild(tooltip);
                
                const rect = this.getBoundingClientRect();
                tooltip.style.top = (rect.top - tooltip.offsetHeight - 10) + 'px';
                tooltip.style.left = (rect.left + rect.width/2 - tooltip.offsetWidth/2) + 'px';
                
                this._tooltip = tooltip;
            });
            
            indicator.addEventListener('mouseleave', function() {
                if (this._tooltip) {
                    this._tooltip.remove();
                    this._tooltip = null;
                }
            });
        });
    });
    </script>
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>