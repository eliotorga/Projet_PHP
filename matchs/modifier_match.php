<?php
session_start();
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
$stmt = $gestion_sportive->prepare("
    SELECT 
        COUNT(*) as nb_joueurs,
        AVG(evaluation) as moyenne_eval
    FROM participation 
    WHERE id_match = ?
");
$stmt->execute([$id_match]);
$stats_match = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les adversaires existants pour l'autocomplete
$stmt = $gestion_sportive->query("
    SELECT DISTINCT adversaire 
    FROM matchs 
    WHERE adversaire IS NOT NULL 
    AND adversaire != '' 
    ORDER BY adversaire
");
$adversaires_existants = $stmt->fetchAll(PDO::FETCH_COLUMN);

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
        transition: var(--transition;
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
                    <div class="state-buttons">
                        <button type="button" class="state-btn <?= $match["lieu"] === "DOMICILE" ? "active" : "" ?>"
                                data-value="DOMICILE">
                            <i class="fas fa-home"></i> Domicile
                        </button>
                        <button type="button" class="state-btn <?= $match["lieu"] === "EXTERIEUR" ? "active" : "" ?>"
                                data-value="EXTERIEUR">
                            <i class="fas fa-plane"></i> Extérieur
                        </button>
                    </div>
                    <input type="hidden" name="lieu" id="lieuInput" value="<?= $match["lieu"] ?>" required>
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
                    <div class="result-buttons" id="resultButtons">
                        <button type="button" class="result-btn victoire" data-value="VICTOIRE">
                            <i class="fas fa-trophy"></i>
                            Victoire
                        </button>
                        <button type="button" class="result-btn defaite" data-value="DEFAITE">
                            <i class="fas fa-times-circle"></i>
                            Défaite
                        </button>
                        <button type="button" class="result-btn nul" data-value="NUL">
                            <i class="fas fa-handshake"></i>
                            Match nul
                        </button>
                    </div>
                    <input type="hidden" name="resultat" id="resultatInput" value="<?= $match["resultat"] ?? '' ?>">
                </div>
                
                <!-- État du Match -->
                <div class="form-group">
                    <label class="form-label required">
                        <i class="fas fa-clipboard-check"></i> État du Match
                    </label>
                    <div class="state-buttons" id="stateButtons">
                        <button type="button" class="state-btn A_PREPARER" data-value="A_PREPARER">
                            <i class="fas fa-clock"></i> À préparer
                        </button>
                        <button type="button" class="state-btn PREPARE" data-value="PREPARE">
                            <i class="fas fa-check-circle"></i> Préparé
                        </button>
                        <button type="button" class="state-btn JOUE" data-value="JOUE">
                            <i class="fas fa-play-circle"></i> Joué
                        </button>
                    </div>
                    <input type="hidden" name="etat" id="etatInput" value="<?= $match["etat"] ?>" required>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editMatchForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Gestion des boutons de lieu
        const lieuButtons = document.querySelectorAll('.state-buttons:first-child .state-btn');
        const lieuInput = document.getElementById('lieuInput');
        
        lieuButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                lieuButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                lieuInput.value = this.dataset.value;
            });
        });
        
        // Initialiser le bouton actif pour le lieu
        document.querySelector(`.state-btn[data-value="${lieuInput.value}"]`).classList.add('active');
        
        // Gestion des boutons de résultat
        const resultButtons = document.querySelectorAll('#resultButtons .result-btn');
        const resultatInput = document.getElementById('resultatInput');
        
        // Initialiser le bouton actif pour le résultat
        if (resultatInput.value) {
            const activeResultBtn = document.querySelector(`#resultButtons .result-btn[data-value="${resultatInput.value}"]`);
            if (activeResultBtn) {
                activeResultBtn.classList.add('active');
            }
        }
        
        resultButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                resultButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                resultatInput.value = this.dataset.value;
            });
        });
        
        // Gestion des boutons d'état
        const stateButtons = document.querySelectorAll('#stateButtons .state-btn');
        const etatInput = document.getElementById('etatInput');
        
        // Initialiser le bouton actif pour l'état
        document.querySelector(`#stateButtons .state-btn[data-value="${etatInput.value}"]`).classList.add('active');
        
        stateButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                stateButtons.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                etatInput.value = this.dataset.value;
                
                // Si on passe à "Joué", proposer de remplir le résultat
                if (this.dataset.value === 'JOUE' && !resultatInput.value) {
                    showResultSuggestion();
                }
            });
        });
        
        function showResultSuggestion() {
            const scoreEquipe = document.getElementById('score_equipe').value;
            const scoreAdverse = document.getElementById('score_adverse').value;
            
            if (scoreEquipe && scoreAdverse) {
                if (parseInt(scoreEquipe) > parseInt(scoreAdverse)) {
                    resultatInput.value = 'VICTOIRE';
                    document.querySelector('.result-btn.victoire').classList.add('active');
                } else if (parseInt(scoreEquipe) < parseInt(scoreAdverse)) {
                    resultatInput.value = 'DEFAITE';
                    document.querySelector('.result-btn.defaite').classList.add('active');
                } else {
                    resultatInput.value = 'NUL';
                    document.querySelector('.result-btn.nul').classList.add('active');
                }
                
                // Afficher un message
                showNotification('Résultat suggéré en fonction du score', 'info');
            }
        }
        
        // Auto-suggestion de résultat basé sur le score
        const scoreInputs = document.querySelectorAll('#score_equipe, #score_adverse');
        scoreInputs.forEach(input => {
            input.addEventListener('change', function() {
                if (etatInput.value === 'JOUE') {
                    showResultSuggestion();
                }
            });
        });
        
        // Validation de la date
        const dateInput = document.getElementById('date_heure');
        dateInput.addEventListener('change', function() {
            const selectedDate = new Date(this.value);
            const now = new Date();
            const minDate = new Date('2023-01-01');
            const maxDate = new Date('2026-12-31');
            
            if (selectedDate < minDate || selectedDate > maxDate) {
                this.style.borderColor = 'var(--danger)';
                showNotification('La date doit être comprise entre 2023 et 2026', 'error');
            } else {
                this.style.borderColor = '#e5e7eb';
            }
        });
        
        // Validation du formulaire
        form.addEventListener('submit', function(e) {
            const adversaire = document.getElementById('adversaire').value.trim();
            const dateHeure = document.getElementById('date_heure').value;
            const scoreEquipe = document.getElementById('score_equipe').value;
            const scoreAdverse = document.getElementById('score_adverse').value;
            const etat = etatInput.value;
            const resultat = resultatInput.value;
            
            let errors = [];
            
            if (!adversaire) errors.push("L'adversaire est requis");
            if (!dateHeure) errors.push("La date et l'heure sont requises");
            if (!etat) errors.push("L'état du match est requis");
            
            // Validation des scores
            if ((scoreEquipe || scoreAdverse) && etat !== 'JOUE') {
                errors.push("Les scores ne peuvent être saisis que pour un match joué");
            }
            
            if (etat === 'JOUE' && resultat && (!scoreEquipe || !scoreAdverse)) {
                errors.push("Pour un match joué avec résultat, les deux scores doivent être renseignés");
            }
            
            if (scoreEquipe && scoreAdverse) {
                if (parseInt(scoreEquipe) < 0 || parseInt(scoreAdverse) < 0) {
                    errors.push("Les scores ne peuvent pas être négatifs");
                }
                if (parseInt(scoreEquipe) > 20 || parseInt(scoreAdverse) > 20) {
                    errors.push("Les scores ne peuvent pas dépasser 20");
                }
            }
            
            if (errors.length > 0) {
                e.preventDefault();
                showNotification(errors.join('<br>'), 'error');
                return false;
            }
            
            // Animation de chargement
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            
            return true;
        });
        
        function showNotification(message, type) {
            // Supprimer les notifications existantes
            const existingNotifications = document.querySelectorAll('.notification');
            existingNotifications.forEach(notif => notif.remove());
            
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                <span>${message}</span>
            `;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 1rem 1.5rem;
                background: ${type === 'error' ? '#fef2f2' : '#ecfdf5'};
                color: ${type === 'error' ? '#dc2626' : '#047857'};
                border-radius: 8px;
                box-shadow: var(--shadow);
                z-index: 1000;
                display: flex;
                align-items: center;
                gap: 0.75rem;
                border-left: 4px solid ${type === 'error' ? '#dc2626' : '#10b981'};
                animation: slideInRight 0.3s ease;
                max-width: 400px;
            `;
            
            document.body.appendChild(notification);
            
            // Auto-dismiss after 5 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.remove();
                    }
                }, 300);
            }, 5000);
            
            // Add animations
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOutRight {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
            `;
            if (!document.querySelector('#notification-animations')) {
                style.id = 'notification-animations';
                document.head.appendChild(style);
            }
        }
        
        // Add data to window for debugging
        window.matchData = {
            id: <?= $id_match ?>,
            adversaire: "<?= htmlspecialchars($match["adversaire"]) ?>",
            date: "<?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>",
            etat: "<?= $match["etat"] ?>"
        };
        
        // Focus sur le premier champ
        document.getElementById('adversaire').focus();
    });
    </script>
</body>
</html>

<?php include "../includes/footer.php"; ?>