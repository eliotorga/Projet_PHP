<!-- Vue: formulaire d'ajout de match -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Planifier un Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/ajouter_match.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
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
                                   min="<?= date('Y-m-d\TH:i') ?>"
                                   max="<?= date('Y-m-d\TH:i', strtotime('+2 years')) ?>">
                            <i class="fas fa-clock input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Sélectionnez une date dans le futur
                        </div>
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
                    </div>

                    <!-- LIEU -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-map-marker-alt"></i> Lieu du Match <span class="required">*</span>
                        </label>
                        <div class="location-options">
                            <div class="location-option">
                                <input type="radio" name="lieu" id="lieu-domicile" value="DOMICILE"
                                    <?= ($_POST['lieu'] ?? '') == 'DOMICILE' ? 'checked' : '' ?> required>
                                <label class="location-label" for="lieu-domicile">
                                    <div class="location-icon"><i class="fas fa-home"></i></div>
                                    <div class="location-text">Domicile</div>
                                    <div class="location-desc">Match à notre stade</div>
                                </label>
                            </div>
                            <div class="location-option">
                                <input type="radio" name="lieu" id="lieu-exterieur" value="EXTERIEUR"
                                    <?= ($_POST['lieu'] ?? '') == 'EXTERIEUR' ? 'checked' : '' ?> required>
                                <label class="location-label" for="lieu-exterieur">
                                    <div class="location-icon"><i class="fas fa-road"></i></div>
                                    <div class="location-text">Extérieur</div>
                                    <div class="location-desc">Match à l'extérieur</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- ADRESSE -->
                    <div class="form-group" id="adresse-group">
                        <label class="form-label">
                            <i class="fas fa-map-pin"></i> Adresse du Match
                        </label>
                        <div class="input-with-icon">
                            <input type="text"
                                   name="adresse"
                                   class="form-input"
                                   placeholder="Ex: Stade de France, 75016 Paris"
                                   value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>"
                                   maxlength="255"
                                   id="adresse-input">
                            <i class="fas fa-map-marker-alt input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Obligatoire pour les matchs à l'extérieur
                        </div>
                    </div>
                </div>

                <!-- MATCHS À VENIR -->
                <?php if (!empty($upcoming_matches)): ?>
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
                                <div style="width: 40px; height: 40px; background: linear-gradient(135deg, var(--primary), var(--primary-dark)); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white;">
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
