<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composition du Match - <?= htmlspecialchars($match['adversaire']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/feuille_match.css">
    <link rel="stylesheet" href="../assets/css/resultats.css">
    <link rel="stylesheet" href="../assets/css/voir_composition.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
</head>
<body>
    <div class="page-container">
        <!-- NOTIFICATION SI MATCH NON ÉVALUÉ -->
        <?php if ($match['etat'] === 'JOUE' && $statistiques['total_joueurs'] == 0): ?>
            <div class="match-notification">
                <i class="fas fa-exclamation-triangle"></i>
                <div>
                    <div class="notification-title">Ce match n'a pas encore été évalué</div>
                    <div class="notification-message">Cliquez sur le bouton "Évaluer les joueurs" ci-dessous pour noter les performances.</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- EN-TÊTE DU MATCH -->
        <div class="match-header">
            <div class="header-content">
                <div class="header-title">
                    <h1><i class="fas fa-clipboard-list"></i> Feuille de Match</h1>
                    <?php if ($match['resultat']): ?>
                        <span class="result-badge <?= $match['resultat'] ?>">
                            <i class="fas fa-<?= $match['resultat'] === 'VICTOIRE' ? 'trophy' : 
                                              ($match['resultat'] === 'DEFAITE' ? 'times' : 'equals') ?>"></i>
                            <?= $match['resultat'] ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($match['score_equipe'] !== null && $match['score_adverse'] !== null): ?>
                    <div class="match-score">
                        <div class="score-number"><?= $match['score_equipe'] ?></div>
                        <div class="score-divider">-</div>
                        <div class="score-number"><?= $match['score_adverse'] ?></div>
                    </div>
                <?php endif; ?>
                
                <div class="match-details">
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-flag"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Adversaire</div>
                            <div class="detail-value"><?= htmlspecialchars($match["adversaire"]) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Date et heure</div>
                            <div class="detail-value"><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Lieu</div>
                            <div class="detail-value"><?= $match["lieu"] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?></div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="detail-text">
                            <div class="detail-label">Joueurs</div>
                            <div class="detail-value"><?= count($titulaires) + count($remplacants) ?> / <?= $match['nb_joueurs'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES DU MATCH -->
        <?php if ($statistiques['total_joueurs'] > 0): ?>
            <div class="stats-section">
                <h2 class="stats-title"><i class="fas fa-chart-bar"></i> Statistiques du Match</h2>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($statistiques['moyenne_generale'] ?? 0, 1) ?></div>
                        <div class="stat-label">Note moyenne</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['excellent'] ?? 0 ?></div>
                        <div class="stat-label">Performances excellentes (≥ 4/5)</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['moyen'] ?? 0 ?></div>
                        <div class="stat-label">Performances moyennes (3/5)</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-number"><?= $statistiques['faible'] ?? 0 ?></div>
                        <div class="stat-label">Performances faibles (≤ 2/5)</div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- TERRAIN AVEC COMPOSITION -->
        <div class="pitch-section">
            <h2 class="section-title"><i class="fas fa-futbol"></i> Composition Titulaire</h2>
            
            <div class="pitch-container">
                <!-- Zone de but -->
                <div class="goal-area left"></div>
                <div class="goal-area right"></div>
                
                <!-- Groupes de joueurs -->
                <?php 
                // Ordre d'affichage des lignes
                $lignes = ['ATT' => 'Attaque', 'MIL' => 'Milieu', 'DEF' => 'Défense', 'GAR' => 'Gardien'];
                
                foreach ($lignes as $code => $label):
                    if (!empty($groupes[$code])):
                ?>
                    <div class="formation-line">
                        <span class="formation-label"><?= $label ?></span>
                        <?php foreach ($groupes[$code] as $joueur): 
                            $isBestPlayer = ($joueur['evaluation'] && $joueur['evaluation'] == $best_note && $best_note >= 4);
                        ?>
                            <div class="player-card titulaire <?= $isBestPlayer ? 'best-player' : '' ?>">
                                <?php if ($isBestPlayer): ?>
                                    <div class="best-player-badge">
                                        <i class="fas fa-crown"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="player-evaluation"><?= $joueur['evaluation'] ?></div>
                                <?php endif; ?>
                                <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                <div class="player-position"><?= htmlspecialchars($joueur['poste_libelle']) ?></div>
                                <div class="player-license"><?= htmlspecialchars($joueur['num_licence']) ?></div>
                                
                                <?php if ($joueur['evaluation']): ?>
                                    <div class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $joueur['evaluation'] ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">Note : <?= $joueur['evaluation'] ?>/5</div>
                                <?php else: ?>
                                    <div class="rating-text not-evaluated">Non évalué</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- BANC DES REMPLAÇANTS -->
        <?php if (!empty($remplacants)): ?>
            <div class="bench-section">
                <h2 class="section-title"><i class="fas fa-chair"></i> Remplaçants</h2>
                
                <div class="bench-container">
                    <?php foreach ($remplacants as $remplacant): ?>
                        <div class="bench-player">
                            <div class="bench-avatar">
                                <?= strtoupper(substr($remplacant['prenom'], 0, 1) . substr($remplacant['nom'], 0, 1)) ?>
                            </div>
                            <div class="bench-info">
                                <div class="bench-name"><?= htmlspecialchars($remplacant['prenom'] . ' ' . $remplacant['nom']) ?></div>
                                <div class="bench-details">
                                    <span><?= htmlspecialchars($remplacant['poste_libelle']) ?></span>
                                    <span><?= htmlspecialchars($remplacant['num_licence']) ?></span>
                                    <span class="bench-badge">Remplaçant</span>
                                </div>
                            </div>
                            <?php if ($remplacant['evaluation']): ?>
                                <div class="bench-evaluation">
                                    <i class="fas fa-star"></i> <?= $remplacant['evaluation'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- ACTIONS -->
        <div class="actions-section">
            <a href="../matchs/liste_matchs.php" class="btn-action btn-back">
                <i class="fas fa-arrow-left"></i> Retour aux matchs
            </a>
            
            <button class="btn-action btn-print">
                <i class="fas fa-print"></i> Imprimer la feuille
            </button>
            
            <?php if ($match['etat'] === 'JOUE'): ?>
                <a href="evaluation.php?id_match=<?= $id_match ?>" class="btn-action btn-eval">
                    <i class="fas fa-star"></i> Évaluer les joueurs
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php include "../includes/footer.php"; ?>
