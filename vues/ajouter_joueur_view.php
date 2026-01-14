<!-- Vue: formulaire d'ajout d'un joueur -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un Joueur</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/ajouter_joueur.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
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
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement"
                                value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                            <i class="fas fa-user input-icon"></i>
                        </div>
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
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement"
                                value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                            <i class="fas fa-user input-icon"></i>
                        </div>
                    </div>

                    <!-- NUMÉRO DE LICENCE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-id-badge"></i> Numéro de licence <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="text"
                                   name="num_licence"
                                   class="form-input"
                                   placeholder="Ex: LIC001"
                                   value="<?= htmlspecialchars($_POST['num_licence'] ?? '') ?>"
                                   required
                                   pattern="[A-Za-z0-9]+">
                            <i class="fas fa-barcode input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Obligatoire, et doit être unique
                        </div>
                    </div>

                    <!-- DATE DE NAISSANCE -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-birthday-cake"></i> Date de naissance <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="date"
                                   name="date_naissance"
                                   class="form-input"
                                   value="<?= htmlspecialchars($_POST['date_naissance'] ?? '') ?>"
                                   required
                                   max="<?= date('Y-m-d', strtotime('-6 years')) ?>"
                                   min="<?= date('Y-m-d', strtotime('-60 years')) ?>">
                            <i class="fas fa-calendar-alt input-icon"></i>
                        </div>
                        <div class="form-hint">
                            <i class="fas fa-info-circle"></i> Le joueur doit avoir au moins 6 ans
                        </div>
                    </div>

                    <!-- TAILLE ET POIDS -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-ruler-vertical"></i> Taille (cm) <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="number"
                                   name="taille_cm"
                                   class="form-input"
                                   placeholder="Ex: 180"
                                   min="100"
                                   max="250"
                                   required
                                   value="<?= htmlspecialchars($_POST['taille_cm'] ?? '') ?>">
                            <i class="fas fa-ruler input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-weight"></i> Poids (kg) <span class="required">*</span>
                        </label>
                        <div class="input-with-icon">
                            <input type="number"
                                   name="poids_kg"
                                   class="form-input"
                                   placeholder="Ex: 75.5"
                                   min="30"
                                   max="150"
                                   step="0.1"
                                   required
                                   value="<?= htmlspecialchars($_POST['poids_kg'] ?? '') ?>">
                            <i class="fas fa-weight-hanging input-icon"></i>
                        </div>
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
</body>
</html>
