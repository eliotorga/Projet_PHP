<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

$error = "";
$success = "";

// Formulaire envoyé ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "date_heure" => $_POST["date_heure"] ?? null,
        "adversaire" => trim($_POST["adversaire"] ?? ""),
        "lieu"       => $_POST["lieu"] ?? ""
    ];

    // Validation avancée
    $errors = [];
    
    // Validation date et heure
    if (empty($data["date_heure"])) {
        $errors[] = "La date et l'heure sont obligatoires";
    } else {
        $matchDateTime = new DateTime($data["date_heure"]);
        $now = new DateTime();
        $minDate = new DateTime('-1 year');
        $maxDate = new DateTime('+2 years');
        
        if ($matchDateTime < $minDate) {
            $errors[] = "La date ne peut pas être antérieure à il y a un an";
        }
        if ($matchDateTime > $maxDate) {
            $errors[] = "La date ne peut pas dépasser deux ans dans le futur";
        }
        if ($matchDateTime < $now) {
            $errors[] = "La date du match doit être dans le futur";
        }
    }
    
    // Validation adversaire
    if (empty($data["adversaire"])) {
        $errors[] = "L'adversaire est obligatoire";
    } elseif (strlen($data["adversaire"]) < 2) {
        $errors[] = "Le nom de l'adversaire doit contenir au moins 2 caractères";
    } elseif (strlen($data["adversaire"]) > 100) {
        $errors[] = "Le nom de l'adversaire ne peut pas dépasser 100 caractères";
    }
    
    // Validation lieu
    if (empty($data["lieu"]) || !in_array($data["lieu"], ['DOMICILE', 'EXTERIEUR'])) {
        $errors[] = "Veuillez sélectionner un lieu valide";
    }
    
    // Vérifier les conflits de dates
    if (empty($errors)) {
        $stmt = $gestion_sportive->prepare("
            SELECT COUNT(*) 
            FROM matchs 
            WHERE DATE(date_heure) = DATE(?) 
            AND etat != 'JOUE'
        ");
        $stmt->execute([$data["date_heure"]]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Un match est déjà programmé à cette date";
        }
    }

    if (empty($errors)) {
        try {
            insertMatch($gestion_sportive, $data);
            $_SESSION['success_message'] = "✅ Match ajouté avec succès !";
            header("Location: liste_matchs.php");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du match : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planifier un Match</title>
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
        padding: 20px;
    }

    .container {
        max-width: 800px;
        margin: 0 auto;
    }

    /* =============================
       HEADER
    ============================= */
    .page-header {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius);
        padding: 35px;
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
        font-size: 2.4rem;
    }

    .header-subtitle {
        opacity: 0.9;
        font-size: 1.1rem;
        line-height: 1.5;
    }

    /* =============================
       CARTE DU FORMULAIRE
    ============================= */
    .form-card {
        background: white;
        border-radius: var(--radius);
        padding: 40px;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .form-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--secondary), var(--primary));
    }

    /* =============================
       MESSAGES
    ============================= */
    .alert {
        padding: 18px 25px;
        border-radius: 12px;
        margin-bottom: 25px;
        display: flex;
        align-items: center;
        gap: 15px;
        animation: slideIn 0.5s ease;
    }

    .alert-error {
        background: linear-gradient(135deg, #ffebee, #ffcdd2);
        color: #c62828;
        border-left: 4px solid var(--danger);
    }

    .alert-success {
        background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
        color: #2e7d32;
        border-left: 4px solid var(--secondary);
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
       FORMULAIRE
    ============================= */
    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 30px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 12px;
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .form-label .required {
        color: var(--danger);
        font-size: 1.2rem;
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 16px 20px;
        border: 2px solid #e0e6ed;
        border-radius: 10px;
        font-size: 1rem;
        color: var(--dark);
        transition: var(--transition);
        background: white;
    }

    .form-input:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }

    .form-input.invalid {
        border-color: var(--danger);
    }

    .form-input.valid {
        border-color: var(--secondary);
    }

    .input-with-icon {
        position: relative;
    }

    .input-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
        font-size: 1.1rem;
    }

    .form-hint {
        font-size: 0.9rem;
        color: var(--gray);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* =============================
       CHOIX LIEU
    ============================= */
    .location-options {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
        margin-top: 10px;
    }

    .location-option {
        position: relative;
    }

    .location-option input[type="radio"] {
        display: none;
    }

    .location-label {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 12px;
        padding: 25px 20px;
        border: 2px solid #e0e6ed;
        border-radius: 12px;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        background: white;
    }

    .location-label:hover {
        border-color: var(--secondary);
        transform: translateY(-3px);
    }

    .location-option input[type="radio"]:checked + .location-label {
        border-color: var(--secondary);
        background: linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05));
        box-shadow: 0 5px 15px rgba(46, 204, 113, 0.2);
    }

    .location-icon {
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
    }

    .location-text {
        font-weight: 600;
        color: var(--dark);
    }

    .location-desc {
        font-size: 0.9rem;
        color: var(--gray);
        margin-top: 5px;
    }

    /* =============================
       PRÉVISUALISATION
    ============================= */
    .preview-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: var(--radius);
        padding: 30px;
        margin-top: 30px;
        border: 2px dashed #e0e6ed;
        display: none;
    }

    .preview-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 25px;
        color: var(--dark);
        font-size: 1.3rem;
    }

    .preview-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
    }

    .preview-item {
        padding: 18px;
        background: white;
        border-radius: 10px;
        border: 1px solid #e0e6ed;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }

    .preview-label {
        font-size: 0.9rem;
        color: var(--gray);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .preview-value {
        font-weight: 600;
        color: var(--dark);
        font-size: 1.1rem;
    }

    /* =============================
       CALENDRIER MINI
    ============================= */
    .calendar-mini {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        margin-top: 30px;
        border: 1px solid #e0e6ed;
        box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    }

    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 1px solid #e0e6ed;
    }

    .calendar-title {
        font-weight: 600;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .calendar-days {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
        margin-bottom: 10px;
    }

    .calendar-day {
        text-align: center;
        font-weight: 600;
        color: var(--gray);
        font-size: 0.9rem;
        padding: 8px 0;
    }

    .calendar-dates {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 8px;
    }

    .calendar-date {
        text-align: center;
        padding: 12px 0;
        border-radius: 8px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
    }

    .calendar-date:hover {
        background: #f8fafc;
    }

    .calendar-date.selected {
        background: var(--secondary);
        color: white;
        font-weight: 600;
    }

    .calendar-date.has-match {
        background: var(--accent);
        color: white;
    }

    .calendar-date.has-match::after {
        content: '⚽';
        position: absolute;
        top: 2px;
        right: 2px;
        font-size: 0.7rem;
    }

    .calendar-date.other-month {
        color: #ccc;
    }

    /* =============================
       BOUTONS
    ============================= */
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 40px;
        padding-top: 30px;
        border-top: 1px solid #e0e6ed;
        flex-wrap: wrap;
    }

    .btn {
        padding: 16px 32px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1.1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition);
        cursor: pointer;
        border: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
        flex: 1;
    }

    .btn-submit:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(46, 204, 113, 0.3);
    }

    .btn-reset {
        background: linear-gradient(135deg, #f39c12, #e67e22);
        color: white;
    }

    .btn-reset:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(243, 156, 18, 0.3);
    }

    .btn-cancel {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        color: white;
    }

    .btn-cancel:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 25px rgba(149, 165, 166, 0.3);
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

    .form-card {
        animation: fadeInUp 0.5s ease forwards;
    }

    /* =============================
       VALIDATION VISUELLE
    ============================= */
    .validation-feedback {
        font-size: 0.9rem;
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
        opacity: 0;
        transition: var(--transition);
    }

    .validation-feedback.visible {
        opacity: 1;
    }

    .validation-feedback.valid {
        color: var(--secondary);
    }

    .validation-feedback.invalid {
        color: var(--danger);
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
        
        .page-header {
            padding: 25px;
        }
        
        .header-title h1 {
            font-size: 1.8rem;
        }
        
        .location-options {
            grid-template-columns: 1fr;
        }
        
        .preview-content {
            grid-template-columns: 1fr;
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
                    <i class="fas fa-calendar-plus"></i>
                    <h1>Planifier un Nouveau Match</h1>
                </div>
                <div class="header-subtitle">
                    Programmez votre prochain match en renseignant les informations ci-dessous. 
                    Une fois ajouté, vous pourrez composer l'équipe et suivre le match.
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?= $error ?></div>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <div><?= $_SESSION['success_message'] ?></div>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- CARTE DU FORMULAIRE -->
        <div class="form-card">
            <form method="POST" id="matchForm" novalidate>
                <div class="form-grid">
                    <!-- DATE ET HEURE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-calendar-alt"></i> Date & Heure du Match <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="datetime-local" 
                                   name="date_heure" 
                                   class="form-input"
                                   required
                                   value="<?= htmlspecialchars($_POST['date_heure'] ?? '') ?>"
                                   min="<?= date('Y-m-d\TH:i', strtotime('-1 year')) ?>"
                                   max="<?= date('Y-m-d\TH:i', strtotime('+2 years')) ?>">
                            <i class="fas fa-clock input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Sélectionnez une date dans le futur
                        </div>
                        <div class="validation-feedback" id="date-feedback"></div>
                    </div>

                    <!-- ADVERSAIRE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-flag"></i> Adversaire <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="text" 
                                   name="adversaire" 
                                   class="form-input"
                                   placeholder="Ex: Paris Saint-Germain"
                                   required
                                   value="<?= htmlspecialchars($_POST['adversaire'] ?? '') ?>"
                                   minlength="2"
                                   maxlength="100">
                            <i class="fas fa-users input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Nom complet de l'équipe adverse
                        </div>
                        <div class="validation-feedback" id="adversaire-feedback"></div>
                    </div>

                    <!-- LIEU -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Lieu du Match <span class="required">*</span>
                        </label>
                        
                        <div class="location-options">
                            <div class="location-option">
                                <input type="radio" 
                                       name="lieu" 
                                       id="lieu-domicile" 
                                       value="DOMICILE"
                                       <?= ($_POST['lieu'] ?? '') == 'DOMICILE' ? 'checked' : '' ?>
                                       required>
                                <label class="location-label" for="lieu-domicile">
                                    <div class="location-icon">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <div class="location-text">Domicile</div>
                                    <div class="location-desc">Match à notre stade</div>
                                </label>
                            </div>
                            
                            <div class="location-option">
                                <input type="radio" 
                                       name="lieu" 
                                       id="lieu-exterieur" 
                                       value="EXTERIEUR"
                                       <?= ($_POST['lieu'] ?? '') == 'EXTERIEUR' ? 'checked' : '' ?>
                                       required>
                                <label class="location-label" for="lieu-exterieur">
                                    <div class="location-icon">
                                        <i class="fas fa-road"></i>
                                    </div>
                                    <div class="location-text">Extérieur</div>
                                    <div class="location-desc">Match à l'extérieur</div>
                                </label>
                            </div>
                        </div>
                        
                        <div class="validation-feedback" id="lieu-feedback"></div>
                    </div>
                </div>

                <!-- PRÉVISUALISATION -->
                <div class="preview-card" id="preview">
                    <div class="preview-title">
                        <i class="fas fa-eye"></i>
                        <h3>Résumé du Match</h3>
                    </div>
                    <div class="preview-content">
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-calendar-alt"></i> Date
                            </div>
                            <div class="preview-value" id="preview-date">-</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-clock"></i> Heure
                            </div>
                            <div class="preview-value" id="preview-time">-</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-flag"></i> Adversaire
                            </div>
                            <div class="preview-value" id="preview-adversaire">-</div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-map-marker-alt"></i> Lieu
                            </div>
                            <div class="preview-value" id="preview-lieu">-</div>
                        </div>
                    </div>
                </div>

                <!-- CALENDRIER DES MATCHS EXISTANTS -->
                <?php 
                // Récupérer les matchs à venir
                $stmt = $gestion_sportive->prepare("
                    SELECT date_heure, adversaire, lieu 
                    FROM matchs 
                    WHERE date_heure > NOW() 
                    ORDER BY date_heure ASC 
                    LIMIT 10
                ");
                $stmt->execute();
                $upcoming_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($upcoming_matches)): ?>
                <div class="calendar-mini">
                    <div class="calendar-header">
                        <div class="calendar-title">
                            <i class="fas fa-calendar-check"></i>
                            <span>Matchs à venir</span>
                        </div>
                    </div>
                    <div class="matchs-list">
                        <?php foreach ($upcoming_matches as $match): ?>
                            <div style="padding: 12px; border-bottom: 1px solid #e0e6ed; display: flex; align-items: center; gap: 15px;">
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-size: 0.9rem;">
                                    <i class="fas fa-futbol"></i>
                                </div>
                                <div style="flex: 1;">
                                    <div style="font-weight: 600;"><?= htmlspecialchars($match['adversaire']) ?></div>
                                    <div style="font-size: 0.9rem; color: var(--gray);">
                                        <?= date("d/m/Y H:i", strtotime($match['date_heure'])) ?> • 
                                        <span style="color: <?= $match['lieu'] === 'DOMICILE' ? 'var(--secondary)' : 'var(--accent)' ?>;">
                                            <?= $match['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ACTIONS -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-calendar-plus"></i> Planifier le match
                    </button>
                    
                    <button type="reset" class="btn btn-reset" onclick="resetForm()">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    
                    <a href="liste_matchs.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    // =============================
    // VALIDATION EN TEMPS RÉEL
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('matchForm');
        const inputs = form.querySelectorAll('input, select, [type="radio"]');
        
        // Fonction de validation
        function validateField(field) {
            const value = field.value.trim();
            const name = field.name;
            const feedback = document.getElementById(`${name}-feedback`);
            
            if (!feedback) return;
            
            feedback.classList.remove('valid', 'invalid', 'visible');
            
            // Validation spécifique par champ
            let isValid = true;
            let message = '';
            
            switch(name) {
                case 'date_heure':
                    if (!value) {
                        isValid = false;
                        message = 'La date et l\'heure sont obligatoires';
                    } else {
                        const selectedDate = new Date(value);
                        const now = new Date();
                        
                        if (selectedDate <= now) {
                            isValid = false;
                            message = 'La date doit être dans le futur';
                        } else {
                            const daysDiff = Math.ceil((selectedDate - now) / (1000 * 60 * 60 * 24));
                            isValid = true;
                            message = `✓ Match dans ${daysDiff} jour(s)`;
                        }
                    }
                    break;
                    
                case 'adversaire':
                    if (value.length < 2) {
                        isValid = false;
                        message = 'Minimum 2 caractères';
                    } else if (value.length > 100) {
                        isValid = false;
                        message = 'Maximum 100 caractères';
                    } else {
                        isValid = true;
                        message = '✓ Nom valide';
                    }
                    break;
                    
                case 'lieu':
                    const lieuSelected = form.querySelector('input[name="lieu"]:checked');
                    if (!lieuSelected) {
                        isValid = false;
                        message = 'Sélectionnez un lieu';
                    } else {
                        isValid = true;
                        message = `✓ ${lieuSelected.value === 'DOMICILE' ? 'Match à domicile' : 'Match à l\'extérieur'}`;
                    }
                    break;
            }
            
            // Appliquer le feedback
            if (message) {
                feedback.textContent = message;
                feedback.classList.add('visible');
                feedback.classList.add(isValid ? 'valid' : 'invalid');
                
                if (field.classList) {
                    field.classList.toggle('valid', isValid);
                    field.classList.toggle('invalid', !isValid);
                }
            }
            
            return isValid;
        }
        
        // Événements de validation
        inputs.forEach(input => {
            if (input.type === 'radio') {
                input.addEventListener('change', () => {
                    validateField(input);
                    updatePreview();
                });
            } else {
                input.addEventListener('blur', () => validateField(input));
                input.addEventListener('input', () => {
                    validateField(input);
                    updatePreview();
                });
            }
        });
        
        // Validation du formulaire
        form.addEventListener('submit', function(e) {
            let formIsValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    formIsValid = false;
                    if (input.required && !input.value.trim()) {
                        input.focus();
                    }
                }
            });
            
            if (!formIsValid) {
                e.preventDefault();
                showNotification('Veuillez corriger les erreurs dans le formulaire', 'error');
            }
        });
        
        // Auto-sélection de la date (dans 7 jours à 20h par défaut)
        const dateInput = document.querySelector('input[name="date_heure"]');
        if (dateInput && !dateInput.value) {
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            defaultDate.setHours(20, 0, 0, 0);
            
            const timezoneOffset = defaultDate.getTimezoneOffset() * 60000;
            const localDate = new Date(defaultDate.getTime() - timezoneOffset);
            dateInput.value = localDate.toISOString().slice(0, 16);
            
            updatePreview();
        }
    });
    
    // =============================
    // PRÉVISUALISATION
    // =============================
    function updatePreview() {
        const dateHeure = document.querySelector('input[name="date_heure"]').value;
        const adversaire = document.querySelector('input[name="adversaire"]').value;
        const lieuRadio = document.querySelector('input[name="lieu"]:checked');
        
        const preview = document.getElementById('preview');
        
        // Afficher/masquer la prévisualisation
        if (dateHeure || adversaire || lieuRadio) {
            preview.style.display = 'block';
            
            // Formater la date
            if (dateHeure) {
                const dateObj = new Date(dateHeure);
                const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                const optionsTime = { hour: '2-digit', minute: '2-digit' };
                
                document.getElementById('preview-date').textContent = 
                    dateObj.toLocaleDateString('fr-FR', optionsDate);
                document.getElementById('preview-time').textContent = 
                    dateObj.toLocaleTimeString('fr-FR', optionsTime);
            } else {
                document.getElementById('preview-date').textContent = '-';
                document.getElementById('preview-time').textContent = '-';
            }
            
            // Adversaire
            document.getElementById('preview-adversaire').textContent = 
                adversaire || '-';
            
            // Lieu
            if (lieuRadio) {
                const lieuText = lieuRadio.value === 'DOMICILE' ? 
                    '<span style="color: var(--secondary);"><i class="fas fa-home"></i> Domicile</span>' :
                    '<span style="color: var(--accent);"><i class="fas fa-road"></i> Extérieur</span>';
                document.getElementById('preview-lieu').innerHTML = lieuText;
            } else {
                document.getElementById('preview-lieu').textContent = '-';
            }
        } else {
            preview.style.display = 'none';
        }
    }
    
    // =============================
    // RÉINITIALISATION
    // =============================
    function resetForm() {
        const preview = document.getElementById('preview');
        preview.style.display = 'none';
        
        // Réinitialiser les feedbacks
        document.querySelectorAll('.validation-feedback').forEach(fb => {
            fb.classList.remove('visible', 'valid', 'invalid');
            fb.textContent = '';
        });
        
        // Réinitialiser les classes des inputs
        document.querySelectorAll('.form-input, .form-select').forEach(input => {
            input.classList.remove('valid', 'invalid');
        });
        
        // Réinitialiser les radios
        document.querySelectorAll('.location-label').forEach(label => {
            label.style.boxShadow = 'none';
            label.style.borderColor = '#e0e6ed';
            label.style.background = 'white';
        });
        
        // Réinitialiser à la date par défaut
        const dateInput = document.querySelector('input[name="date_heure"]');
        if (dateInput) {
            const defaultDate = new Date();
            defaultDate.setDate(defaultDate.getDate() + 7);
            defaultDate.setHours(20, 0, 0, 0);
            
            const timezoneOffset = defaultDate.getTimezoneOffset() * 60000;
            const localDate = new Date(defaultDate.getTime() - timezoneOffset);
            dateInput.value = localDate.toISOString().slice(0, 16);
        }
        
        showNotification('Formulaire réinitialisé', 'info');
        updatePreview();
    }
    
    // =============================
    // NOTIFICATIONS
    // =============================
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: ${type === 'error' ? 'var(--danger)' : 
                        type === 'success' ? 'var(--secondary)' : 
                        type === 'warning' ? 'var(--accent)' : 'var(--info)'};
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.5s ease;
        `;
        
        const icon = type === 'error' ? 'fa-exclamation-circle' :
                    type === 'success' ? 'fa-check-circle' :
                    type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle';
        
        notification.innerHTML = `
            <i class="fas ${icon}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        // Supprimer après 5 secondes
        setTimeout(() => {
            notification.style.animation = 'slideOutRight 0.5s ease';
            setTimeout(() => notification.remove(), 500);
        }, 5000);
        
        // Ajouter les animations CSS
        if (!document.querySelector('#notification-styles')) {
            const style = document.createElement('style');
            style.id = 'notification-styles';
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
            document.head.appendChild(style);
        }
    }
    
    // =============================
    // GESTION DES RADIOS
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const radioLabels = document.querySelectorAll('.location-label');
        radioLabels.forEach(label => {
            label.addEventListener('click', function() {
                // Mettre à jour le style de tous les labels
                radioLabels.forEach(l => {
                    l.style.boxShadow = 'none';
                    l.style.borderColor = '#e0e6ed';
                    l.style.background = 'white';
                });
                
                // Appliquer le style au label sélectionné
                this.style.boxShadow = '0 5px 15px rgba(46, 204, 113, 0.2)';
                this.style.borderColor = 'var(--secondary)';
                this.style.background = 'linear-gradient(135deg, rgba(46, 204, 113, 0.1), rgba(46, 204, 113, 0.05))';
            });
        });
    });
    </script>
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>