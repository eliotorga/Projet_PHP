<?php
// permet de planifier un nouveau match dans le calendrier
// formulaire avec validation de date, adversaire, lieu et adresse

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
        "lieu"       => $_POST["lieu"] ?? "",
        "adresse"     => trim($_POST["adresse"] ?? "")
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
    
    // Validation adresse (uniquement pour les matchs à l'extérieur)
    if ($data["lieu"] === 'EXTERIEUR' && empty($data["adresse"])) {
        $errors[] = "L'adresse est obligatoire pour les matchs à l'extérieur";
    } elseif (!empty($data["adresse"]) && strlen($data["adresse"]) > 255) {
        $errors[] = "L'adresse ne peut pas dépasser 255 caractères";
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
    <link rel="stylesheet" href="/Projet_PHP/assets/css/ajouter_match.css">
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

                    <!-- ADRESSE (uniquement pour les matchs à l'extérieur) -->
                    <?php if (($_POST['lieu'] ?? '') == 'EXTERIEUR' || (!isset($_POST['lieu']) && isset($_GET['lieu']) && $_GET['lieu'] == 'EXTERIEUR') || (!isset($_POST['lieu']) && !isset($_GET['lieu']))): ?>
                    <div class="form-group" id="adresse-group">
                        <label class="form-label">
                            <i class="fas fa-map-pin"></i> Adresse du Match <span class="required">*</span>
                        </label>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Adresse complète (uniquement pour les matchs à l'extérieur)
                        </div>
                        <div class="input-with-icon">
                            <input type="text" 
                                   name="adresse" 
                                   class="form-input"
                                   placeholder="Ex: Stade de France, 75016 Paris"
                                   value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>"
                                   maxlength="255"
                                   required
                                   id="adresse-input">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                        <div class="validation-feedback" id="adresse-feedback"></div>
                    </div>
                    <?php endif; ?>
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
                            <div class="preview-value" id="preview-date">
                                <?php 
                                if (!empty($_POST['date_heure'])) {
                                    $date = new DateTime($_POST['date_heure']);
                                    echo $date->format('d F Y');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-clock"></i> Heure
                            </div>
                            <div class="preview-value" id="preview-time">
                                <?php 
                                if (!empty($_POST['date_heure'])) {
                                    $date = new DateTime($_POST['date_heure']);
                                    echo $date->format('H:i');
                                } else {
                                    echo '-';
                                }
                                ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-flag"></i> Adversaire
                            </div>
                            <div class="preview-value" id="preview-adversaire">
                                <?= htmlspecialchars($_POST['adversaire'] ?? '-') ?>
                            </div>
                        </div>
                        <div class="preview-item">
                            <div class="preview-label">
                                <i class="fas fa-map-marker-alt"></i> Lieu
                            </div>
                            <div class="preview-value" id="preview-lieu">
                                <?= ($_POST['lieu'] ?? '') == 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>
                            </div>
                        </div>
                        <?php if (($_POST['lieu'] ?? '') == 'EXTERIEUR'): ?>
                        <div class="preview-item" id="preview-adresse-item">
                            <div class="preview-label">
                                <i class="fas fa-map-pin"></i> Adresse
                            </div>
                            <div class="preview-value" id="preview-adresse">
                                <?= htmlspecialchars($_POST['adresse'] ?? '-') ?>
                            </div>
                        </div>
                        <?php endif; ?>
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
                    
                    <button type="reset" class="btn btn-reset">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                    
                    <a href="liste_matchs.php" class="btn btn-cancel">
                        <i class="fas fa-times"></i> Annuler
                    </a>
                </div>
            </form>
        </div>
    </div>

    
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>
