<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

// Récupération des statuts pour le <select>
$statuts = getAllStatuts($gestion_sportive);

$erreur = "";
$succes = "";

// Soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Nettoyage des données reçues
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => trim($_POST["num_licence"] ?? ""),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ? (int)$_POST["taille_cm"] : null,
        "poids_kg"       => $_POST["poids_kg"] ? (float)$_POST["poids_kg"] : null,
        "id_statut"      => $_POST["id_statut"] ? (int)$_POST["id_statut"] : null
    ];

    // Validation
    $errors = [];
    
    if (empty($data["nom"])) $errors[] = "Le nom est obligatoire";
    if (empty($data["prenom"])) $errors[] = "Le prénom est obligatoire";
    if (empty($data["id_statut"])) $errors[] = "Le statut est obligatoire";
    
    if (!empty($data["num_licence"])) {
        // Vérifier l'unicité du numéro de licence
        $stmt = $gestion_sportive->prepare("SELECT COUNT(*) FROM joueur WHERE num_licence = ?");
        $stmt->execute([$data["num_licence"]]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce numéro de licence existe déjà";
        }
    }
    
    if ($data["date_naissance"]) {
        $naissance = new DateTime($data["date_naissance"]);
        $aujourdhui = new DateTime();
        $age = $aujourdhui->diff($naissance)->y;
        if ($age < 6) $errors[] = "Le joueur doit avoir au moins 6 ans";
        if ($age > 60) $errors[] = "Veuillez vérifier la date de naissance";
    }
    
    if ($data["taille_cm"] && ($data["taille_cm"] < 100 || $data["taille_cm"] > 250)) {
        $errors[] = "La taille doit être entre 100 et 250 cm";
    }
    
    if ($data["poids_kg"] && ($data["poids_kg"] < 30 || $data["poids_kg"] > 150)) {
        $errors[] = "Le poids doit être entre 30 et 150 kg";
    }

    if (empty($errors)) {
        try {
            insertPlayer($gestion_sportive, $data);
            $_SESSION['success_message'] = "✅ Joueur ajouté avec succès !";
            header("Location: liste_joueurs.php");
            exit; // IMPORTANT : arrêter l'exécution après la redirection
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'ajout du joueur : " . $e->getMessage();
        }
    } else {
        $erreur = implode("<br>", $errors);
    }
}

// INCLURE LE HEADER APRÈS TOUS LES TRAITEMENTS PHP QUI PEUVENT UTILISER header()
include __DIR__ . "/../includes/header.php";
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Joueur</title>
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
    }

    /* =============================
       CARTE DU FORMULAIRE
    ============================= */
    .form-card {
        background: white;
        border-radius: var(--radius);
        padding: 35px;
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
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 25px;
    }

    .form-group {
        margin-bottom: 25px;
    }

    .form-label {
        display: block;
        margin-bottom: 10px;
        font-weight: 600;
        color: var(--dark);
        font-size: 1rem;
    }

    .form-label .required {
        color: var(--danger);
    }

    .form-input,
    .form-select {
        width: 100%;
        padding: 14px 18px;
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
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .form-hint {
        font-size: 0.85rem;
        color: var(--gray);
        margin-top: 8px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    /* =============================
       INFO-BULLE STATUTS
    ============================= */
    .status-info {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 15px;
        margin-bottom: 25px;
    }

    .status-badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .status-actif { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
    .status-blesse { background: linear-gradient(135deg, #fff8e1, #ffe082); color: #f57c00; }
    .status-suspendu { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }
    .status-absent { background: linear-gradient(135deg, #eceff1, #cfd8dc); color: #455a64; }

    /* =============================
       PRÉVISUALISATION
    ============================= */
    .preview-card {
        background: linear-gradient(135deg, #f8fafc, #f1f5f9);
        border-radius: var(--radius);
        padding: 25px;
        margin-top: 30px;
        border: 2px dashed #e0e6ed;
        display: none;
    }

    .preview-title {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 20px;
        color: var(--dark);
    }

    .preview-content {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 15px;
    }

    .preview-item {
        padding: 12px;
        background: white;
        border-radius: 8px;
        border: 1px solid #e0e6ed;
    }

    .preview-label {
        font-size: 0.85rem;
        color: var(--gray);
        margin-bottom: 5px;
    }

    .preview-value {
        font-weight: 600;
        color: var(--dark);
    }

    /* =============================
       BOUTONS
    ============================= */
    .form-actions {
        display: flex;
        gap: 15px;
        margin-top: 30px;
        padding-top: 25px;
        border-top: 1px solid #e0e6ed;
        flex-wrap: wrap;
    }

    .btn {
        padding: 15px 30px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 1rem;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        cursor: pointer;
        border: none;
    }

    .btn-submit {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
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
            padding: 20px;
        }
        
        .header-title h1 {
            font-size: 1.8rem;
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
                    <i class="fas fa-user-plus"></i>
                    <h1>Ajouter un Nouveau Joueur</h1>
                </div>
                <div class="header-subtitle">
                    Complétez les informations pour intégrer un nouveau joueur à l'effectif
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <?php if ($erreur): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <div><?= $erreur ?></div>
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
            <form method="POST" id="playerForm" novalidate>
                <div class="form-grid">
                    <!-- NOM ET PRÉNOM -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> Nom <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="text" 
                                   name="nom" 
                                   class="form-input"
                                   placeholder="Ex: Dupont"
                                   required
                                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        <div class="validation-feedback" id="nom-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-card"></i> Prénom <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="text" 
                                   name="prenom" 
                                   class="form-input"
                                   placeholder="Ex: Jean"
                                   required
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                        <div class="validation-feedback" id="prenom-feedback"></div>
                    </div>

                    <!-- NUMÉRO DE LICENCE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-badge"></i> Numéro de licence
                        </label>
                        <div class="input-with-icon">
                            <input type="text" 
                                   name="num_licence" 
                                   class="form-input"
                                   placeholder="Ex: LIC001"
                                   value="<?= htmlspecialchars($_POST['num_licence'] ?? '') ?>"
                                   pattern="[A-Za-z0-9]+">
                            <i class="fas fa-barcode input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Facultatif, mais doit être unique
                        </div>
                        <div class="validation-feedback" id="licence-feedback"></div>
                    </div>

                    <!-- DATE DE NAISSANCE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-birthday-cake"></i> Date de naissance
                        </label>
                        <div class="input-with-icon">
                            <input type="date" 
                                   name="date_naissance" 
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>"
                                   max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                                   min="<?= date('Y-m-d', strtotime('-60 years')) ?>">
                            <i class="fas fa-calendar-alt input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Le joueur doit avoir au moins 6 ans
                        </div>
                        <div class="validation-feedback" id="naissance-feedback"></div>
                    </div>

                    <!-- TAILLE ET POIDS -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-ruler-vertical"></i> Taille (cm)
                        </label>
                        <div class="input-with-icon">
                            <input type="number" 
                                   name="taille_cm" 
                                   class="form-input"
                                   placeholder="Ex: 180"
                                   min="100"
                                   max="250"
                                   value="<?= htmlspecialchars($_POST['taille_cm'] ?? '') ?>">
                            <i class="fas fa-ruler input-icon"></i>
                        </div>
                        <div class="validation-feedback" id="taille-feedback"></div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-weight"></i> Poids (kg)
                        </label>
                        <div class="input-with-icon">
                            <input type="number" 
                                   name="poids_kg" 
                                   class="form-input"
                                   placeholder="Ex: 75.5"
                                   min="30"
                                   max="150"
                                   step="0.1"
                                   value="<?= htmlspecialchars($_POST['poids_kg'] ?? '') ?>">
                            <i class="fas fa-weight-hanging input-icon"></i>
                        </div>
                        <div class="validation-feedback" id="poids-feedback"></div>
                    </div>

                    <!-- STATUT -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user-check"></i> Statut <span class="required">*</span>
                        </label>
                        <select name="id_statut" class="form-select" required>
                            <option value="">-- Sélectionner un statut --</option>
                            <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s["id_statut"] ?>" 
                                    <?= ($_POST['id_statut'] ?? '') == $s["id_statut"] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s["libelle"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="validation-feedback" id="statut-feedback"></div>
                    </div>
                </div>

                <!-- LÉGENDE DES STATUTS -->
                <div class="status-info">
                    <?php foreach ($statuts as $s): 
                        $class = match($s['code']) {
                            'ACT' => 'status-actif',
                            'BLE' => 'status-blesse',
                            'SUS' => 'status-suspendu',
                            'ABS' => 'status-absent',
                            default => ''
                        };
                        $icon = match($s['code']) {
                            'ACT' => 'fa-check-circle',
                            'BLE' => 'fa-band-aid',
                            'SUS' => 'fa-ban',
                            'ABS' => 'fa-user-slash',
                            default => 'fa-user'
                        };
                    ?>
                        <div class="status-badge <?= $class ?>">
                            <i class="fas <?= $icon ?>"></i>
                            <?= htmlspecialchars($s["libelle"]) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ACTIONS -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Enregistrer le joueur
                    </button>
                    
                    <button type="reset" class="btn btn-reset">
                        <i class="fas fa-redo"></i> Réinitialiser
                    </button>
                    
                    <a href="liste_joueurs.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
    // =============================
    // VALIDATION SIMPLIFIÉE
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('playerForm');
        
        // Validation du formulaire
        form.addEventListener('submit', function(e) {
            let formIsValid = true;
            const requiredFields = form.querySelectorAll('[required]');
            
            // Réinitialiser les styles d'erreur
            document.querySelectorAll('.form-input, .form-select').forEach(input => {
                input.classList.remove('invalid');
            });
            
            // Valider les champs requis
            requiredFields.forEach(input => {
                if (!input.value.trim()) {
                    formIsValid = false;
                    input.classList.add('invalid');
                    
                    // Focus sur le premier champ invalide
                    if (formIsValid === false) {
                        input.focus();
                        formIsValid = null; // Pour ne pas focus sur tous les champs
                    }
                }
            });
            
            if (!formIsValid) {
                e.preventDefault();
                showNotification('Veuillez remplir tous les champs obligatoires', 'error');
            }
        });
        
        // Validation en temps réel pour les champs requis
        const requiredInputs = form.querySelectorAll('[required]');
        requiredInputs.forEach(input => {
            input.addEventListener('blur', function() {
                if (!this.value.trim()) {
                    this.classList.add('invalid');
                } else {
                    this.classList.remove('invalid');
                }
            });
        });
    });
    
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
            background: ${type === 'error' ? '#e74c3c' : 
                        type === 'success' ? '#2ecc71' : 
                        '#3498db'};
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
                    type === 'success' ? 'fa-check-circle' : 'fa-info-circle';
        
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
    }
    
    // Ajouter les animations CSS
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
        .invalid {
            border-color: #e74c3c !important;
            box-shadow: 0 0 0 3px rgba(231, 76, 60, 0.1) !important;
        }
    `;
    document.head.appendChild(style);
    </script>
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>