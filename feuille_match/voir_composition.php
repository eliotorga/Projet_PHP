<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =============================
   VÉRIFICATION ID MATCH
============================= */
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php?error=no_match");
    exit();
}

$id_match = intval($_GET["id_match"]);

/* =============================
   INFOS COMPLÈTES DU MATCH
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT 
        m.*,
        COUNT(DISTINCT p.id_joueur) as nb_joueurs,
        ROUND(AVG(p.evaluation), 1) as moyenne_eval
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE m.id_match = ?
    GROUP BY m.id_match
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("<div class='error-container'><h2>⚽ Match introuvable</h2><p>Le match sélectionné n'existe pas.</p></div>");
}

/* =============================
   RÉCUPÉRATION COMPOSITION COMPLÈTE
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        j.num_licence,
        p.evaluation,
        p.role,
        po.code AS poste_code,
        po.libelle AS poste_libelle,
        s.libelle AS statut
    FROM participation p
    JOIN joueur j ON j.id_joueur = p.id_joueur
    JOIN poste po ON po.id_poste = p.id_poste
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE p.id_match = ?
    ORDER BY 
        FIELD(p.role, 'TITULAIRE', 'REMPLACANT') DESC,
        FIELD(po.code, 'GAR', 'DEF', 'MIL', 'ATT'),
        j.nom
");
$stmt->execute([$id_match]);
$participations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Séparation titulaires/remplaçants
$titulaires = array_filter($participations, fn($p) => $p['role'] === 'TITULAIRE');
$remplacants = array_filter($participations, fn($p) => $p['role'] === 'REMPLACANT');

// Classement par poste selon l'ordre footballistique
$groupes = [
    'GAR' => [],
    'DEF' => [],
    'MIL' => [], 
    'ATT' => []
];

foreach ($titulaires as $joueur) {
    if (isset($groupes[$joueur['poste_code']])) {
        $groupes[$joueur['poste_code']][] = $joueur;
    }
}

/* =============================
   STATISTIQUES DU MATCH
============================= */
$stats = $gestion_sportive->prepare("
    SELECT 
        COUNT(*) as total_joueurs,
        ROUND(AVG(evaluation), 1) as moyenne_generale,
        MIN(evaluation) as note_min,
        MAX(evaluation) as note_max,
        SUM(CASE WHEN evaluation >= 4 THEN 1 ELSE 0 END) as excellent,
        SUM(CASE WHEN evaluation = 3 THEN 1 ELSE 0 END) as moyen,
        SUM(CASE WHEN evaluation <= 2 THEN 1 ELSE 0 END) as faible
    FROM participation
    WHERE id_match = ? AND evaluation IS NOT NULL
");
$stats->execute([$id_match]);
$statistiques = $stats->fetch(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feuille de Match - <?= htmlspecialchars($match["adversaire"]) ?></title>
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
        --info: #3498db;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.25);
        --radius: 20px;
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
        padding-bottom: 50px;
    }

    .error-container {
        max-width: 600px;
        margin: 100px auto;
        text-align: center;
        background: rgba(231, 76, 60, 0.1);
        border: 2px solid var(--danger);
        border-radius: var(--radius);
        padding: 40px;
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
       HEADER DU MATCH
    ============================= */
    .match-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius);
        padding: 40px;
        margin-bottom: 40px;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    .match-header::before {
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

    .header-content {
        position: relative;
        z-index: 1;
    }

    .header-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .header-title h1 {
        font-size: 2.8rem;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .match-score {
        display: flex;
        align-items: center;
        gap: 20px;
        background: rgba(255,255,255,0.15);
        padding: 15px 30px;
        border-radius: 50px;
        backdrop-filter: blur(10px);
    }

    .score-number {
        font-size: 3.5rem;
        font-weight: 800;
        line-height: 1;
    }

    .score-divider {
        font-size: 2.5rem;
        opacity: 0.5;
    }

    .match-details {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 20px;
        background: rgba(255,255,255,0.1);
        border-radius: 16px;
        backdrop-filter: blur(10px);
        transition: var(--transition);
    }

    .detail-item:hover {
        background: rgba(255,255,255,0.15);
        transform: translateY(-3px);
    }

    .detail-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }

    .detail-text {
        flex: 1;
    }

    .detail-label {
        font-size: 0.9rem;
        opacity: 0.8;
        margin-bottom: 5px;
    }

    .detail-value {
        font-size: 1.3rem;
        font-weight: 600;
    }

    /* =============================
       BADGE RÉSULTAT
    ============================= */
    .result-badge {
        padding: 12px 25px;
        border-radius: 50px;
        font-size: 1.1rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
    }

    .VICTOIRE { background: linear-gradient(135deg, #2ecc71, #27ae60); }
    .DEFAITE { background: linear-gradient(135deg, #e74c3c, #c0392b); }
    .NUL { background: linear-gradient(135deg, #f39c12, #e67e22); }

    /* =============================
       STATISTIQUES DU MATCH
    ============================= */
    .stats-section {
        background: rgba(30, 40, 35, 0.85);
        backdrop-filter: blur(10px);
        border-radius: var(--radius);
        padding: 30px;
        margin-bottom: 40px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .stats-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        font-size: 1.8rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .stat-card {
        text-align: center;
        padding: 25px;
        background: rgba(255,255,255,0.05);
        border-radius: 16px;
        transition: var(--transition);
    }

    .stat-card:hover {
        background: rgba(255,255,255,0.1);
        transform: translateY(-5px);
    }

    .stat-number {
        font-size: 3rem;
        font-weight: 800;
        color: var(--secondary);
        margin: 10px 0;
        line-height: 1;
    }

    .stat-label {
        font-size: 1rem;
        opacity: 0.8;
    }

    /* =============================
       TERRAIN DE FOOT
    ============================= */
    .pitch-section {
        background: rgba(30, 40, 35, 0.85);
        backdrop-filter: blur(10px);
        border-radius: var(--radius);
        padding: 35px;
        margin-bottom: 40px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
        position: relative;
        overflow: hidden;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        font-size: 1.8rem;
    }

    .pitch-container {
        position: relative;
        background: linear-gradient(135deg, #1a7a3f 0%, #0f5a2f 100%);
        border-radius: var(--radius);
        min-height: 700px;
        padding: 40px 30px;
        border: 6px solid rgba(255,255,255,0.2);
        box-shadow: inset 0 0 60px rgba(0,0,0,0.5);
        overflow: hidden;
    }

    /* Lignes du terrain */
    .pitch-container::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(255,255,255,0.3);
        transform: translateY(-50%);
        z-index: 1;
    }

    .pitch-container::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 180px;
        height: 180px;
        border: 4px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
        z-index: 1;
    }

    /* Zone de but */
    .goal-area {
        position: absolute;
        top: 50%;
        left: 20px;
        right: 20px;
        height: 200px;
        border: 4px solid rgba(255,255,255,0.3);
        border-radius: 8px;
        transform: translateY(-50%);
        z-index: 1;
    }

    .goal-area.left { right: auto; width: 80px; }
    .goal-area.right { left: auto; width: 80px; }

    /* Formation lines */
    .formation-line {
        display: flex;
        justify-content: center;
        gap: 30px;
        margin-bottom: 45px;
        position: relative;
        z-index: 2;
    }

    .formation-label {
        position: absolute;
        left: 20px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(0,0,0,0.5);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255,255,255,0.8);
    }

    /* Cartes joueurs */
    .player-card {
        width: 200px;
        min-height: 140px;
        background: linear-gradient(135deg, rgba(0,0,0,0.6), rgba(0,0,0,0.4));
        border-radius: 16px;
        padding: 20px;
        text-align: center;
        border: 2px solid rgba(255,255,255,0.2);
        transition: var(--transition);
        position: relative;
        backdrop-filter: blur(5px);
    }

    .player-card:hover {
        border-color: var(--secondary);
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }

    .player-card.titulaire {
        border-color: var(--secondary);
        box-shadow: 0 10px 25px rgba(46, 204, 113, 0.3);
    }

    .player-name {
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: white;
    }

    .player-position {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.7);
        margin-bottom: 10px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .player-license {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.5);
        font-family: 'Courier New', monospace;
        margin-bottom: 12px;
    }

    /* Évaluation par étoiles */
    .rating-stars {
        display: flex;
        justify-content: center;
        gap: 3px;
        margin: 12px 0;
    }

    .star {
        color: rgba(255,255,255,0.3);
        font-size: 1.1rem;
    }

    .star.filled {
        color: var(--accent);
        text-shadow: 0 0 10px rgba(243, 156, 18, 0.5);
    }

    .rating-text {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.7);
        margin-top: 5px;
    }

    .player-evaluation {
        position: absolute;
        top: -10px;
        right: -10px;
        width: 40px;
        height: 40px;
        background: linear-gradient(135deg, var(--accent), #e67e22);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }

    /* =============================
       BANC DES REMPLAÇANTS
    ============================= */
    .bench-section {
        background: rgba(30, 40, 35, 0.85);
        backdrop-filter: blur(10px);
        border-radius: var(--radius);
        padding: 35px;
        margin-bottom: 40px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
    }

    .bench-container {
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
        margin-top: 25px;
    }

    .bench-player {
        flex: 1;
        min-width: 250px;
        background: linear-gradient(135deg, rgba(0,0,0,0.4), rgba(0,0,0,0.2));
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        border: 2px solid rgba(243, 156, 18, 0.3);
        transition: var(--transition);
    }

    .bench-player:hover {
        border-color: var(--accent);
        transform: translateY(-5px);
    }

    .bench-avatar {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, var(--accent), #e67e22);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .bench-info {
        flex: 1;
    }

    .bench-name {
        font-weight: 600;
        margin-bottom: 5px;
        font-size: 1.1rem;
    }

    .bench-details {
        display: flex;
        gap: 15px;
        font-size: 0.9rem;
        color: rgba(255,255,255,0.7);
    }

    .bench-evaluation {
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 10px;
        font-weight: 700;
        color: var(--accent);
        font-size: 1.2rem;
    }

    /* =============================
       ACTIONS
    ============================= */
    .actions-section {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 18px 35px;
        font-size: 1.1rem;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition);
        border: none;
        cursor: pointer;
    }

    .btn-back {
        background: linear-gradient(135deg, #34495e, #2c3e50);
        color: white;
    }

    .btn-back:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(52, 73, 94, 0.3);
    }

    .btn-eval {
        background: linear-gradient(135deg, var(--accent), #e67e22);
        color: white;
    }

    .btn-eval:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(243, 156, 18, 0.3);
    }

    .btn-print {
        background: linear-gradient(135deg, #3498db, #2980b9);
        color: white;
    }

    .btn-print:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(52, 152, 219, 0.3);
    }

    /* =============================
       ANIMATIONS
    ============================= */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    .match-header, .stats-section, .pitch-section, .bench-section {
        animation: fadeInUp 0.6s ease forwards;
    }

    .player-card:hover .player-evaluation {
        animation: float 2s ease-in-out infinite;
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 1200px) {
        .formation-line {
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .player-card {
            width: 180px;
        }
    }

    @media (max-width: 768px) {
        .header-title h1 {
            font-size: 2rem;
        }
        
        .match-score {
            padding: 10px 20px;
        }
        
        .score-number {
            font-size: 2.5rem;
        }
        
        .player-card {
            width: 100%;
            max-width: 250px;
            margin: 0 auto;
        }
        
        .formation-line {
            flex-direction: column;
            align-items: center;
        }
        
        .formation-label {
            position: relative;
            left: auto;
            top: auto;
            transform: none;
            margin-bottom: 10px;
            text-align: center;
        }
        
        .actions-section {
            flex-direction: column;
            align-items: center;
        }
        
        .btn-action {
            width: 100%;
            max-width: 300px;
            justify-content: center;
        }
    }
    
    /* =============================
       STYLES D'IMPRESSION
    ============================= */
    @media print {
        body {
            background: white !important;
            color: black !important;
        }
        
        .match-header, .stats-section, .pitch-section, .bench-section {
            box-shadow: none !important;
            border: 2px solid #ddd !important;
            background: white !important;
            color: black !important;
        }
        
        .pitch-container {
            background: #f0f8f0 !important;
            border-color: #ccc !important;
        }
        
        .player-card, .bench-player {
            border: 1px solid #ddd !important;
            background: white !important;
            color: black !important;
            box-shadow: none !important;
        }
        
        .btn-action {
            display: none !important;
        }
        
        .actions-section {
            display: none !important;
        }
    }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- EN-TÊTE DU MATCH -->
        <div class="match-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-clipboard-list"></i> Feuille de Match</h1>
                    <?php if ($match['resultat']): ?>
                        <span class="result-badge <?= $match['resultat'] ?>">
                            <i class="fas fa-<?= $match['resultat'] === 'VICTOIRE' ? 'trophy' : 
                                              ($match['resultat'] === 'DEFAITE' ? 'times' : 'equals') ?>"></i>
                            <?= $match['resultat'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($match['score_equipe'] !== null && $match['score_adverse'] !== null): ?>
                    <div class="match-score">
                        <div class="score-number"><?= $match['score_equipe'] ?></div>
                        <div class="score-divider">-</div>
                        <div class="score-number"><?= $match['score_adverse'] ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="match-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Adversaire</div>
                            <div class="detail-value"><?= htmlspecialchars($match["adversaire"]) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Date et heure</div>
                            <div class="detail-value"><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Lieu</div>
                            <div class="detail-value"><?= $match["lieu"] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Joueurs</div>
                            <div class="detail-value"><?= count($titulaires) + count($remplacants) ?> / <?= $match['nb_joueurs'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES DU MATCH -->
        <?php if ($statistiques['total_joueurs'] > 0): ?>
            <div class="stats-section">
                <h2 class="stats-title"><i class="fas fa-chart-bar"></i> Statistiques du Match</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($statistiques['moyenne_generale'] ?? 0, 1) ?></div>
                        <div class="stat-label">Note moyenne</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['excellent'] ?? 0 ?></div>
                        <div class="stat-label">Performances excellentes (≥ 4/5)</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['moyen'] ?? 0 ?></div>
                        <div class="stat-label">Performances moyennes (3/5)</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['faible'] ?? 0 ?></div>
                        <div class="stat-label">Performances faibles (≤ 2/5)</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TERRAIN AVEC COMPOSITION -->
        <div class="pitch-section">
            <h2 class="section-title"><i class="fas fa-futbol"></i> Composition Titulaire</h2>
            
            <div class="pitch-container">
                <!-- Zone de but -->
                <div class="goal-area left"></div>
                <div class="goal-area right"></div>
                
                <!-- Attaquants -->
                <?php if (!empty($groupes['ATT'])): ?>
                    <div class="formation-line">
                        <span class="formation-label">Attaque</span>
                        <?php foreach ($groupes['ATT'] as $joueur): ?>
                            <div class="player-card titulaire">
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="player-evaluation"><?= $joueur['evaluation'] ?></div>
                                <?php endif; ?>
                                <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                <div class="player-position"><?= htmlspecialchars($joueur['poste_libelle']) ?></div>
                                <div class="player-license"><?= htmlspecialchars($joueur['num_licence']) ?></div>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $joueur['evaluation'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">Note : <?= $joueur['evaluation'] ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-text" style="opacity: 0.6;">Non évalué</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Milieux -->
                <?php if (!empty($groupes['MIL'])): ?>
                    <div class="formation-line">
                        <span class="formation-label">Milieu</span>
                        <?php foreach ($groupes['MIL'] as $joueur): ?>
                            <div class="player-card titulaire">
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="player-evaluation"><?= $joueur['evaluation'] ?></div>
                                <?php endif; ?>
                                <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                <div class="player-position"><?= htmlspecialchars($joueur['poste_libelle']) ?></div>
                                <div class="player-license"><?= htmlspecialchars($joueur['num_licence']) ?></div>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $joueur['evaluation'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">Note : <?= $joueur['evaluation'] ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-text" style="opacity: 0.6;">Non évalué</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Défenseurs -->
                <?php if (!empty($groupes['DEF'])): ?>
                    <div class="formation-line">
                        <span class="formation-label">Défense</span>
                        <?php foreach ($groupes['DEF'] as $joueur): ?>
                            <div class="player-card titulaire">
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="player-evaluation"><?= $joueur['evaluation'] ?></div>
                                <?php endif; ?>
                                <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                <div class="player-position"><?= htmlspecialchars($joueur['poste_libelle']) ?></div>
                                <div class="player-license"><?= htmlspecialchars($joueur['num_licence']) ?></div>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $joueur['evaluation'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">Note : <?= $joueur['evaluation'] ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-text" style="opacity: 0.6;">Non évalué</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Gardiens -->
                <?php if (!empty($groupes['GAR'])): ?>
                    <div class="formation-line">
                        <span class="formation-label">Gardien</span>
                        <?php foreach ($groupes['GAR'] as $joueur): ?>
                            <div class="player-card titulaire">
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="player-evaluation"><?= $joueur['evaluation'] ?></div>
                                <?php endif; ?>
                                <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                <div class="player-position"><?= htmlspecialchars($joueur['poste_libelle']) ?></div>
                                <div class="player-license"><?= htmlspecialchars($joueur['num_licence']) ?></div>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $joueur['evaluation'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">Note : <?= $joueur['evaluation'] ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-text" style="opacity: 0.6;">Non évalué</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- BANC DES REMPLAÇANTS -->
        <?php if (!empty($remplacants)): ?>
            <div class="bench-section">
                <h2 class="section-title"><i class="fas fa-chair"></i> Remplaçants</h2>
                
                <div class="bench-container">
                    <?php foreach ($remplacants as $remplacant): ?>
                        <div class="bench-player">
                            <div class="bench-avatar">
                                <?= strtoupper(substr($remplacant['prenom'], 0, 1) . substr($remplacant['nom'], 0, 1)) ?>
                            </div>
                            <div class="bench-info">
                                <div class="bench-name"><?= htmlspecialchars($remplacant['prenom'] . ' ' . $remplacant['nom']) ?></div>
                                <div class="bench-details">
                                    <span><?= htmlspecialchars($remplacant['poste_libelle']) ?></span>
                                    <span><?= htmlspecialchars($remplacant['num_licence']) ?></span>
                                    <span class="badge" style="background: var(--accent); color: white; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">Remplaçant</span>
                                </div>
                            </div>
                            <?php if ($remplacant['evaluation']): ?>
                                <div class="bench-evaluation">
                                    <i class="fas fa-star"></i> <?= $remplacant['evaluation'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ACTIONS -->
        <div class="actions-section">
            <a href="../matchs/liste_matchs.php" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Retour aux matchs
            </a>
            
            <button onclick="window.print()" class="btn-action btn-print">
                <i class="fas fa-print"></i> Imprimer la feuille
            </button>
            
            <?php if ($match['etat'] === 'JOUE'): ?>
                <a href="evaluation.php?id_match=<?= $id_match ?>" class="btn-action btn-eval">
                    <i class="fas fa-star"></i> Évaluer les joueurs
                </a>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // =============================
    // ANIMATIONS ET INTERACTIVITÉ
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des cartes joueurs au scroll
        function animateCardsOnScroll() {
            const playerCards = document.querySelectorAll('.player-card, .bench-player');
            
            playerCards.forEach((card, index) => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (cardTop < windowHeight - 100) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        }
        
        // Mettre en avant le meilleur joueur
        function highlightBestPlayer() {
            const evaluations = document.querySelectorAll('.player-evaluation');
            if (evaluations.length === 0) return;
            
            let bestScore = 0;
            let bestCard = null;
            
            evaluations.forEach(eval => {
                const score = parseInt(eval.textContent);
                const card = eval.closest('.player-card');
                
                if (score > bestScore) {
                    bestScore = score;
                    bestCard = card;
                }
            });
            
            if (bestCard && bestScore >= 4) {
                bestCard.style.borderColor = 'var(--accent)';
                bestCard.style.boxShadow = '0 0 30px rgba(243, 156, 18, 0.5)';
                bestCard.style.position = 'relative';
                
                const star = document.createElement('div');
                star.innerHTML = '<i class="fas fa-crown" style="color: var(--accent); font-size: 1.5rem;"></i>';
                star.style.position = 'absolute';
                star.style.top = '-15px';
                star.style.left = '50%';
                star.style.transform = 'translateX(-50%)';
                star.style.zIndex = '10';
                bestCard.appendChild(star);
            }
        }
        
        // Gestion des événements
        window.addEventListener('scroll', animateCardsOnScroll);
        
        // Initialisation
        animateCardsOnScroll();
        highlightBestPlayer();
        
        // Effet de survol amélioré pour les cartes
        const playerCards = document.querySelectorAll('.player-card');
        playerCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.zIndex = '100';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.zIndex = '2';
            });
        });
        
        // Notification si match non évalué
        <?php if ($match['etat'] === 'JOUE' && $statistiques['total_joueurs'] == 0): ?>
        setTimeout(() => {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                bottom: 20px;
                right: 20px;
                background: linear-gradient(135deg, var(--accent), #e67e22);
                color: white;
                padding: 15px 25px;
                border-radius: 12px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.3);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 10px;
                animation: slideIn 0.5s ease;
            `;
            
            notification.innerHTML = `
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <div style="font-weight: 600;">Ce match n'a pas encore été évalué</div>
                    <div style="font-size: 0.9rem; opacity: 0.9;">Cliquez sur "Évaluer les joueurs" pour noter les performances</div>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Supprimer après 10 secondes
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.5s ease';
                setTimeout(() => notification.remove(), 500);
            }, 10000);
            
            // Ajouter les animations CSS
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            document.head.appendChild(style);
        }, 2000);
        <?php endif; ?>
    });
    </script>
</body>
</html>
<?php include "../includes/footer.php"; ?>