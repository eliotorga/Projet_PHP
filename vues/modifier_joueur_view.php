<!-- Vue: formulaire de modification d'un joueur -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Joueur - <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/modifier_joueur.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
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
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement">
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
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement">
                        </div>

                        <div class="form-group">
                            <label for="num_licence" class="form-label required">Numéro de Licence</label>
                            <input type="text"
                                   id="num_licence"
                                   name="num_licence"
                                   class="form-control"
                                   value="<?= htmlspecialchars($joueur['num_licence']) ?>"
                                   required
                                   maxlength="20">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Format: LIC001 (LIC suivi de 3 chiffres)
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="date_naissance" class="form-label required">Date de Naissance</label>
                            <input type="date"
                                   id="date_naissance"
                                   name="date_naissance"
                                   class="form-control"
                                   value="<?= $joueur['date_naissance'] ?>"
                                   required
                                   min="1970-01-01"
                                   max="<?= date('Y-m-d', strtotime('-15 years')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="taille_cm" class="form-label required">Taille (cm)</label>
                            <input type="number"
                                   id="taille_cm"
                                   name="taille_cm"
                                   class="form-control"
                                   min="140"
                                   max="220"
                                   step="1"
                                   required
                                   value="<?= $joueur['taille_cm'] ?>">
                            <div class="form-help">
                                <i class="fas fa-ruler-vertical"></i>
                                Entre 140 et 220 cm
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="poids_kg" class="form-label required">Poids (kg)</label>
                            <input type="number"
                                   id="poids_kg"
                                   name="poids_kg"
                                   class="form-control"
                                   min="40"
                                   max="120"
                                   step="0.1"
                                   required
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

                <!-- Commentaires -->
                <div class="comments-card">
                    <h3 class="card-title"><i class="fas fa-comment-alt"></i> Commentaires</h3>

                    <?php if (isset($_SESSION["comment_success"])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div><?= htmlspecialchars($_SESSION["comment_success"]) ?></div>
                        </div>
                        <?php unset($_SESSION["comment_success"]); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION["comment_error"])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div><?= htmlspecialchars($_SESSION["comment_error"]) ?></div>
                        </div>
                        <?php unset($_SESSION["comment_error"]); ?>
                    <?php endif; ?>

                    <form method="POST" class="comment-form">
                        <input type="hidden" name="action" value="add_comment">
                        <label for="comment_texte" class="form-label">Ajouter un commentaire</label>
                        <textarea id="comment_texte" name="comment_texte" class="comment-textarea" rows="4" maxlength="500" required></textarea>
                        <div class="comment-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Ajouter
                            </button>
                        </div>
                    </form>

                    <div class="comments-list">
                        <?php if (!empty($commentaires)): ?>
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
                        <?php else: ?>
                            <div class="comment-item">
                                <div class="comment-text">Aucun commentaire pour ce joueur.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
