<!-- Vue: formulaire de modification de match -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Match - <?= htmlspecialchars($match["adversaire"]) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/modifier_match.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
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
                                   min="0" max="20"
                                   value="<?= $match["score_equipe"] ?? '' ?>"
                                   placeholder="0">
                        </div>
                        <span class="score-separator">-</span>
                        <div class="score-input">
                            <span class="score-label">Adversaire</span>
                            <input type="number"
                                   id="score_adverse"
                                   name="score_adverse"
                                   min="0" max="20"
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
                    <input type="text" class="form-control"
                           value="<?= $resultat_initial !== '' ? htmlspecialchars($resultat_initial) : 'Calculé automatiquement' ?>"
                           readonly>
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
