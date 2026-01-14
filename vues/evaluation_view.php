<!-- Vue: formulaire d'évaluation des joueurs -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation des Joueurs - <?= htmlspecialchars($match['adversaire']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/feuille_match.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/resultats.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/evaluation.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
</head>
<body>
    <div class="container">
        <!-- Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i>
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="match-info">
                    <h1><i class="fas fa-star"></i> Évaluation du Match</h1>
                    <div class="match-details">
                        <span class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-users"></i>
                            <?= htmlspecialchars($match["adversaire"]) ?>
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($match["lieu"]) ?>
                        </span>
                        <?php if ($match["score_equipe"] !== null): ?>
                            <span class="detail-item">
                                <i class="fas fa-futbol"></i>
                                <?= $match["score_equipe"] ?> - <?= $match["score_adverse"] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="match-stats">
                    <div class="stat-item">
                        <span class="stat-label">Joueurs</span>
                        <span class="stat-value"><?= $match["nb_participants"] ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">État actuel</span>
                        <span class="stat-value status-<?= strtolower($match["etat"]) ?>">
                            <?= htmlspecialchars($match["etat"]) ?>
                        </span>
                    </div>
                    <?php if ($match["moyenne_existante"]): ?>
                        <div class="stat-item">
                            <span class="stat-label">Moyenne existante</span>
                            <span class="stat-value"><?= $match["moyenne_existante"] ?>/5</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="post" id="evaluationForm">
            <!-- Résultat du Match -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-flag-checkered"></i> Résultat du Match</h2>

                <div class="score-inputs">
                    <div class="score-input">
                        <span>Notre équipe</span>
                        <input type="number" name="score_equipe" min="0" required
                               value="<?= htmlspecialchars($match['score_equipe'] ?? '') ?>"
                               placeholder="0">
                    </div>
                    <span class="score-separator">-</span>
                    <div class="score-input">
                        <input type="number" name="score_adverse" min="0" required
                               value="<?= htmlspecialchars($match['score_adverse'] ?? '') ?>"
                               placeholder="0">
                        <span><?= htmlspecialchars($match['adversaire']) ?></span>
                    </div>
                </div>

                <div class="result-buttons">
                    <input type="text" class="form-control" value="<?= htmlspecialchars($match['resultat'] ?? 'Calculé automatiquement') ?>" readonly>
                </div>
            </div>

            <!-- Évaluation des Joueurs -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-users"></i> Évaluation des Joueurs</h2>

                <div class="preview-stats">
                    <div class="preview-stat">
                        <i class="fas fa-user-check"></i>
                        <span><?= $nb_notes ?> joueurs notés</span>
                    </div>
                    <div class="preview-stat">
                        <i class="fas fa-star"></i>
                        <span>Moyenne : <?= $moyenne_calc ?>/5</span>
                    </div>
                </div>

                <div class="players-table-container">
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>Joueur</th>
                                <th>Poste</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Note (1 à 5)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td>
                                        <div class="player-name"><?= htmlspecialchars($p["prenom"] . " " . $p["nom"]) ?></div>
                                        <div class="player-licence">#<?= htmlspecialchars($p["num_licence"]) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars($p["poste"]) ?></td>
                                    <td>
                                        <span class="badge <?= $p["role"] === "TITULAIRE" ? "badge-titulaire" : "badge-remplacant" ?>">
                                            <?= $p["role"] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-statut-<?= $p["statut_code"] ?>">
                                            <?= htmlspecialchars($p["statut_libelle"]) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select name="evaluation[<?= $p["id_joueur"] ?>]" class="rating-select">
                                            <option value="">-- Note --</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= ($p["evaluation"] == $i) ? 'selected' : '' ?>>
                                                    <?= $i ?> - <?=
                                                        $i == 5 ? 'Excellent' :
                                                        ($i == 4 ? 'Bon' :
                                                        ($i == 3 ? 'Moyen' :
                                                        ($i == 2 ? 'Mauvais' : 'Très mauvais')))
                                                    ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="../matchs/liste_matchs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer les évaluations
                </button>
            </div>
        </form>
    </div>
</body>
</html>
