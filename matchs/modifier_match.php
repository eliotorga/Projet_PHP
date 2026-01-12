<?php
// permet de modifier les informations d'un match existant
// affiche un formulaire avec les donnees actuelles et les statistiques du match

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_match.php";

// Vérification ID match
if (!isset($_GET["id_match"])) {
    $_SESSION['error_message'] = "ID match manquant.";
    header("Location: liste_matchs.php");
    exit;
}

$id_match = intval($_GET["id_match"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    $_SESSION['error_message'] = "Match introuvable.";
    header("Location: liste_matchs.php");
    exit;
}

// Récupérer les statistiques du match
$stats_match = getMatchStatsSummary($gestion_sportive, $id_match);

// Récupérer les adversaires existants pour l'autocomplete
$adversaires_existants = getDistinctAdversaires($gestion_sportive);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Match - <?= htmlspecialchars($match["adversaire"]) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modifier_match.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
    <!-- Styles déplacés vers modifier_match.css
    <style>
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --primary-light: #2ecc71;
        --secondary: #2ecc71;
        --warning: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --dark: #2c3e50;
        --light: #f8fafc;
        --gray: #6b7280;
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: var(--dark);
        min-height: 100vh;
    }

    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header */
    .page-header {
        background: white;
        border-radius: var(--radius);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        animation: slideInDown 0.6s ease;
        border-left: 4px solid var(--primary);
    }

    @keyframes slideInDown {
        from {
            opacity: 0;
            transform: translateY(-30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .match-title {
        flex: 1;
    }

    .match-title h1 {
        font-size: 2rem;
        color: var(--dark);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .match-details {
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        margin-top: 1rem;
    }

    .detail-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--light);
        border-radius: 50px;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .detail-badge i {
        color: var(--primary);
    }

    .match-stats {
        display: flex;
        gap: 1.5rem;
        padding: 1rem;
        background: var(--light);
        border-radius: var(--radius);
        min-width: 250px;
    }

    .stat-item {
        text-align: center;
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin-bottom: 0.25rem;
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Form Container */
    .form-container {
        background: white;
        border-radius: var(--radius);
        padding: 2.5rem;
        box-shadow: var(--shadow);
        animation: slideInUp 0.6s ease 0.2s both;
        position: relative;
        overflow: hidden;
    }

    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .form-container::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 600;
        color: var(--dark);
        font-size: 0.9rem;
    }

    .form-label.required::after {
        content: " *";
        color: var(--danger);
    }

    .form-control {
        width: 100%;
        padding: 0.875rem 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        font-size: 1rem;
        transition: var(--transition);
        background: white;
    }

    .form-control:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 122, 60, 0.1);
    }

    /* Score Inputs */
    .score-container {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
    }

    .score-input {
        flex: 1;
        position: relative;
    }

    .score-input input {
        width: 100%;
        padding: 1rem;
        text-align: center;
        font-size: 1.25rem;
        font-weight: 700;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        transition: var(--transition);
    }

    .score-input input:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 122, 60, 0.1);
    }

    .score-separator {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--gray);
        min-width: 40px;
        text-align: center;
    }

    .score-label {
        position: absolute;
        top: -0.75rem;
        left: 1rem;
        background: white;
        padding: 0 0.5rem;
        font-size: 0.75rem;
        color: var(--gray);
        font-weight: 600;
    }

    /* Result Buttons */
    .result-buttons {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .result-btn {
        flex: 1;
        min-width: 120px;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .result-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .result-btn.active {
        border-color: var(--primary);
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: var(--primary);
    }

    .result-btn.victoire.active {
        border-color: var(--secondary);
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #047857;
    }

    .result-btn.defaite.active {
        border-color: var(--danger);
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: #dc2626;
    }

    .result-btn.nul.active {
        border-color: var(--warning);
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #d97706;
    }

    /* State Select */
    .state-buttons {
        display: flex;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .state-btn {
        flex: 1;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        font-size: 0.9rem;
    }

    .state-btn:hover {
        transform: translateY(-2px);
    }

    .state-btn.active {
        border-color: var(--primary);
        background: var(--primary);
        color: white;
    }

    .state-btn.A_PREPARER.active {
        background: linear-gradient(135deg, var(--warning), #e67e22);
        border-color: var(--warning);
    }

    .state-btn.PREPARE.active {
        background: linear-gradient(135deg, var(--info), #2980b9);
        border-color: var(--info);
    }

    .state-btn.JOUE.active {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        border-color: var(--secondary);
    }

    /* Datalist styling */
    .suggestions-list {
        max-height: 200px;
        overflow-y: auto;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        position: absolute;
        width: 100%;
        z-index: 1000;
        box-shadow: var(--shadow);
        display: none;
    }

    .suggestion-item {
        padding: 0.75rem 1rem;
        cursor: pointer;
        transition: var(--transition);
        border-bottom: 1px solid #f1f5f9;
    }

    .suggestion-item:hover {
        background: var(--light);
    }

    /* Actions */
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 3rem;
        padding-top: 2rem;
        border-top: 2px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .btn {
        padding: 0.875rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.75rem;
        transition: var(--transition);
        cursor: pointer;
        border: 2px solid transparent;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark), #0f4a24);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(30, 122, 60, 0.3);
    }

    .btn-secondary {
        background: white;
        color: var(--dark);
        border-color: #e5e7eb;
    }

    .btn-secondary:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }

    /* Status Badge */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .A_PREPARER { background: #fef3c7; color: #92400e; }
    .PREPARE { background: #dbeafe; color: #1e40af; }
    .JOUE { background: #d1fae5; color: #065f46; }

    .VICTOIRE { background: #d1fae5; color: #065f46; }
    .DEFAITE { background: #fee2e2; color: #991b1b; }
    .NUL { background: #fef3c7; color: #92400e; }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .header-content {
            flex-direction: column;
        }
        
        .form-container {
            padding: 1.5rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .score-container {
            flex-direction: column;
        }
        
        .score-separator {
            transform: rotate(90deg);
        }
        
        .result-buttons,
        .state-buttons {
            flex-direction: column;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
    -->
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="match-title">
                    <h1><i class="fas fa-futbol"></i> Modifier le Match</h1>
                    <div class="match-details">
                        <span class="detail-badge">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
                        </span>
                        <span class="detail-badge">
                            <i class="fas fa-users"></i>
                            <?= htmlspecialchars($match["adversaire"]) ?>
                        </span>
                        <span class="status-badge <?= $match["etat"] ?>">
                            <?= $match["etat"] === "A_PREPARER" ? "À préparer" : 
                               ($match["etat"] === "PREPARE" ? "Préparé" : "Joué") ?>
                        </span>
                        <?php if ($match["resultat"]): ?>
                            <span class="status-badge <?= $match["resultat"] ?>">
                                <?= $match["resultat"] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($stats_match['nb_joueurs'] > 0): ?>
                <div class="match-stats">
                    <div class="stat-item">
                        <div class="stat-value"><?= $stats_match['nb_joueurs'] ?></div>
                        <div class="stat-label">Joueurs</div>
                    </div>
                    <?php if ($stats_match['moyenne_eval']): ?>
                    <div class="stat-item">
                        <div class="stat-value"><?= number_format($stats_match['moyenne_eval'], 1) ?></div>
                        <div class="stat-label">Moyenne</div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Form -->
        <form method="POST" action="save_modifier_match.php" class="form-container" id="editMatchForm">
            <input type="hidden" name="id_match" value="<?= $id_match ?>">
            
            <div class="form-grid">
                <!-- Date & Heure -->
                <div class="form-group">
                    <label for="date_heure" class="form-label required">
                        <i class="far fa-calendar-alt"></i> Date et Heure
                    </label>
                    <input type="datetime-local" 
                           id="date_heure" 
                           name="date_heure" 
                           class="form-control" 
                           value="<?= date('Y-m-d\TH:i', strtotime($match["date_heure"])) ?>"
                           required>
                </div>
                
                <!-- Adversaire -->
                <div class="form-group">
                    <label for="adversaire" class="form-label required">
                        <i class="fas fa-users"></i> Adversaire
                    </label>
                    <input type="text" 
                           id="adversaire" 
                           name="adversaire" 
                           class="form-control" 
                           value="<?= htmlspecialchars($match["adversaire"]) ?>" 
                           required
                           list="adversairesList"
                           autocomplete="off">
                    <datalist id="adversairesList">
                        <?php foreach ($adversaires_existants as $adv): ?>
                            <option value="<?= htmlspecialchars($adv) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
                
                <!-- Lieu -->
                <div class="form-group">
                    <label class="form-label required">
                        <i class="fas fa-map-marker-alt"></i> Lieu du Match
                    </label>
                    <select name="lieu" class="form-control" required>
                        <option value="DOMICILE" <?= ($match["lieu"] === "DOMICILE") ? "selected" : "" ?>>Domicile</option>
                        <option value="EXTERIEUR" <?= ($match["lieu"] === "EXTERIEUR") ? "selected" : "" ?>>Extérieur</option>
                    </select>
                </div>
                
                <!-- Score -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-futbol"></i> Score du Match
                    </label>
                    <div class="score-container">
                        <div class="score-input">
                            <span class="score-label">Notre équipe</span>
                            <input type="number" 
                                   id="score_equipe" 
                                   name="score_equipe" 
                                   min="0" 
                                   max="20"
                                   value="<?= $match["score_equipe"] ?? '' ?>"
                                   placeholder="0">
                        </div>
                        <span class="score-separator">-</span>
                        <div class="score-input">
                            <span class="score-label">Adversaire</span>
                            <input type="number" 
                                   id="score_adverse" 
                                   name="score_adverse" 
                                   min="0" 
                                   max="20"
                                   value="<?= $match["score_adverse"] ?? '' ?>"
                                   placeholder="0">
                        </div>
                    </div>
                </div>
                
                <!-- Résultat -->
                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-flag-checkered"></i> Résultat
                    </label>
                    <?php
                        $resultat_initial = $match["resultat"] ?? '';
                        if (!$resultat_initial && $match["etat"] === "JOUE" && $match["score_equipe"] !== null && $match["score_adverse"] !== null) {
                            if ((int)$match["score_equipe"] > (int)$match["score_adverse"]) $resultat_initial = "VICTOIRE";
                            elseif ((int)$match["score_equipe"] < (int)$match["score_adverse"]) $resultat_initial = "DEFAITE";
                            else $resultat_initial = "NUL";
                        }
                    ?>
                    <input type="text" class="form-control" value="<?= $resultat_initial !== '' ? htmlspecialchars($resultat_initial) : 'Calculé automatiquement à partir du score' ?>" readonly>
                    <input type="hidden" name="resultat" value="">
                </div>
                
                <!-- État du Match -->
                <div class="form-group">
                    <label class="form-label required">
                        <i class="fas fa-clipboard-check"></i> État du Match
                    </label>
                    <select name="etat" class="form-control" required>
                        <option value="A_PREPARER" <?= ($match["etat"] === "A_PREPARER") ? "selected" : "" ?>>À préparer</option>
                        <option value="PREPARE" <?= ($match["etat"] === "PREPARE") ? "selected" : "" ?>>Préparé</option>
                        <option value="JOUE" <?= ($match["etat"] === "JOUE") ? "selected" : "" ?>>Joué</option>
                    </select>
                </div>
            </div>
            
            <!-- Actions -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Enregistrer les modifications
                </button>
                <a href="liste_matchs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <a href="../feuille_match/composition.php?id_match=<?= $id_match ?>" class="btn" style="background: var(--info); color: white;">
                    <i class="fas fa-futbol"></i> Composition
                </a>
                <?php if ($match["etat"] === "JOUE"): ?>
                <a href="../feuille_match/evaluation.php?id_match=<?= $id_match ?>" class="btn" style="background: var(--warning); color: white;">
                    <i class="fas fa-star"></i> Évaluation
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    
</body>
</html>

<?php include "../includes/footer.php"; ?>
