<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

// =====================
// SUPPRESSION DIRECTE
// =====================
if (isset($_GET['id'])) {
    $id_match = intval($_GET['id']);
    
    try {
        $gestion_sportive->beginTransaction();
        
        // 1. Supprimer les participations
        $stmt = $gestion_sportive->prepare("DELETE FROM participation WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        // 2. Supprimer le match
        $stmt = $gestion_sportive->prepare("DELETE FROM matchs WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        $gestion_sportive->commit();
        
        $_SESSION['success_message'] = "✅ Match supprimé avec succès.";
        header("Location: liste_matchs.php");
        exit;
        
    } catch (Exception $e) {
        $gestion_sportive->rollBack();
        $error = "❌ Erreur lors de la suppression : " . $e->getMessage();
    }
}

// =====================
// SUPPRESSION PAR LOT (code existant)
// =====================
// ... le reste de votre code actuel ...

// Initialiser les messages
$message = "";
$error = "";

// Récupérer tous les matchs avec leurs statistiques
$matchs = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        m.score_equipe,
        m.score_adverse,
        m.resultat,
        m.etat,
        COUNT(DISTINCT p.id_joueur) AS nb_participants,
        ROUND(AVG(p.evaluation), 1) AS note_moyenne
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Suppression directe
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id_match = intval($_GET['id']);
    
    try {
        $gestion_sportive->beginTransaction();
        
        // Supprimer les participations
        $stmt = $gestion_sportive->prepare("DELETE FROM participation WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        // Supprimer le match
        $stmt = $gestion_sportive->prepare("DELETE FROM matchs WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        $gestion_sportive->commit();
        
        $_SESSION['success_message'] = "Match supprimé avec succès.";
        header("Location: liste_matchs.php");
        exit;
        
    } catch (Exception $e) {
        $gestion_sportive->rollBack();
        $error = "Erreur lors de la suppression : " . $e->getMessage();
    }
}

// Traitement de la suppression
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['supprimer_matchs'])) {
    if (!empty($_POST['matchs_selectionnes'])) {
        $ids_matchs = array_map('intval', $_POST['matchs_selectionnes']);
        
        try {
            $gestion_sportive->beginTransaction();
            
            $matchs_supprimes = [];
            $matchs_avec_participations = 0;
            
            foreach ($ids_matchs as $id_match) {
                // Récupérer les infos du match avant suppression
                $stmt = $gestion_sportive->prepare("
                    SELECT adversaire, date_heure 
                    FROM matchs 
                    WHERE id_match = ?
                ");
                $stmt->execute([$id_match]);
                if ($match_info = $stmt->fetch()) {
                    $date_format = date("d/m/Y", strtotime($match_info['date_heure']));
                    $matchs_supprimes[] = $match_info['adversaire'] . " (" . $date_format . ")";
                }
                
                // Compter les participations pour information
                $stmt = $gestion_sportive->prepare("
                    SELECT COUNT(*) 
                    FROM participation 
                    WHERE id_match = ?
                ");
                $stmt->execute([$id_match]);
                $nb_participations = $stmt->fetchColumn();
                if ($nb_participations > 0) {
                    $matchs_avec_participations++;
                }
                
                // Supprimer les participations (CASCADE devrait le faire automatiquement)
                $stmt = $gestion_sportive->prepare("DELETE FROM participation WHERE id_match = ?");
                $stmt->execute([$id_match]);
                
                // Supprimer le match
                $stmt = $gestion_sportive->prepare("DELETE FROM matchs WHERE id_match = ?");
                $stmt->execute([$id_match]);
            }
            
            $gestion_sportive->commit();
            
            if (!empty($matchs_supprimes)) {
                $message = "✅ " . count($matchs_supprimes) . " match(s) supprimé(s) avec succès.";
                if ($matchs_avec_participations > 0) {
                    $message .= "<br><small>(" . $matchs_avec_participations . " match(s) avec évaluations supprimées)</small>";
                }
                $_SESSION['success_message'] = $message;
            }
            
            // Recharger la page pour voir les changements
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } else {
        $error = "Veuillez sélectionner au moins un match à supprimer.";
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer des Matchs</title>
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
        --danger: #e74c3c;
        --danger-dark: #c0392b;
        --warning: #f39c12;
        --info: #3498db;
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

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* =============================
       HEADER
    ============================= */
    .page-header {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        border-radius: var(--radius);
        padding: 30px;
        margin-bottom: 30px;
        color: white;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
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
        align-items: center;
        gap: 15px;
        margin-bottom: 10px;
    }

    .header-title h1 {
        font-size: 2.2rem;
    }

    .header-subtitle {
        opacity: 0.9;
        font-size: 1.1rem;
        line-height: 1.5;
    }

    .warning-box {
        background: rgba(255, 255, 255, 0.2);
        border-left: 4px solid var(--warning);
        padding: 15px;
        margin-top: 20px;
        border-radius: 8px;
        display: flex;
        align-items: flex-start;
        gap: 15px;
    }

    /* =============================
       MESSAGES
    ============================= */
    .message-container {
        margin-bottom: 25px;
    }

    .alert {
        padding: 20px 25px;
        border-radius: 12px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: slideIn 0.5s ease;
    }

    .alert-success {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        color: #2e7d32;
        border-left: 4px solid var(--secondary);
    }

    .alert-error {
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        color: #c62828;
        border-left: 4px solid var(--danger);
    }

    @keyframes slideIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    /* =============================
       CONTROLS BAR
    ============================= */
    .controls-bar {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 25px;
        box-shadow: var(--shadow);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
    }

    .stats-info {
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .matches-count {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark);
    }

    .selection-info {
        font-size: 0.9rem;
        color: var(--gray);
        padding: 5px 12px;
        background: #f8f9fa;
        border-radius: 20px;
        border: 1px solid #e9ecef;
    }

    .selection-info .count {
        font-weight: 700;
        color: var(--danger);
    }

    .controls-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .btn {
        padding: 12px 24px;
        border-radius: 50px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        cursor: pointer;
        border: none;
        font-size: 0.95rem;
    }

    .btn-select-all {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        color: var(--dark);
        border: 2px solid #dee2e6;
    }

    .btn-select-all:hover {
        background: linear-gradient(135deg, #e9ecef, #dee2e6);
        transform: translateY(-2px);
    }

    .btn-deselect-all {
        background: linear-gradient(135deg, #f1f8e9, #dcedc8);
        color: #689f38;
        border: 2px solid #c5e1a5;
    }

    .btn-deselect-all:hover {
        background: linear-gradient(135deg, #dcedc8, #c5e1a5);
        transform: translateY(-2px);
    }

    .btn-delete-selected {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        color: white;
    }

    .btn-delete-selected:hover {
        background: linear-gradient(135deg, var(--danger-dark), #a93226);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(231, 76, 60, 0.3);
    }

    .btn-cancel {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        color: white;
    }

    .btn-cancel:hover {
        background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
        transform: translateY(-2px);
    }

    /* =============================
       TABLE DES MATCHS
    ============================= */
    .matches-table-container {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }

    .table-responsive {
        overflow-x: auto;
    }

    .matches-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 800px;
    }

    .matches-table thead {
        background: linear-gradient(135deg, var(--dark), #34495e);
        color: white;
    }

    .matches-table th {
        padding: 18px 20px;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 3px solid var(--primary);
    }

    .matches-table tbody tr {
        border-bottom: 1px solid #f0f3f8;
        transition: var(--transition);
    }

    .matches-table tbody tr:hover {
        background-color: #f8fafc;
    }

    .matches-table tbody tr.selected {
        background-color: #fff8e1;
    }

    .matches-table td {
        padding: 16px 20px;
        vertical-align: middle;
    }

    .checkbox-cell {
        width: 50px;
        text-align: center;
    }

    .match-checkbox {
        width: 20px;
        height: 20px;
        cursor: pointer;
    }

    .match-date {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .match-time {
        font-size: 0.85rem;
        color: var(--gray);
    }

    .match-adversaire {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark);
        margin-bottom: 5px;
    }

    .match-lieu {
        font-size: 0.85rem;
        color: var(--gray);
        padding: 4px 10px;
        background: #f0f3f8;
        border-radius: 12px;
        display: inline-block;
    }

    .match-lieu.DOMICILE {
        background: #e8f5e9;
        color: #2e7d32;
    }

    .match-lieu.EXTERIEUR {
        background: #e3f2fd;
        color: #1565c0;
    }

    .match-score {
        font-size: 1.3rem;
        font-weight: 700;
        text-align: center;
        padding: 8px 15px;
        border-radius: 8px;
        background: #f8f9fa;
    }

    .resultat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .VICTOIRE { background: #e8f5e9; color: #2e7d32; }
    .DEFAITE { background: #ffebee; color: #c62828; }
    .NUL { background: #fff8e1; color: #f57c00; }

    .etat-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: inline-block;
    }

    .A_PREPARER { background: #e3f2fd; color: #1565c0; }
    .PREPARE { background: #f3e5f5; color: #7b1fa2; }
    .JOUE { background: #e8f5e9; color: #2e7d32; }

    .match-stats {
        font-size: 0.9rem;
        color: var(--dark);
    }

    .stats-icons {
        display: flex;
        gap: 15px;
        margin-top: 5px;
    }

    .stat-icon {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.85rem;
        color: var(--gray);
    }

    .stat-icon i {
        color: var(--primary);
    }

    /* =============================
       EMPTY STATE
    ============================= */
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }

    .empty-icon {
        font-size: 4rem;
        color: #e0e6ed;
        margin-bottom: 20px;
    }

    .empty-title {
        font-size: 1.5rem;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .empty-text {
        color: var(--gray);
        margin-bottom: 20px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    /* =============================
       MODAL DE CONFIRMATION
    ============================= */
    .confirmation-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }

    .modal-content {
        background: white;
        border-radius: var(--radius);
        max-width: 500px;
        width: 100%;
        box-shadow: 0 25px 50px rgba(0,0,0,0.3);
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        color: white;
        padding: 25px;
        border-radius: var(--radius) var(--radius) 0 0;
    }

    .modal-header h3 {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.3rem;
    }

    .modal-body {
        padding: 25px;
        border-bottom: 1px solid #f0f3f8;
    }

    .modal-body p {
        margin-bottom: 15px;
        line-height: 1.6;
    }

    .matches-to-delete {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 15px;
        max-height: 200px;
        overflow-y: auto;
    }

    .match-to-delete {
        padding: 8px 12px;
        background: white;
        border-radius: 6px;
        margin-bottom: 8px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-left: 3px solid var(--danger);
    }

    .modal-footer {
        padding: 20px 25px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .btn-modal-cancel {
        background: #95a5a6;
        color: white;
    }

    .btn-modal-confirm {
        background: linear-gradient(135deg, var(--danger), var(--danger-dark));
        color: white;
    }

    .btn-modal-cancel:hover,
    .btn-modal-confirm:hover {
        transform: translateY(-2px);
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .controls-bar {
            flex-direction: column;
            align-items: stretch;
        }
        
        .stats-info {
            justify-content: space-between;
            width: 100%;
        }
        
        .controls-buttons {
            width: 100%;
            justify-content: center;
        }
        
        .btn {
            flex: 1;
            justify-content: center;
            min-width: 120px;
        }
        
        .modal-footer {
            flex-direction: column;
        }
        
        .modal-footer .btn {
            width: 100%;
        }
    }

    @media (max-width: 480px) {
        .container {
            padding: 15px;
        }
        
        .page-header {
            padding: 20px;
        }
        
        .header-title h1 {
            font-size: 1.8rem;
        }
        
        .matches-table td {
            padding: 12px 15px;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-trash-alt"></i>
                    <h1>Supprimer des Matchs</h1>
                </div>
                <div class="header-subtitle">
                    Sélectionnez les matchs que vous souhaitez supprimer de la base de données.
                    Cette action est irréversible et supprimera également toutes les évaluations des joueurs pour ces matchs.
                </div>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning); font-size: 1.2rem;"></i>
                    <div>
                        <strong>Attention :</strong> La suppression des matchs déjà joués entraînera la perte des statistiques des joueurs pour ces rencontres.
                    </div>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <div class="message-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= $_SESSION['success_message'] ?></div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTROLS BAR -->
        <div class="controls-bar">
            <div class="stats-info">
                <div class="matches-count">
                    <i class="fas fa-calendar-alt"></i> <?= count($matchs) ?> match(s)
                </div>
                <div class="selection-info">
                    <span class="count">0</span> match(s) sélectionné(s)
                </div>
            </div>
            
            <div class="controls-buttons">
                <button type="button" class="btn btn-select-all" id="selectAllBtn">
                    <i class="fas fa-check-square"></i> Tout sélectionner
                </button>
                
                <button type="button" class="btn btn-deselect-all" id="deselectAllBtn">
                    <i class="fas fa-square"></i> Tout désélectionner
                </button>
                
                <button type="button" class="btn btn-delete-selected" id="deleteSelectedBtn">
                    <i class="fas fa-trash-alt"></i> Supprimer la sélection
                </button>
                
                <a href="liste_matchs.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- TABLEAU DES MATCHS -->
        <?php if (!empty($matchs)): ?>
            <div class="matches-table-container">
                <div class="table-responsive">
                    <form method="POST" id="deleteForm">
                        <table class="matches-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <input type="checkbox" id="selectAllCheckbox" class="match-checkbox">
                                    </th>
                                    <th>Date & Heure</th>
                                    <th>Adversaire & Lieu</th>
                                    <th>Score</th>
                                    <th>Résultat</th>
                                    <th>État</th>
                                    <th>Statistiques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matchs as $match): 
                                    $date_heure = new DateTime($match['date_heure']);
                                    $formatted_date = $date_heure->format('d/m/Y');
                                    $formatted_time = $date_heure->format('H:i');
                                    
                                    // Déterminer la couleur du score
                                    $score_class = '';
                                    if ($match['resultat'] === 'VICTOIRE') {
                                        $score_class = 'VICTOIRE';
                                    } elseif ($match['resultat'] === 'DEFAITE') {
                                        $score_class = 'DEFAITE';
                                    } elseif ($match['resultat'] === 'NUL') {
                                        $score_class = 'NUL';
                                    }
                                ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   name="matchs_selectionnes[]" 
                                                   value="<?= $match['id_match'] ?>" 
                                                   class="match-checkbox match-select">
                                        </td>
                                        <td>
                                            <div class="match-date"><?= $formatted_date ?></div>
                                            <div class="match-time"><?= $formatted_time ?></div>
                                        </td>
                                        <td>
                                            <div class="match-adversaire">
                                                <?= htmlspecialchars($match['adversaire']) ?>
                                            </div>
                                            <span class="match-lieu <?= $match['lieu'] ?>">
                                                <i class="fas <?= $match['lieu'] === 'DOMICILE' ? 'fa-home' : 'fa-plane' ?>"></i>
                                                <?= $match['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($match['score_equipe'] !== null): ?>
                                                <div class="match-score">
                                                    <?= $match['score_equipe'] ?> - <?= $match['score_adverse'] ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="color: #95a5a6; font-style: italic;">
                                                    À venir
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($match['resultat']): ?>
                                                <span class="resultat-badge <?= $score_class ?>">
                                                    <?= htmlspecialchars($match['resultat']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #95a5a6;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="etat-badge <?= $match['etat'] ?>">
                                                <?= htmlspecialchars($match['etat']) ?>
                                            </span>
                                        </td>
                                        <td class="match-stats">
                                            <div class="stats-icons">
                                                <div class="stat-icon">
                                                    <i class="fas fa-users"></i>
                                                    <span><?= $match['nb_participants'] ?> joueurs</span>
                                                </div>
                                                <?php if ($match['note_moyenne']): ?>
                                                    <div class="stat-icon">
                                                        <i class="fas fa-star"></i>
                                                        <span><?= $match['note_moyenne'] ?>/5</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Bouton de suppression caché (sera déclenché par JS) -->
                        <button type="submit" name="supprimer_matchs" id="submitDelete" style="display: none;"></button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h2 class="empty-title">Aucun match à supprimer</h2>
                <p class="empty-text">
                    Aucun match n'est actuellement enregistré dans la base de données.
                </p>
                <a href="liste_matchs.php" class="btn btn-cancel" style="margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DE CONFIRMATION -->
    <div class="confirmation-modal" id="confirmationModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h3>
            </div>
            <div class="modal-body">
                <p><strong>⚠️ Attention : Cette action est irréversible !</strong></p>
                <p>Vous êtes sur le point de supprimer <span id="modalCount">0</span> match(s) de la base de données.</p>
                <p>Cette suppression entraînera également la suppression de :</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Toutes les évaluations des joueurs pour ces matchs</li>
                    <li>Toutes les participations enregistrées</li>
                    <li>Toutes les statistiques associées</li>
                </ul>
                <div class="matches-to-delete" id="matchesListModal">
                    <!-- La liste des matchs à supprimer sera insérée ici par JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-modal-cancel" id="modalCancelBtn">
                    <i class="fas fa-times"></i> Annuler
                </button>
                <button type="button" class="btn btn-modal-confirm" id="modalConfirmBtn">
                    <i class="fas fa-trash-alt"></i> Confirmer la suppression
                </button>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Éléments DOM
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');
        const matchCheckboxes = document.querySelectorAll('.match-select');
        const selectAllBtn = document.getElementById('selectAllBtn');
        const deselectAllBtn = document.getElementById('deselectAllBtn');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
        const selectionInfo = document.querySelector('.selection-info .count');
        const confirmationModal = document.getElementById('confirmationModal');
        const modalCancelBtn = document.getElementById('modalCancelBtn');
        const modalConfirmBtn = document.getElementById('modalConfirmBtn');
        const modalCount = document.getElementById('modalCount');
        const matchesListModal = document.getElementById('matchesListModal');
        const submitDelete = document.getElementById('submitDelete');
        const deleteForm = document.getElementById('deleteForm');

        // Fonction pour mettre à jour le compteur de sélection
        function updateSelectionCount() {
            const selectedCount = document.querySelectorAll('.match-select:checked').length;
            selectionInfo.textContent = selectedCount;
            
            // Mettre à jour l'état de la case "Tout sélectionner"
            selectAllCheckbox.checked = selectedCount === matchCheckboxes.length && matchCheckboxes.length > 0;
            
            // Mettre à jour la classe des lignes sélectionnées
            matchCheckboxes.forEach(checkbox => {
                const row = checkbox.closest('tr');
                if (checkbox.checked) {
                    row.classList.add('selected');
                } else {
                    row.classList.remove('selected');
                }
            });
            
            // Activer/désactiver le bouton de suppression
            deleteSelectedBtn.disabled = selectedCount === 0;
        }

        // Sélectionner tout
        function selectAll() {
            matchCheckboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectionCount();
        }

        // Désélectionner tout
        function deselectAll() {
            matchCheckboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectionCount();
        }

        // Récupérer les infos des matchs sélectionnés
        function getSelectedMatches() {
            const selectedMatches = [];
            matchCheckboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    const row = checkbox.closest('tr');
                    const adversaire = row.querySelector('.match-adversaire').textContent;
                    const date = row.querySelector('.match-date').textContent;
                    selectedMatches.push(adversaire + " (" + date + ")");
                }
            });
            return selectedMatches;
        }

        // Afficher le modal de confirmation
        function showConfirmationModal() {
            const selectedCount = document.querySelectorAll('.match-select:checked').length;
            const selectedMatches = getSelectedMatches();
            
            if (selectedCount === 0) {
                alert('Veuillez sélectionner au moins un match à supprimer.');
                return;
            }
            
            // Mettre à jour le contenu du modal
            modalCount.textContent = selectedCount;
            matchesListModal.innerHTML = '';
            
            selectedMatches.forEach(matchInfo => {
                const matchDiv = document.createElement('div');
                matchDiv.className = 'match-to-delete';
                matchDiv.innerHTML = `
                    <span>${matchInfo}</span>
                    <i class="fas fa-trash" style="color: var(--danger);"></i>
                `;
                matchesListModal.appendChild(matchDiv);
            });
            
            // Afficher le modal
            confirmationModal.style.display = 'flex';
        }

        // Événements
        selectAllCheckbox.addEventListener('change', function() {
            if (this.checked) {
                selectAll();
            } else {
                deselectAll();
            }
        });

        selectAllBtn.addEventListener('click', selectAll);
        deselectAllBtn.addEventListener('click', deselectAll);
        deleteSelectedBtn.addEventListener('click', showConfirmationModal);

        matchCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectionCount);
        });

        // Gestion du modal
        modalCancelBtn.addEventListener('click', function() {
            confirmationModal.style.display = 'none';
        });

        modalConfirmBtn.addEventListener('click', function() {
            // Fermer le modal
            confirmationModal.style.display = 'none';
            
            // Afficher une animation de chargement
            deleteSelectedBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Suppression en cours...';
            deleteSelectedBtn.disabled = true;
            
            // Soumettre le formulaire après un court délai
            setTimeout(() => {
                submitDelete.click();
            }, 500);
        });

        // Fermer le modal en cliquant en dehors
        confirmationModal.addEventListener('click', function(e) {
            if (e.target === confirmationModal) {
                confirmationModal.style.display = 'none';
            }
        });

        // Initialiser le compteur
        updateSelectionCount();

        // Animation des lignes au survol
        const tableRows = document.querySelectorAll('.matches-table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.boxShadow = '0 5px 15px rgba(0,0,0,0.1)';
            });
            
            row.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
                this.style.boxShadow = 'none';
            });
        });

        // Empêcher la soumission du formulaire si aucun match n'est sélectionné
        deleteForm.addEventListener('submit', function(e) {
            const selectedCount = document.querySelectorAll('.match-select:checked').length;
            if (selectedCount === 0) {
                e.preventDefault();
                alert('Veuillez sélectionner au moins un match à supprimer.');
            }
        });
    });

    // Gestion des touches pour fermer le modal avec ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('confirmationModal');
            if (modal.style.display === 'flex') {
                modal.style.display = 'none';
            }
        }
    });
    </script>
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>