<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

// Vérifier qu'un ID a été envoyé
if (!isset($_GET["id"])) {
    $_SESSION['error_message'] = "ID joueur manquant.";
    header("Location: liste_joueurs.php");
    exit;
}

$id_joueur = intval($_GET["id"]);

// Récupération du joueur à modifier
$joueur = getPlayerById($gestion_sportive, $id_joueur);

if (!$joueur) {
    $_SESSION['error_message'] = "Joueur introuvable.";
    header("Location: liste_joueurs.php");
    exit;
}

// Récupérer la liste des statuts
$statuts = getAllStatuts($gestion_sportive);

// Récupérer les commentaires du joueur
$stmt = $gestion_sportive->prepare("
    SELECT * FROM commentaire 
    WHERE id_joueur = ? 
    ORDER BY date_commentaire DESC 
    LIMIT 5
");
$stmt->execute([$id_joueur]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les statistiques du joueur
$stmt = $gestion_sportive->prepare("
    SELECT 
        COUNT(*) as total_matchs,
        SUM(CASE WHEN p.role = 'TITULAIRE' THEN 1 ELSE 0 END) as matchs_titulaire,
        AVG(p.evaluation) as moyenne_evaluation,
        COUNT(p.evaluation) as matchs_evalues
    FROM participation p
    INNER JOIN matchs m ON p.id_match = m.id_match
    WHERE p.id_joueur = ? AND m.etat = 'JOUE'
");
$stmt->execute([$id_joueur]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$error = "";
$success = "";

// Formulaire soumis ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => trim($_POST["num_licence"] ?? ""),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ? intval($_POST["taille_cm"]) : null,
        "poids_kg"       => $_POST["poids_kg"] ? floatval($_POST["poids_kg"]) : null,
        "id_statut"      => $_POST["id_statut"] ? intval($_POST["id_statut"]) : null
    ];

    // Validation
    $errors = [];
    
    if (empty($data["nom"])) $errors[] = "Le nom est requis.";
    if (empty($data["prenom"])) $errors[] = "Le prénom est requis.";
    if (empty($data["num_licence"])) $errors[] = "Le numéro de licence est requis.";
    if (empty($data["id_statut"])) $errors[] = "Le statut est requis.";
    
    // Validation spécifique
    if (!empty($data["num_licence"]) && $data["num_licence"] !== $joueur["num_licence"]) {
        // Vérifier si le numéro de licence existe déjà
        $stmt = $gestion_sportive->prepare("SELECT id_joueur FROM joueur WHERE num_licence = ? AND id_joueur != ?");
        $stmt->execute([$data["num_licence"], $id_joueur]);
        if ($stmt->fetch()) {
            $errors[] = "Ce numéro de licence est déjà utilisé par un autre joueur.";
        }
    }
    
    if ($data["taille_cm"] !== null && ($data["taille_cm"] < 140 || $data["taille_cm"] > 220)) {
        $errors[] = "La taille doit être comprise entre 140 et 220 cm.";
    }
    
    if ($data["poids_kg"] !== null && ($data["poids_kg"] < 40 || $data["poids_kg"] > 120)) {
        $errors[] = "Le poids doit être compris entre 40 et 120 kg.";
    }
    
    if ($data["date_naissance"]) {
        $date_naissance = DateTime::createFromFormat('Y-m-d', $data["date_naissance"]);
        $today = new DateTime();
        $min_date = $today->modify('-50 years');
        $max_date = $today->modify('+50 years');
        
        if ($date_naissance < $min_date || $date_naissance > $max_date) {
            $errors[] = "La date de naissance n'est pas valide.";
        }
    }

    if (empty($errors)) {
        try {
            updatePlayer($gestion_sportive, $id_joueur, $data);
            $_SESSION['success_message'] = "✅ Joueur modifié avec succès !";
            header("Location: liste_joueurs.php");
            exit;
        } catch (Exception $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Joueur - <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></title>
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
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .header-title {
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-title h1 {
        font-size: 2rem;
        color: var(--dark);
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: white;
        color: var(--dark);
        text-decoration: none;
        border-radius: 8px;
        font-weight: 600;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border: 2px solid transparent;
    }

    .back-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateX(-5px);
        border-color: var(--primary);
    }

    /* Main Layout */
    .main-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 2rem;
        margin-bottom: 2rem;
    }

    @media (max-width: 1024px) {
        .main-layout {
            grid-template-columns: 1fr;
        }
    }

    /* Form Card */
    .form-card {
        background: white;
        border-radius: var(--radius);
        padding: 2.5rem;
        box-shadow: var(--shadow);
        animation: slideInUp 0.6s ease;
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

    .form-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 2px solid #f1f5f9;
    }

    .form-header h2 {
        font-size: 1.5rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    /* Form Grid */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .form-group {
        margin-bottom: 1.25rem;
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

    .form-control.invalid {
        border-color: var(--danger);
        background: linear-gradient(135deg, rgba(231, 76, 60, 0.05), transparent);
    }

    .form-control.valid {
        border-color: var(--secondary);
    }

    .form-help {
        font-size: 0.8rem;
        color: var(--gray);
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Player Stats Sidebar */
    .player-sidebar {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .stats-card, .comments-card {
        background: white;
        border-radius: var(--radius);
        padding: 1.75rem;
        box-shadow: var(--shadow);
        animation: slideInUp 0.6s ease 0.2s both;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #f1f5f9;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 1rem;
    }

    .stat-item {
        text-align: center;
        padding: 1rem;
        background: #f8fafc;
        border-radius: 8px;
        transition: var(--transition);
    }

    .stat-item:hover {
        background: #f1f5f9;
        transform: translateY(-2px);
    }

    .stat-value {
        font-size: 1.5rem;
        font-weight: 700;
        color: var(--primary);
        margin: 0.5rem 0;
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--gray);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Comments */
    .comments-list {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 0.5rem;
    }

    .comment-item {
        padding: 1rem;
        background: #f8fafc;
        border-radius: 8px;
        margin-bottom: 0.75rem;
        border-left: 3px solid var(--info);
    }

    .comment-date {
        font-size: 0.75rem;
        color: var(--gray);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .comment-text {
        font-size: 0.9rem;
        line-height: 1.5;
        color: var(--dark);
    }

    /* Messages */
    .message-container {
        margin-bottom: 1.5rem;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .alert {
        padding: 1.25rem 1.5rem;
        border-radius: 8px;
        display: flex;
        align-items: center;
        gap: 1rem;
        border-left: 4px solid;
    }

    .alert-danger {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: #dc2626;
        border-left-color: var(--danger);
    }

    .alert-success {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #047857;
        border-left-color: var(--secondary);
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 1rem;
        margin-top: 2.5rem;
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

    .btn-danger {
        background: white;
        color: var(--danger);
        border-color: var(--danger);
    }

    .btn-danger:hover {
        background: var(--danger);
        color: white;
        transform: translateY(-2px);
    }

    /* Player Info */
    .player-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
        border-radius: 50px;
        font-size: 0.9rem;
        color: #0369a1;
        margin-bottom: 1rem;
    }

    .player-badge i {
        color: var(--info);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .form-card {
            padding: 1.5rem;
        }
        
        .stats-card, .comments-card {
            padding: 1.25rem;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
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
            <div class="header-title">
                <h1><i class="fas fa-user-edit"></i> Modifier le Joueur</h1>
                <span class="player-badge">
                    <i class="fas fa-hashtag"></i>
                    <?= htmlspecialchars($joueur['num_licence']) ?>
                </span>
            </div>
            <a href="liste_joueurs.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="message-container">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= $error ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <!-- Form Section -->
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-user-circle"></i> Informations Personnelles</h2>
                </div>

                <form method="POST" id="editForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom" class="form-label required">Nom</label>
                            <input type="text" 
                                   id="nom" 
                                   name="nom" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($joueur['nom']) ?>" 
                                   required
                                   maxlength="50">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Maximum 50 caractères
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom" class="form-label required">Prénom</label>
                            <input type="text" 
                                   id="prenom" 
                                   name="prenom" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($joueur['prenom']) ?>" 
                                   required
                                   maxlength="50">
                        </div>
                        
                        <div class="form-group">
                            <label for="num_licence" class="form-label required">Numéro de Licence</label>
                            <input type="text" 
                                   id="num_licence" 
                                   name="num_licence" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($joueur['num_licence']) ?>" 
                                   required
                                   maxlength="20"
                                   pattern="LIC[0-9]{3}"
                                   title="Format: LIC001 (LIC suivi de 3 chiffres)">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Format: LIC001 (LIC suivi de 3 chiffres)
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_naissance" class="form-label">Date de Naissance</label>
                            <input type="date" 
                                   id="date_naissance" 
                                   name="date_naissance" 
                                   class="form-control" 
                                   value="<?= $joueur['date_naissance'] ?>"
                                   min="1970-01-01"
                                   max="<?= date('Y-m-d', strtotime('-15 years')) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="taille_cm" class="form-label">Taille (cm)</label>
                            <input type="number" 
                                   id="taille_cm" 
                                   name="taille_cm" 
                                   class="form-control" 
                                   min="140" 
                                   max="220" 
                                   step="1"
                                   value="<?= $joueur['taille_cm'] ?>">
                            <div class="form-help">
                                <i class="fas fa-ruler-vertical"></i>
                                Entre 140 et 220 cm
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="poids_kg" class="form-label">Poids (kg)</label>
                            <input type="number" 
                                   id="poids_kg" 
                                   name="poids_kg" 
                                   class="form-control" 
                                   min="40" 
                                   max="120" 
                                   step="0.1"
                                   value="<?= $joueur['poids_kg'] ?>">
                            <div class="form-help">
                                <i class="fas fa-weight"></i>
                                Entre 40 et 120 kg
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_statut" class="form-label required">Statut</label>
                            <select id="id_statut" name="id_statut" class="form-control" required>
                                <option value="">-- Sélectionner un statut --</option>
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?= $s["id_statut"] ?>" 
                                        <?= ($s["id_statut"] == $joueur["id_statut"]) ? "selected" : "" ?>
                                        data-code="<?= $s['code'] ?>">
                                        <?= htmlspecialchars($s["libelle"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                        <a href="liste_joueurs.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="player-sidebar">
                <!-- Statistics -->
                <div class="stats-card">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Matchs Joués</div>
                            <div class="stat-value"><?= $stats['total_matchs'] ?: 0 ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Titulaire</div>
                            <div class="stat-value"><?= $stats['matchs_titulaire'] ?: 0 ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Moyenne</div>
                            <div class="stat-value"><?= $stats['moyenne_evaluation'] ? number_format($stats['moyenne_evaluation'], 1) : 'N/A' ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Évalués</div>
                            <div class="stat-value"><?= $stats['matchs_evalues'] ?: 0 ?></div>
                        </div>
                    </div>
                </div>

                <!-- Recent Comments -->
                <?php if (!empty($commentaires)): ?>
                <div class="comments-card">
                    <h3 class="card-title"><i class="fas fa-comment-alt"></i> Commentaires Récents</h3>
                    <div class="comments-list">
                        <?php foreach ($commentaires as $comment): ?>
                            <div class="comment-item">
                                <div class="comment-date">
                                    <i class="far fa-calendar"></i>
                                    <?= date('d/m/Y H:i', strtotime($comment['date_commentaire'])) ?>
                                </div>
                                <div class="comment-text">
                                    <?= nl2br(htmlspecialchars($comment['texte'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('editForm');
        const submitBtn = document.getElementById('submitBtn');
        const inputs = document.querySelectorAll('.form-control');
        
        // Form validation
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Clear previous validations
            inputs.forEach(input => {
                input.classList.remove('invalid', 'valid');
            });
            
            // Validate required fields
            const requiredFields = ['nom', 'prenom', 'num_licence', 'id_statut'];
            requiredFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (!field.value.trim()) {
                    field.classList.add('invalid');
                    isValid = false;
                    showError(field, 'Ce champ est obligatoire');
                } else {
                    field.classList.add('valid');
                }
            });
            
            // Validate license format
            const licenseField = document.getElementById('num_licence');
            const licenseRegex = /^LIC\d{3}$/;
            if (licenseField.value && !licenseRegex.test(licenseField.value)) {
                licenseField.classList.add('invalid');
                isValid = false;
                showError(licenseField, 'Format invalide. Doit être LIC suivi de 3 chiffres');
            }
            
            // Validate numeric fields
            const tailleField = document.getElementById('taille_cm');
            if (tailleField.value && (tailleField.value < 140 || tailleField.value > 220)) {
                tailleField.classList.add('invalid');
                isValid = false;
                showError(tailleField, 'La taille doit être entre 140 et 220 cm');
            }
            
            const poidsField = document.getElementById('poids_kg');
            if (poidsField.value && (poidsField.value < 40 || poidsField.value > 120)) {
                poidsField.classList.add('invalid');
                isValid = false;
                showError(poidsField, 'Le poids doit être entre 40 et 120 kg');
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Shake animation for errors
                form.classList.add('shake');
                setTimeout(() => {
                    form.classList.remove('shake');
                }, 500);
                
                // Scroll to first error
                const firstError = document.querySelector('.invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                
                return false;
            }
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.8';
            
            // Success animation
            submitBtn.classList.add('success-animation');
            
            return true;
        });
        
        function showError(field, message) {
            // Remove existing error message
            const existingError = field.parentElement.querySelector('.error-message');
            if (existingError) existingError.remove();
            
            // Add new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error-message';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
            errorDiv.style.color = 'var(--danger)';
            errorDiv.style.fontSize = '0.8rem';
            errorDiv.style.marginTop = '0.25rem';
            errorDiv.style.display = 'flex';
            errorDiv.style.alignItems = 'center';
            errorDiv.style.gap = '0.25rem';
            
            field.parentElement.appendChild(errorDiv);
        }
        
        // Real-time validation
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('invalid', 'valid');
                const errorMsg = this.parentElement.querySelector('.error-message');
                if (errorMsg) errorMsg.remove();
            });
            
            input.addEventListener('blur', function() {
                if (this.value.trim()) {
                    this.classList.add('valid');
                }
            });
        });
        
        // Status color indicator
        const statusSelect = document.getElementById('id_statut');
        const originalColor = statusSelect.style.backgroundColor;
        
        statusSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const statusCode = selectedOption.dataset.code;
            
            // Reset color
            this.style.backgroundColor = originalColor;
            this.style.borderColor = '';
            
            // Set color based on status
            switch(statusCode) {
                case 'ACT':
                    this.style.borderColor = 'var(--secondary)';
                    this.style.backgroundColor = 'rgba(46, 204, 113, 0.1)';
                    break;
                case 'BLE':
                    this.style.borderColor = 'var(--warning)';
                    this.style.backgroundColor = 'rgba(243, 158, 11, 0.1)';
                    break;
                case 'SUS':
                    this.style.borderColor = 'var(--danger)';
                    this.style.backgroundColor = 'rgba(231, 76, 60, 0.1)';
                    break;
                case 'ABS':
                    this.style.borderColor = 'var(--gray)';
                    this.style.backgroundColor = 'rgba(107, 114, 128, 0.1)';
                    break;
            }
        });
        
        // Trigger initial status color
        if (statusSelect.value) {
            statusSelect.dispatchEvent(new Event('change'));
        }
        
        // Calculate BMI if both weight and height are filled
        const tailleInput = document.getElementById('taille_cm');
        const poidsInput = document.getElementById('poids_kg');
        
        function calculateBMI() {
            if (tailleInput.value && poidsInput.value) {
                const tailleM = tailleInput.value / 100;
                const poids = poidsInput.value;
                const bmi = poids / (tailleM * tailleM);
                
                // Show BMI info
                const bmiDiv = document.createElement('div');
                bmiDiv.className = 'bmi-info';
                bmiDiv.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <i class="fas fa-heartbeat"></i>
                        <span style="font-size: 0.8rem; color: ${getBMIColor(bmi)}">
                            IMC: ${bmi.toFixed(1)} (${getBMICategory(bmi)})
                        </span>
                    </div>
                `;
                
                // Remove existing BMI info
                const existingBMI = poidsInput.parentElement.querySelector('.bmi-info');
                if (existingBMI) existingBMI.remove();
                
                poidsInput.parentElement.appendChild(bmiDiv);
            }
        }
        
        function getBMIColor(bmi) {
            if (bmi < 18.5) return '#3b82f6'; // Underweight - blue
            if (bmi < 25) return '#10b981'; // Normal - green
            if (bmi < 30) return '#f59e0b'; // Overweight - yellow
            return '#ef4444'; // Obese - red
        }
        
        function getBMICategory(bmi) {
            if (bmi < 18.5) return 'Maigreur';
            if (bmi < 25) return 'Normal';
            if (bmi < 30) return 'Surpoids';
            return 'Obésité';
        }
        
        tailleInput.addEventListener('input', calculateBMI);
        poidsInput.addEventListener('input', calculateBMI);
        
        // Age calculation from birth date
        const birthDateInput = document.getElementById('date_naissance');
        birthDateInput.addEventListener('change', function() {
            if (this.value) {
                const birthDate = new Date(this.value);
                const today = new Date();
                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();
                
                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }
                
                const ageDiv = document.createElement('div');
                ageDiv.className = 'age-info';
                ageDiv.innerHTML = `
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 0.5rem;">
                        <i class="fas fa-birthday-cake"></i>
                        <span style="font-size: 0.8rem; color: var(--primary)">
                            Âge: ${age} ans
                        </span>
                    </div>
                `;
                
                // Remove existing age info
                const existingAge = this.parentElement.querySelector('.age-info');
                if (existingAge) existingAge.remove();
                
                this.parentElement.appendChild(ageDiv);
            }
        });
        
        // Add shake animation CSS
        const style = document.createElement('style');
        style.textContent = `
            @keyframes shake {
                0%, 100% { transform: translateX(0); }
                10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
                20%, 40%, 60%, 80% { transform: translateX(5px); }
            }
            .shake {
                animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
            }
            .success-animation {
                animation: pulse 0.5s ease-in-out;
            }
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.05); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
        
        // Auto-focus first field
        document.getElementById('nom').focus();
    });
    </script>
</body>
</html>

<?php include __DIR__ . "/../includes/footer.php"; ?>