<!-- Vue: page de suppression des matchs -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer des Matchs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/supprimer_match.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-trash-alt"></i>
                    <h1>Supprimer des Matchs</h1>
                </div>
                <div class="header-subtitle">
                    Sélectionnez les matchs que vous souhaitez supprimer. Cette action est irréversible.
                </div>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning); font-size: 1.2rem;"></i>
                    <div>
                        <strong>Attention :</strong> La suppression des matchs entraînera la perte des statistiques des joueurs.
                    </div>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <div class="message-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= $_SESSION['success_message'] ?></div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTROLS BAR -->
        <div class="controls-bar">
            <div class="stats-info">
                <div class="matches-count">
                    <i class="fas fa-calendar-alt"></i> <?= count($matchs) ?> match(s)
                </div>
                <div class="selection-info">
                    <span class="count"><?= $select_all ? count($matchs) : 0 ?></span> match(s) sélectionné(s)
                </div>
            </div>

            <div class="controls-buttons">
                <a href="?select_all=1" class="btn btn-select-all">
                    <i class="fas fa-check-square"></i> Tout sélectionner
                </a>
                <a href="?select_all=0" class="btn btn-deselect-all">
                    <i class="fas fa-square"></i> Tout désélectionner
                </a>
                <button type="submit" form="deleteForm" name="demander_confirmation" class="btn btn-delete-selected">
                    <i class="fas fa-trash-alt"></i> Supprimer la sélection
                </button>
                <a href="liste_matchs.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- TABLEAU DES MATCHS -->
        <?php if (!empty($matchs)): ?>
            <div class="matches-table-container">
                <div class="table-responsive">
                    <form method="POST" id="deleteForm">
                        <table class="matches-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell"></th>
                                    <th>Date & Heure</th>
                                    <th>Adversaire & Lieu</th>
                                    <th>Score</th>
                                    <th>Résultat</th>
                                    <th>État</th>
                                    <th>Statistiques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matchs as $match):
                                    $date_heure = new DateTime($match['date_heure']);
                                    $score_class = $match['resultat'] ?? '';
                                ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox"
                                                   name="matchs_selectionnes[]"
                                                   value="<?= $match['id_match'] ?>"
                                                   class="match-checkbox match-select"
                                                   <?= $select_all ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <div class="match-date"><?= $date_heure->format('d/m/Y') ?></div>
                                            <div class="match-time"><?= $date_heure->format('H:i') ?></div>
                                        </td>
                                        <td>
                                            <div class="match-adversaire"><?= htmlspecialchars($match['adversaire']) ?></div>
                                            <span class="match-lieu <?= $match['lieu'] ?>">
                                                <i class="fas <?= $match['lieu'] === 'DOMICILE' ? 'fa-home' : 'fa-plane' ?>"></i>
                                                <?= $match['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($match['score_equipe'] !== null): ?>
                                                <div class="match-score"><?= $match['score_equipe'] ?> - <?= $match['score_adverse'] ?></div>
                                            <?php else: ?>
                                                <div style="color: #95a5a6; font-style: italic;">À venir</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($match['resultat']): ?>
                                                <span class="resultat-badge <?= $score_class ?>"><?= $match['resultat'] ?></span>
                                            <?php else: ?>
                                                <span style="color: #95a5a6;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="etat-badge <?= $match['etat'] ?>"><?= $match['etat'] ?></span>
                                        </td>
                                        <td class="match-stats">
                                            <div class="stats-icons">
                                                <div class="stat-icon">
                                                    <i class="fas fa-users"></i>
                                                    <span><?= $match['nb_participants'] ?> joueurs</span>
                                                </div>
                                                <?php if ($match['note_moyenne']): ?>
                                                    <div class="stat-icon">
                                                        <i class="fas fa-star"></i>
                                                        <span><?= $match['note_moyenne'] ?>/5</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon"><i class="fas fa-calendar-times"></i></div>
                <h2 class="empty-title">Aucun match à supprimer</h2>
                <a href="liste_matchs.php" class="btn btn-cancel" style="margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DE CONFIRMATION -->
    <?php if ($show_confirmation): ?>
    <div class="confirmation-modal" style="display:flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h3>
            </div>
            <div class="modal-body">
                <p><strong>Attention : Cette action est irréversible !</strong></p>
                <p>Vous êtes sur le point de supprimer <?= count($matches_to_confirm) ?> match(s).</p>
                <div class="matches-to-delete">
                    <?php foreach ($matches_to_confirm as $m): ?>
                        <div class="match-to-delete">
                            <span><?= htmlspecialchars($m['adversaire']) ?> (<?= date("d/m/Y", strtotime($m['date_heure'])) ?>)</span>
                            <i class="fas fa-trash" style="color: var(--danger);"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <?php foreach ($matches_to_confirm as $m): ?>
                        <input type="hidden" name="ids_to_delete[]" value="<?= intval($m['id_match']) ?>">
                    <?php endforeach; ?>
                    <button type="submit" name="confirm_deletion" class="btn btn-modal-confirm">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
                    </button>
                </form>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-modal-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>
</html>
