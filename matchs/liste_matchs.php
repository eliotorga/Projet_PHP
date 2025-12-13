<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =====================
   RÉCUPÉRATION DES MATCHS AVEC STATISTIQUES
===================== */
$stmt = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        m.resultat,
        m.etat,
        m.score_equipe,
        m.score_adverse,
        COUNT(p.id_joueur) AS nb_joueurs,
        AVG(p.evaluation) AS moyenne_eval
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
");

$matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matchs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
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
        --warning: #e67e22;
        --info: #3498db;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.15);
        --radius: 12px;
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

    /* =============================
       CONTAINER PRINCIPAL
    ============================= */
    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* =============================
       HEADER ET FILTRES
    ============================= */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-title {
        flex: 1;
    }

    .page-title h1 {
        font-size: 2.4rem;
        color: var(--dark);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-title p {
        color: var(--gray);
        font-size: 1.1rem;
        max-width: 600px;
    }

    .btn-add-match {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
        padding: 14px 28px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        box-shadow: var(--shadow);
    }

    .btn-add-match:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(46, 204, 113, 0.3);
    }

    /* =============================
       FILTRES
    ============================= */
    .filters-container {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
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

    .filter-select {
        padding: 10px 15px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 500;
        min-width: 180px;
        transition: var(--transition);
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }

    .btn-filter {
        background: var(--info);
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: var(--transition);
        align-self: flex-end;
    }

    .btn-filter:hover {
        background: #2980b9;
    }

    /* =============================
       CARTES DE MATCH
    ============================= */
    .matches-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    @media (max-width: 768px) {
        .matches-grid {
            grid-template-columns: 1fr;
        }
    }

    .match-card {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
    }

    .match-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .match-header {
        padding: 20px;
        border-bottom: 1px solid #f0f3f8;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(90deg, var(--primary-dark), var(--primary));
        color: white;
    }

    .match-date {
        display: flex;
        flex-direction: column;
    }

    .match-day {
        font-size: 1.8rem;
        font-weight: 700;
    }

    .match-month {
        font-size: 0.9rem;
        opacity: 0.9;
        text-transform: uppercase;
    }

    .match-time {
        background: rgba(255,255,255,0.2);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .match-body {
        padding: 20px;
    }

    .match-teams {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .team-home, .team-away {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
    }

    .team-logo {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.2rem;
        font-weight: bold;
    }

    .team-name {
        font-weight: 600;
        text-align: center;
        max-width: 120px;
    }

    .match-score {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 5px;
    }

    .score {
        font-size: 2rem;
        font-weight: 700;
        color: var(--dark);
    }

    .score-divider {
        font-size: 1.5rem;
        color: var(--gray);
    }

    .match-status {
        font-size: 0.85rem;
        padding: 4px 12px;
        border-radius: 20px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .match-details {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .detail-item i {
        color: var(--primary);
        font-size: 1.1rem;
    }

    .detail-label {
        font-size: 0.85rem;
        color: var(--gray);
    }

    .detail-value {
        font-weight: 600;
        color: var(--dark);
    }

    /* =============================
       BADGES D'ÉTAT
    ============================= */
    .etat-badge {
        padding: 6px 15px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .A_PREPARER {
        background: linear-gradient(135deg, #fff8e1, #ffe082);
        color: #f57c00;
    }

    .PREPARE {
        background: linear-gradient(135deg, #e3f2fd, #bbdefb);
        color: #1565c0;
    }

    .JOUE {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        color: #2e7d32;
    }

    .VICTOIRE {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        color: #2e7d32;
    }

    .DEFAITE {
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        color: #c62828;
    }

    .NUL {
        background: linear-gradient(135deg, #eceff1, #cfd8dc);
        color: #455a64;
    }

    /* =============================
       ÉVALUATION
    ============================= */
    .evaluation-stars {
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .star {
        color: #ddd;
        font-size: 0.9rem;
    }

    .star.filled {
        color: #f39c12;
    }

    .evaluation-text {
        font-size: 0.85rem;
        color: var(--gray);
        margin-left: 8px;
    }

    /* =============================
       ACTIONS
    ============================= */




    /* AJOUTER DANS LA SECTION ACTIONS */
.btn-feuille {
    background: linear-gradient(135deg, #9b59b6, #8e44ad);
    color: white;
}

.btn-feuille:hover {
    background: linear-gradient(135deg, #8e44ad, #7d3c98);
    transform: translateY(-2px);
}

    .match-actions {
        display: flex;
        gap: 10px;
        padding: 15px 20px;
        background: #f8fafc;
        border-top: 1px solid #f0f3f8;
        flex-wrap: wrap;
    }

    .btn-action {
        flex: 1;
        min-width: 120px;
        padding: 10px 15px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: var(--transition);
        text-align: center;
        border: none;
        cursor: pointer;
    }

    .btn-compose {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
    }

    .btn-compose:hover {
        background: linear-gradient(135deg, #27ae60, #219653);
        transform: translateY(-2px);
    }

    .btn-modify {
        background: linear-gradient(135deg, var(--warning), #e67e22);
        color: white;
    }

    .btn-modify:hover {
        background: linear-gradient(135deg, #e67e22, #d35400);
        transform: translateY(-2px);
    }

    .btn-view {
        background: linear-gradient(135deg, var(--info), #3498db);
        color: white;
    }

    .btn-view:hover {
        background: linear-gradient(135deg, #3498db, #2980b9);
        transform: translateY(-2px);
    }

    .btn-eval {
        background: linear-gradient(135deg, #9b59b6, #8e44ad);
        color: white;
    }

    .btn-eval:hover {
        background: linear-gradient(135deg, #8e44ad, #7d3c98);
        transform: translateY(-2px);
    }

    .btn-edit {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        color: white;
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
        transform: translateY(-2px);
    }

    .btn-delete {
        background: linear-gradient(135deg, var(--danger), #c0392b);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        transform: translateY(-2px);
    }

    /* =============================
       STATISTIQUES
    ============================= */
    .stats-container {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        padding: 15px;
        background: #f8fafc;
        border-radius: var(--radius);
        transition: var(--transition);
    }

    .stat-item:hover {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        transform: translateY(-3px);
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 10px;
        color: white;
        font-size: 1.3rem;
    }

    .stat-number {
        font-size: 2rem;
        font-weight: 700;
        color: var(--primary);
        margin: 5px 0;
    }

    .stat-label {
        font-size: 0.9rem;
        color: var(--gray);
    }

    /* =============================
       WORKFLOW
    ============================= */
    .workflow-container {
        background: linear-gradient(135deg, #2c3e50, #34495e);
        border-radius: var(--radius);
        padding: 30px;
        color: white;
        margin-top: 40px;
        box-shadow: var(--shadow);
    }

    .workflow-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        font-size: 1.3rem;
    }

    .workflow-steps {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        gap: 15px;
        position: relative;
    }

    .workflow-steps::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 10%;
        right: 10%;
        height: 3px;
        background: rgba(255,255,255,0.3);
        z-index: 1;
    }

    .workflow-step {
        flex: 1;
        min-width: 180px;
        text-align: center;
        position: relative;
        z-index: 2;
    }

    .step-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        font-size: 1.3rem;
        color: white;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }

    .step-title {
        font-weight: 600;
        margin-bottom: 8px;
        font-size: 1.1rem;
    }

    .step-desc {
        font-size: 0.9rem;
        opacity: 0.8;
        line-height: 1.4;
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

    .match-card {
        animation: fadeInUp 0.5s ease forwards;
    }

    .match-card:nth-child(1) { animation-delay: 0.1s; }
    .match-card:nth-child(2) { animation-delay: 0.2s; }
    .match-card:nth-child(3) { animation-delay: 0.3s; }
    .match-card:nth-child(4) { animation-delay: 0.4s; }
    .match-card:nth-child(5) { animation-delay: 0.5s; }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .btn-add-match {
            width: 100%;
            justify-content: center;
        }
        
        .workflow-steps {
            flex-direction: column;
        }
        
        .workflow-steps::before {
            display: none;
        }
        
        .match-details {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-calendar-alt"></i> Calendrier des Matchs</h1>
                <p>Gérez vos matchs de la préparation à l'analyse post-match</p>
            </div>
            <a href="ajouter_match.php" class="btn-add-match">
                <i class="fas fa-plus-circle"></i> Nouveau Match
            </a>
            <a href="supprimer_match.php" class="btn-add-match" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <i class="fas fa-trash-alt"></i> Supprimer plusieurs
        </a>
        </div>

        <!-- FILTRES -->
        <div class="filters-container">
            <div class="filter-group">
                <label class="filter-label">Filtrer par état</label>
                <select class="filter-select" id="filterEtat">
                    <option value="all">Tous les états</option>
                    <option value="A_PREPARER">À préparer</option>
                    <option value="PREPARE">Préparé</option>
                    <option value="JOUE">Joué</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par résultat</label>
                <select class="filter-select" id="filterResultat">
                    <option value="all">Tous les résultats</option>
                    <option value="VICTOIRE">Victoires</option>
                    <option value="DEFAITE">Défaites</option>
                    <option value="NUL">Nuls</option>
                    <option value="null">Non joué</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par date</label>
                <select class="filter-select" id="filterDate">
                    <option value="all">Toutes les dates</option>
                    <option value="future">À venir</option>
                    <option value="past">Passés</option>
                    <option value="month">Ce mois-ci</option>
                </select>
            </div>
            
            <button class="btn-filter" onclick="applyFilters()">
                <i class="fas fa-filter"></i> Appliquer
            </button>
        </div>

        <!-- STATISTIQUES -->
        <?php
        // Calcul des statistiques
        $total_matchs = count($matchs);
        $matchs_joues = array_filter($matchs, fn($m) => $m['etat'] === 'JOUE');
        $victoires = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'VICTOIRE');
        $nuls = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'NUL');
        $defaites = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'DEFAITE');
        $matchs_a_preparer = array_filter($matchs, fn($m) => $m['etat'] === 'A_PREPARER');
        $moyenne_eval = round(array_reduce($matchs_joues, function($carry, $m) {
            return $carry + ($m['moyenne_eval'] ?: 0);
        }, 0) / max(count($matchs_joues), 1), 1);
        ?>
        
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-number"><?= $total_matchs ?></div>
                <div class="stat-label">Matchs au total</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= count($matchs_a_preparer) ?></div>
                <div class="stat-label">À préparer</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-number"><?= count($victoires) ?></div>
                <div class="stat-label">Victoires</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?= $moyenne_eval ?></div>
                <div class="stat-label">Moy. évaluation</div>
            </div>
        </div>

        <!-- LISTE DES MATCHS -->
        <div class="matches-grid" id="matchesList">
            <?php foreach ($matchs as $m): 
                $dateMatch = strtotime($m["date_heure"]);
                $now = time();
                $matchAVenir = $dateMatch > $now;
                $compositionComplete = ($m["nb_joueurs"] >= 11);
                $score = ($m['score_equipe'] !== null && $m['score_adverse'] !== null) ? 
                    $m['score_equipe'] . ' - ' . $m['score_adverse'] : ' - ';
            ?>
                <div class="match-card" 
                     data-etat="<?= $m['etat'] ?>" 
                     data-resultat="<?= $m['resultat'] ? 'null' : $m['resultat'] ?>"
                     data-date="<?= $dateMatch ?>">
                    
                    <!-- EN-TÊTE DU MATCH -->
                    <div class="match-header">
                        <div class="match-date">
                            <div class="match-day"><?= date("d", $dateMatch) ?></div>
                            <div class="match-month"><?= date("M", $dateMatch) ?></div>
                        </div>
                        <div class="match-time">
                            <i class="fas fa-clock"></i> <?= date("H:i", $dateMatch) ?>
                        </div>
                    </div>
                    
                    <!-- CORPS DU MATCH -->
                    <div class="match-body">
                        <!-- ÉQUIPES ET SCORE -->
                        <div class="match-teams">
                            <div class="team-home">
                                <div class="team-logo">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="team-name">
                                    <?= $m['lieu'] === 'DOMICILE' ? 'Notre équipe' : htmlspecialchars($m['adversaire']) ?>
                                </div>
                            </div>
                            
                            <div class="match-score">
                                <?php if ($m['etat'] === 'JOUE'): ?>
                                    <div class="score"><?= $score ?></div>
                                    <span class="match-status <?= $m['resultat'] ?>">
                                        <?= $m['resultat'] ?>
                                    </span>
                                <?php else: ?>
                                    <div class="score">-</div>
                                    <span class="match-status <?= $m['etat'] ?>">
                                        <?= str_replace("_", " ", $m['etat']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="team-away">
                                <div class="team-logo">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="team-name">
                                    <?= $m['lieu'] === 'EXTERIEUR' ? 'Notre équipe' : htmlspecialchars($m['adversaire']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DÉTAILS -->
                        <div class="match-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="detail-label">Lieu</div>
                                    <div class="detail-value"><?= $m['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?></div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <div class="detail-label">Joueurs</div>
                                    <div class="detail-value"><?= $m['nb_joueurs'] ?>/11</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-chart-line"></i>
                                <div>
                                    <div class="detail-label">État</div>
                                    <span class="etat-badge <?= $m['etat'] ?>">
                                        <i class="fas fa-<?= $m['etat'] === 'A_PREPARER' ? 'clock' : ($m['etat'] === 'PREPARE' ? 'check-circle' : 'play-circle') ?>"></i>
                                        <?= str_replace("_", " ", $m['etat']) ?>
                                    </span>
                                </div>
                            </div>
                            
                            <?php if ($m['etat'] === 'JOUE' && $m['moyenne_eval']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-star"></i>
                                    <div>
                                        <div class="detail-label">Évaluation</div>
                                        <div class="evaluation-stars">
                                            <?php 
                                            $note = round($m['moyenne_eval']);
                                            for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?= $i <= $note ? 'filled' : '' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="evaluation-text">(<?= number_format($m['moyenne_eval'], 1) ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ACTIONS -->
<div class="match-actions">
    <!-- BOUTON FEUILLE DE MATCH (TOUJOURS DISPONIBLE) -->
    <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>" 
       class="btn-action btn-feuille">
        <i class="fas fa-clipboard-list"></i> Feuille de Match
    </a>
    
    <?php if ($matchAVenir): ?>
        <?php if (!$compositionComplete): ?>
            <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>" 
               class="btn-action btn-compose">
                <i class="fas fa-futbol"></i> Composer
            </a>
        <?php else: ?>
            <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>" 
               class="btn-action btn-modify">
                <i class="fas fa-edit"></i> Modifier
            </a>
        <?php endif; ?>
    <?php else: ?>
        <a href="../feuille_match/voir_composition.php?id_match=<?= $m["id_match"] ?>" 
           class="btn-action btn-view">
            <i class="fas fa-eye"></i> Voir
        </a>
        
        <?php if ($m['etat'] === 'JOUE'): ?>
            <a href="../feuille_match/evaluation.php?id_match=<?= $m["id_match"] ?>" 
               class="btn-action btn-eval">
                <i class="fas fa-star"></i> Évaluer
            </a>
        <?php endif; ?>
    <?php endif; ?>
    
    <a href="modifier_match.php?id_match=<?= $m["id_match"] ?>" 
       class="btn-action btn-edit">
        <i class="fas fa-cog"></i> Modifier
    </a>
    
    <a href="supprimer_match.php?id=<?= $m['id_match'] ?>" 
       class="btn-action btn-delete"
       onclick="return confirm('Voulez-vous vraiment supprimer ce match ?')">
       <i class="fas fa-trash"></i> Supprimer
    </a>
</div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- WORKFLOW -->
        <div class="workflow-container">
            <div class="workflow-title">
                <i class="fas fa-project-diagram"></i>
                <h2>Workflow de Gestion des Matchs</h2>
            </div>
            
            <div class="workflow-steps">
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h3 class="step-title">1. Planification</h3>
                    <p class="step-desc">Ajouter un nouveau match dans le calendrier</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <h3 class="step-title">2. Composition</h3>
                    <p class="step-desc">Sélectionner les titulaires et remplaçants</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <h3 class="step-title">3. Match</h3>
                    <p class="step-desc">Le match se joue, résultat enregistré</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="step-title">4. Évaluation</h3>
                    <p class="step-desc">Noter les performances des joueurs</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="step-title">5. Analyse</h3>
                    <p class="step-desc">Statistiques et analyses post-match</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    // =============================
    // FILTRES
    // =============================
    function applyFilters() {
        const filterEtat = document.getElementById('filterEtat').value;
        const filterResultat = document.getElementById('filterResultat').value;
        const filterDate = document.getElementById('filterDate').value;
        const now = Math.floor(Date.now() / 1000);
        
        const matchCards = document.querySelectorAll('.match-card');
        
        matchCards.forEach(card => {
            let show = true;
            const etat = card.dataset.etat;
            const resultat = card.dataset.resultat;
            const date = parseInt(card.dataset.date);
            
            // Filtre par état
            if (filterEtat !== 'all' && etat !== filterEtat) {
                show = false;
            }
            
            // Filtre par résultat
            if (filterResultat !== 'all') {
                if (filterResultat === 'null' && resultat !== 'null') {
                    show = false;
                } else if (filterResultat !== 'null' && resultat !== filterResultat) {
                    show = false;
                }
            }
            
            // Filtre par date
            if (filterDate !== 'all') {
                const isFuture = date > now;
                const isPast = date <= now;
                const isThisMonth = new Date(date * 1000).getMonth() === new Date().getMonth();
                
                switch(filterDate) {
                    case 'future':
                        if (!isFuture) show = false;
                        break;
                    case 'past':
                        if (!isPast) show = false;
                        break;
                    case 'month':
                        if (!isThisMonth) show = false;
                        break;
                }
            }
            
            // Afficher ou cacher la carte
            if (show) {
                card.style.display = 'block';
                card.style.animation = 'fadeInUp 0.5s ease forwards';
            } else {
                card.style.display = 'none';
            }
        });
    }
    
    // =============================
    // TRI PAR DEFAUT
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        // Trier les matchs par date (plus récent en premier)
        const matchesList = document.getElementById('matchesList');
        const matchCards = Array.from(document.querySelectorAll('.match-card'));
        
        matchCards.sort((a, b) => {
            return parseInt(b.dataset.date) - parseInt(a.dataset.date);
        });
        
        // Réorganiser dans le DOM
        matchCards.forEach(card => {
            matchesList.appendChild(card);
        });
        
        // Ajouter des événements aux filtres
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', applyFilters);
        });
    });
    
    // =============================
    // ANIMATIONS AU SCROLL
    // =============================
    function checkScrollAnimations() {
        const matchCards = document.querySelectorAll('.match-card');
        
        matchCards.forEach(card => {
            const cardTop = card.getBoundingClientRect().top;
            const windowHeight = window.innerHeight;
            
            if (cardTop < windowHeight - 100) {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }
        });
    }
    
    window.addEventListener('scroll', checkScrollAnimations);
    window.addEventListener('load', checkScrollAnimations);
    </script>
</body>
</html>
<?php include "../includes/footer.php"; ?>