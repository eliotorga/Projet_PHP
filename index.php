<?php
require_once "includes/auth_check.php";
require_once "includes/config.php";

/* =====================
   DONNÉES DASHBOARD COMPLET
===================== */

/* STATISTIQUES PRINCIPALES */
// Joueurs actifs
$nbJoueursActifs = $gestion_sportive->query("
    SELECT COUNT(*)
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE s.code = 'ACT'
")->fetchColumn();

// Matchs par statut
$stats_matchs = $gestion_sportive->query("
    SELECT 
        SUM(CASE WHEN etat IN ('A_PREPARER', 'PREPARE') THEN 1 ELSE 0 END) as a_venir,
        SUM(CASE WHEN etat = 'JOUE' THEN 1 ELSE 0 END) as joues,
        COUNT(*) as total
    FROM matchs
")->fetch(PDO::FETCH_ASSOC);

// Statistiques de victoires
$stats_victoires = $gestion_sportive->query("
    SELECT 
        SUM(CASE WHEN resultat = 'VICTOIRE' THEN 1 ELSE 0 END) as victoires,
        SUM(CASE WHEN resultat = 'NUL' THEN 1 ELSE 0 END) as nuls,
        SUM(CASE WHEN resultat = 'DEFAITE' THEN 1 ELSE 0 END) as defaites,
        COUNT(*) as total
    FROM matchs
    WHERE resultat IS NOT NULL
")->fetch(PDO::FETCH_ASSOC);

// Performance moyenne des joueurs
$performance_moyenne = $gestion_sportive->query("
    SELECT ROUND(AVG(evaluation), 1) as moyenne
    FROM participation
    WHERE evaluation IS NOT NULL
")->fetchColumn();

// Joueurs blessés/suspendus
$joueurs_indisponibles = $gestion_sportive->query("
    SELECT COUNT(*)
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE s.code IN ('BLE', 'SUS')
")->fetchColumn();

// Dernières activités
$dernieres_activites = $gestion_sportive->query("
    SELECT 
        m.date_heure,
        m.adversaire,
        m.resultat,
        m.lieu,
        COUNT(p.id_joueur) as nb_participants
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE m.etat = 'JOUE'
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
    LIMIT 3
")->fetchAll(PDO::FETCH_ASSOC);

/* PROCHAIN MATCH */
$prochainMatch = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        COUNT(p.id_joueur) as nb_joueurs
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE m.etat IN ('A_PREPARER', 'PREPARE')
    GROUP BY m.id_match
    ORDER BY m.date_heure ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

/* DERNIER MATCH */
$dernierMatch = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.resultat,
        m.score_equipe,
        m.score_adverse,
        ROUND(AVG(p.evaluation), 1) as moyenne_eval
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE m.resultat IS NOT NULL
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

/* MEILLEURS JOUEURS */
$meilleurs_joueurs = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        ROUND(AVG(p.evaluation), 1) as moyenne,
        COUNT(p.id_match) as nb_matchs
    FROM joueur j
    JOIN participation p ON p.id_joueur = j.id_joueur
    WHERE p.evaluation IS NOT NULL
    GROUP BY j.id_joueur
    ORDER BY moyenne DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

include "includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Équipe</title>
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
        background: linear-gradient(135deg, #0c2918 0%, #1a3a26 100%);
        color: white;
        min-height: 100vh;
    }

    /* =============================
       LAYOUT PRINCIPAL
    ============================= */
    .dashboard-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* =============================
       HERO SECTION
    ============================= */
    .hero-section {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius);
        padding: 40px;
        margin-bottom: 40px;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    .hero-section::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 40px 40px;
        opacity: 0.2;
        z-index: 0;
    }

    .hero-content {
        position: relative;
        z-index: 1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 30px;
    }

    .hero-text h1 {
        font-size: 3rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .hero-text p {
        font-size: 1.2rem;
        opacity: 0.9;
        max-width: 600px;
    }

    .hero-stats {
        display: flex;
        gap: 20px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        padding: 20px;
        border-radius: var(--radius);
        min-width: 300px;
    }

    .hero-stat {
        text-align: center;
        flex: 1;
    }

    .hero-stat-number {
        font-size: 2.5rem;
        font-weight: 700;
        color: var(--secondary);
        display: block;
        line-height: 1;
    }

    .hero-stat-label {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-top: 5px;
    }

    /* =============================
       GRID PRINCIPAL
    ============================= */
    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 30px;
        margin-bottom: 40px;
    }

    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
    }

    /* =============================
       CARTES DE STATISTIQUES
    ============================= */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: rgba(30, 40, 35, 0.85);
        backdrop-filter: blur(10px);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        border-color: var(--secondary);
    }

    .stat-card::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary), var(--primary));
    }

    .stat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
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
        font-size: 1.5rem;
    }

    .stat-number {
        font-size: 2.8rem;
        font-weight: 700;
        line-height: 1;
    }

    .stat-label {
        font-size: 1rem;
        opacity: 0.8;
        margin-top: 10px;
    }

    /* =============================
       CARTES D'ACTION PRINCIPALES
    ============================= */
    .action-card-large {
        background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(39, 174, 96, 0.05));
        border: 2px solid rgba(46, 204, 113, 0.3);
        border-radius: var(--radius);
        padding: 30px;
        margin-bottom: 30px;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }

    .action-card-large:hover {
        border-color: var(--secondary);
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(46, 204, 113, 0.2);
    }

    .action-card-large::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 100%;
        background: linear-gradient(135deg, transparent, rgba(46, 204, 113, 0.1));
        z-index: 0;
    }

    .action-content {
        position: relative;
        z-index: 1;
    }

    .action-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
    }

    .action-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3);
    }

    .action-title {
        font-size: 1.8rem;
        font-weight: 700;
    }

    .action-details {
        margin-bottom: 25px;
    }

    .match-info {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
    }

    .match-info i {
        color: var(--secondary);
        font-size: 1.2rem;
    }

    .btn-action {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        padding: 16px 32px;
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        transition: var(--transition);
        border: none;
        cursor: pointer;
        box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3);
    }

    .btn-action:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(46, 204, 113, 0.4);
    }

    .btn-action.btn-warning {
        background: linear-gradient(135deg, var(--warning), #e67e22);
        box-shadow: 0 10px 20px rgba(230, 126, 34, 0.3);
    }

    .btn-action.btn-warning:hover {
        box-shadow: 0 15px 30px rgba(230, 126, 34, 0.4);
    }

    /* =============================
       MEILLEURS JOUEURS
    ============================= */
    .players-card {
        background: rgba(30, 40, 35, 0.85);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .players-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(255,255,255,0.1);
    }

    .players-header i {
        color: var(--accent);
        font-size: 1.8rem;
    }

    .players-header h3 {
        font-size: 1.5rem;
    }

    .player-ranking {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .player-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        transition: var(--transition);
    }

    .player-item:hover {
        background: rgba(255,255,255,0.1);
        transform: translateX(5px);
    }

    .player-rank {
        width: 35px;
        height: 35px;
        background: linear-gradient(135deg, var(--accent), #e67e22);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
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
        gap: 15px;
        font-size: 0.9rem;
        opacity: 0.8;
    }

    .player-rating {
        color: var(--accent);
        font-weight: 700;
    }

    /* =============================
       ACTIVITÉS RÉCENTES
    ============================= */
    .activities-card {
        background: rgba(30, 40, 35, 0.85);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .activities-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(255,255,255,0.1);
    }

    .activities-header i {
        color: var(--info);
        font-size: 1.8rem;
    }

    .activities-header h3 {
        font-size: 1.5rem;
    }

    .activity-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .activity-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
    }

    .activity-icon {
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--info), #3498db);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .activity-details {
        flex: 1;
    }

    .activity-title {
        font-weight: 600;
        margin-bottom: 5px;
    }

    .activity-meta {
        display: flex;
        gap: 15px;
        font-size: 0.85rem;
        opacity: 0.8;
    }

    /* =============================
       RACCOURCIS RAPIDES
    ============================= */
    .quick-actions {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-top: 40px;
    }

    .quick-action-card {
        background: rgba(30, 40, 35, 0.85);
        border-radius: var(--radius);
        padding: 25px;
        text-align: center;
        transition: var(--transition);
        border: 1px solid transparent;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 15px;
    }

    .quick-action-card:hover {
        border-color: var(--secondary);
        transform: translateY(-5px);
    }

    .quick-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        margin-bottom: 10px;
    }

    .quick-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 5px;
    }

    .quick-desc {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-bottom: 15px;
    }

    .btn-quick {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 20px;
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
        text-decoration: none;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        transition: var(--transition);
        width: fit-content;
    }

    .btn-quick:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(46, 204, 113, 0.3);
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

    .stat-card, .action-card-large, .players-card, .activities-card, .quick-action-card {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .hero-content {
            flex-direction: column;
            text-align: center;
        }
        
        .hero-text h1 {
            font-size: 2.2rem;
            justify-content: center;
        }
        
        .hero-stats {
            width: 100%;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
        
        .quick-actions {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Entraîneur</h1>
                    <p>Pilotez votre équipe avec précision : matchs, joueurs, performances et statistiques en temps réel.</p>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $nbJoueursActifs ?></span>
                        <span class="hero-stat-label">Joueurs Actifs</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats_matchs['joues'] ?? 0 ?></span>
                        <span class="hero-stat-label">Matchs Joués</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats_victoires['victoires'] ?? 0 ?></span>
                        <span class="hero-stat-label">Victoires</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRID PRINCIPAL -->
        <div class="dashboard-grid">
            <!-- COLONNE GAUCHE -->
            <div class="left-column">
                <!-- STATISTIQUES RAPIDES -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?= $joueurs_indisponibles ?></div>
                        </div>
                        <div class="stat-label">Joueurs indisponibles</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-number">
                                <?= $stats_victoires['total'] > 0 ? 
                                    round(($stats_victoires['victoires'] / $stats_victoires['total']) * 100) : 0 ?>%
                            </div>
                        </div>
                        <div class="stat-label">Taux de victoire</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-number"><?= $performance_moyenne ?: '0.0' ?></div>
                        </div>
                        <div class="stat-label">Performance moyenne</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-number"><?= $stats_matchs['a_venir'] ?? 0 ?></div>
                        </div>
                        <div class="stat-label">Matchs à venir</div>
                    </div>
                </div>

                <!-- PROCHAIN MATCH -->
                <div class="action-card-large">
                    <div class="action-content">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="action-title">Prochain Match</div>
                                <div style="opacity: 0.8; font-size: 0.9rem;">Préparez votre équipe</div>
                            </div>
                        </div>
                        
                        <?php if ($prochainMatch): ?>
                            <div class="action-details">
                                <div class="match-info">
                                    <i class="fas fa-flag"></i>
                                    <div>
                                        <div style="font-weight: 600; font-size: 1.3rem;"><?= htmlspecialchars($prochainMatch["adversaire"]) ?></div>
                                        <div style="opacity: 0.8;"><?= $prochainMatch['lieu'] === 'DOMICILE' ? 'Match à domicile' : 'Match à l\'extérieur' ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <div style="font-weight: 600;"><?= date("l d F Y", strtotime($prochainMatch["date_heure"])) ?></div>
                                        <div style="opacity: 0.8;">À <?= date("H:i", strtotime($prochainMatch["date_heure"])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <div style="font-weight: 600;"><?= $prochainMatch['nb_joueurs'] ?> joueurs sélectionnés</div>
                                        <div style="opacity: 0.8;"><?= 11 - $prochainMatch['nb_joueurs'] ?> places restantes</div>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="feuille_match/composition.php?id_match=<?= $prochainMatch["id_match"] ?>" 
                               class="btn-action">
                                <i class="fas fa-futbol"></i> Composer l'équipe
                            </a>
                        <?php else: ?>
                            <div class="action-details" style="text-align: center; padding: 30px 0;">
                                <i class="fas fa-calendar-plus" style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;"></i>
                                <div style="font-size: 1.2rem; margin-bottom: 10px;">Aucun match à venir</div>
                                <p style="opacity: 0.8; margin-bottom: 20px;">Planifiez un nouveau match pour commencer la préparation.</p>
                                <a href="matchs/ajouter_match.php" class="btn-action">
                                    <i class="fas fa-plus-circle"></i> Ajouter un match
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DERNIER MATCH -->
                <div class="action-card-large">
                    <div class="action-content">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <div class="action-title">Dernier Match</div>
                                <div style="opacity: 0.8; font-size: 0.9rem;">Analyse et évaluation</div>
                            </div>
                        </div>
                        
                        <?php if ($dernierMatch): ?>
                            <div class="action-details">
                                <div class="match-info">
                                    <i class="fas fa-flag"></i>
                                    <div>
                                        <div style="font-weight: 600; font-size: 1.3rem;"><?= htmlspecialchars($dernierMatch["adversaire"]) ?></div>
                                        <div style="opacity: 0.8;"><?= date("d/m/Y H:i", strtotime($dernierMatch["date_heure"])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-chart-line"></i>
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600;">Résultat : 
                                            <span style="color: <?= $dernierMatch['resultat'] === 'VICTOIRE' ? 'var(--secondary)' : 
                                                                ($dernierMatch['resultat'] === 'NUL' ? 'var(--accent)' : 'var(--danger)') ?>">
                                                <?= $dernierMatch['resultat'] ?>
                                            </span>
                                        </div>
                                        <?php if ($dernierMatch['score_equipe'] !== null): ?>
                                            <div style="opacity: 0.8;">Score : <?= $dernierMatch['score_equipe'] ?> - <?= $dernierMatch['score_adverse'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($dernierMatch['moyenne_eval']): ?>
                                    <div class="match-info">
                                        <i class="fas fa-star"></i>
                                        <div>
                                            <div style="font-weight: 600;">Évaluation moyenne : <?= $dernierMatch['moyenne_eval'] ?>/5</div>
                                            <div style="opacity: 0.8;">Performance de l'équipe</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                                <a href="feuille_match/evaluation.php?id_match=<?= $dernierMatch["id_match"] ?>" 
                                   class="btn-action btn-warning">
                                    <i class="fas fa-star"></i> Évaluer les joueurs
                                </a>
                                <a href="feuille_match/voir_composition.php?id_match=<?= $dernierMatch["id_match"] ?>" 
                                   class="btn-action">
                                    <i class="fas fa-eye"></i> Voir la composition
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="action-details" style="text-align: center; padding: 30px 0;">
                                <i class="fas fa-futbol" style="font-size: 4rem; opacity: 0.3; margin-bottom: 20px;"></i>
                                <div style="font-size: 1.2rem; margin-bottom: 10px;">Aucun match joué</div>
                                <p style="opacity: 0.8;">Les statistiques s'afficheront après votre premier match.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE -->
            <div class="right-column">
                <!-- MEILLEURS JOUEURS -->
                <div class="players-card">
                    <div class="players-header">
                        <i class="fas fa-crown"></i>
                        <h3>Top Performers</h3>
                    </div>
                    
                    <div class="player-ranking">
                        <?php if (!empty($meilleurs_joueurs)): ?>
                            <?php foreach ($meilleurs_joueurs as $index => $joueur): ?>
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
                                        </div>
                                    </div>
                                    <a href="joueurs/fiche_joueur.php?id=<?= $joueur['id_joueur'] ?>" 
                                       style="color: var(--secondary);">
                                        <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 30px 0; opacity: 0.6;">
                                <i class="fas fa-user-slash" style="font-size: 3rem; margin-bottom: 15px;"></i>
                                <div>Aucune donnée d'évaluation disponible</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- ACTIVITÉS RÉCENTES -->
                <div class="activities-card" style="margin-top: 30px;">
                    <div class="activities-header">
                        <i class="fas fa-history"></i>
                        <h3>Activités Récentes</h3>
                    </div>
                    
                    <div class="activity-list">
                        <?php if (!empty($dernieres_activites)): ?>
                            <?php foreach ($dernieres_activites as $activite): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-futbol"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title"><?= htmlspecialchars($activite['adversaire']) ?></div>
                                        <div class="activity-meta">
                                            <span><?= date("d/m/Y", strtotime($activite['date_heure'])) ?></span>
                                            <span style="color: <?= $activite['resultat'] === 'VICTOIRE' ? 'var(--secondary)' : 
                                                                ($activite['resultat'] === 'NUL' ? 'var(--accent)' : 'var(--danger)') ?>">
                                                <?= $activite['resultat'] ?>
                                            </span>
                                            <span><?= $activite['nb_participants'] ?> joueurs</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div style="text-align: center; padding: 20px 0; opacity: 0.6;">
                                <i class="fas fa-calendar-times" style="font-size: 2rem; margin-bottom: 10px;"></i>
                                <div>Aucune activité récente</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RACCOURCIS RAPIDES -->
        <div class="quick-actions">
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="quick-title">Effectif</div>
                <div class="quick-desc">Gérez votre liste de joueurs et leurs statuts</div>
                <a href="joueurs/liste_joueurs.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="quick-title">Calendrier</div>
                <div class="quick-desc">Planifiez et gérez tous vos matchs</div>
                <a href="matchs/liste_matchs.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="quick-title">Statistiques</div>
                <div class="quick-desc">Analyses détaillées et performances</div>
                <a href="stats/stats_equipe.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="quick-title">Compositions</div>
                <div class="quick-desc">Historique des compositions d'équipe</div>
                <a href="feuille_match/historique_compositions.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
        </div>
    </div>

    <script>
    // =============================
    // ANIMATIONS ET INTERACTIVITÉ
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des cartes au scroll
        function animateOnScroll() {
            const cards = document.querySelectorAll('.stat-card, .action-card-large, .quick-action-card');
            
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (cardTop < windowHeight - 100) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Initial call
        
        // Mise à jour en temps réel (simulée)
        function updateStats() {
            // Ici vous pourriez ajouter une requête AJAX pour des stats en temps réel
            const statNumbers = document.querySelectorAll('.stat-number');
            
            statNumbers.forEach(number => {
                const original = parseInt(number.textContent);
                if (!isNaN(original)) {
                    // Animation visuelle
                    number.style.transform = 'scale(1.1)';
                    setTimeout(() => {
                        number.style.transform = 'scale(1)';
                    }, 300);
                }
            });
        }
        
        // Mettre à jour toutes les 30 secondes (simulation)
        // setInterval(updateStats, 30000);
        
        // Effets de survol améliorés
        const actionCards = document.querySelectorAll('.action-card-large');
        actionCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 25px 50px rgba(0,0,0,0.25)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'var(--shadow)';
            });
        });
    });
    
    // =============================
    // NOTIFICATIONS (EXEMPLE)
    // =============================
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);
        
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
    }
    
    // Exemple : Si un match est imminent
    <?php if ($prochainMatch && (strtotime($prochainMatch['date_heure']) - time()) < 86400): ?>
        setTimeout(() => {
            showNotification('⚠️ Match imminent demain ! Pensez à finaliser votre composition.', 'warning');
        }, 2000);
    <?php endif; ?>
    </script>
</body>
</html>
<?php include "includes/footer.php"; ?>