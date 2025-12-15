<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statistiques de l'Équipe - Score d'Impact</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Lien vers votre fichier CSS -->
    <link rel="stylesheet" href="../assets/css/stats.css">s
</head>
<body>
    <div class="page-container">
        <!-- HEADER -->
        <div class="page-header">
            <h1><i class="fas fa-chart-bar"></i> Tableau de Bord Statistiques</h1>
            <p>Analyses détaillées des performances de l'équipe et des joueurs</p>
        </div>

        <!-- CARTE SCORE D'IMPACT -->
        <div class="impact-card">
            <div class="impact-header">
                <div class="impact-icon">
                    <i class="fas fa-bolt"></i>
                </div>
                <div class="impact-info">
                    <h3>Système de Score d'Impact</h3>
                    <p>Algorithme prédictif pour estimer les chances de gagner avec chaque joueur</p>
                </div>
            </div>
            <div class="score-container">
                <div class="score-circle">
                    <svg width="120" height="120" viewBox="0 0 120 120">
                        <circle class="circle-bg" cx="60" cy="60" r="50"></circle>
                        <circle class="circle-progress" cx="60" cy="60" r="50" 
                                style="stroke-dasharray: 314; stroke-dashoffset: <?= 314 - (314 * ($performance_moyenne * 100 / 600)) / 100 ?>; stroke: <?= 
                                    $performance_moyenne >= 4.5 ? '#2ecc71' : 
                                    ($performance_moyenne >= 3 ? '#f39c12' : '#e74c3c') ?>;">
                        </circle>
                    </svg>
                    <div class="circle-text">
                        <div class="circle-value"><?= number_format($performance_moyenne, 1) ?></div>
                        <div class="circle-label">Performance globale</div>
                    </div>
                </div>
                <div class="facteurs-container">
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Moyenne des évaluations</span>
                            <span class="facteur-valeur">30%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Pourcentage de victoires</span>
                            <span class="facteur-valeur">30%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Régularité (sélections consécutives)</span>
                            <span class="facteur-valeur">20%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Performance au poste</span>
                            <span class="facteur-valeur">10%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                    <div class="facteur-item">
                        <div class="facteur-label">
                            <span class="facteur-nom">Expérience (matchs joués)</span>
                            <span class="facteur-valeur">10%</span>
                        </div>
                        <div class="facteur-bar">
                            <div class="facteur-fill" style="width: 100%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TOP PERFORMERS -->
        <div class="stats-grid">
            <div class="top-players">
                <div class="stat-header">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h2 class="stat-title">Top Performers & Score d'Impact</h2>
                </div>
                
                <div class="player-ranking">
                    <?php if (!empty($top_performers)): ?>
                        <?php foreach ($top_performers as $index => $joueur): ?>
                            <div class="player-item">
                                <div class="player-rank"><?= $index + 1 ?></div>
                                <div class="player-info">
                                    <div class="player-name"><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                    <div class="player-stats">
                                        <span class="player-rating">
                                            <i class="fas fa-star"></i> <?= $joueur['moyenne'] ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-gamepad"></i> <?= $joueur['nb_matchs'] ?> matchs
                                        </span>
                                        <span class="player-impact">
                                            <i class="fas fa-bolt"></i> <?= $joueur['pourcentage_impact'] ?>%
                                        </span>
                                    </div>
                                </div>
                                <span class="badge <?= statutClass($joueur['statut']) ?>">
                                    <?= htmlspecialchars($joueur['statut']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="text-align: center; padding: 30px 0; opacity: 0.6;">
                            <i class="fas fa-user-slash" style="font-size: 2rem; margin-bottom: 10px;"></i>
                            <div>Aucune donnée de performance disponible</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- TABLEAU DES STATISTIQUES -->
        <div class="table-container">
            <div class="table-header">
                <h2><i class="fas fa-table"></i> Statistiques Détaillées par Joueur</h2>
            </div>
            
            <div class="info-bar">
                <div>
                    Affichage de <span class="count"><?= $joueurs_filtres_count ?></span> joueur(s) 
                    sur <span class="count"><?= $total_joueurs ?></span> au total
                </div>
                <?php if ($joueurs_filtres_count < $total_joueurs): ?>
                    <div>
                        <a href="?"><i class="fas fa-times"></i> Réinitialiser tous les filtres</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- FORMULAIRE DE FILTRAGE -->
            <div class="filters-container">
                <form method="GET" action="" class="filters-form" style="width: 100%; display: flex; flex-wrap: wrap; gap: 15px;">
                    <div class="filter-group">
                        <label class="filter-label">Filtrer par statut</label>
                        <select name="statut" class="filter-select">
                            <option value="">Tous les statuts</option>
                            <?php foreach ($joueurs_statut as $stat): ?>
                                <option value="<?= htmlspecialchars($stat['statut_code']) ?>"
                                    <?= ($filtre_statut == $stat['statut_code']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($stat['statut']) ?> (<?= $stat['nb_joueurs'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="flex: 1;">
                        <label class="filter-label">Rechercher un joueur</label>
                        <input type="text" name="recherche" class="search-input" 
                               placeholder="Nom, prénom..." 
                               value="<?= htmlspecialchars($recherche) ?>">
                    </div>
                    
                    <div class="filter-group">
                        <label class="filter-label">Trier par</label>
                        <select name="tri" class="filter-select">
                            <option value="nom" <?= ($tri == 'nom') ? 'selected' : '' ?>>Nom A-Z</option>
                            <option value="impact_desc" <?= ($tri == 'impact_desc') ? 'selected' : '' ?>>Score d'impact ▼</option>
                            <option value="impact_asc" <?= ($tri == 'impact_asc') ? 'selected' : '' ?>>Score d'impact ▲</option>
                            <option value="moyenne_desc" <?= ($tri == 'moyenne_desc') ? 'selected' : '' ?>>Note décroissante</option>
                            <option value="victoires_desc" <?= ($tri == 'victoires_desc') ? 'selected' : '' ?>>% victoires</option>
                            <option value="matchs_desc" <?= ($tri == 'matchs_desc') ? 'selected' : '' ?>>Matchs joués</option>
                            <option value="consecutifs_desc" <?= ($tri == 'consecutifs_desc') ? 'selected' : '' ?>>Forme actuelle</option>
                        </select>
                    </div>
                    
                    <div class="filter-group" style="align-self: flex-end;">
                        <button type="submit" class="filter-btn">
                            <i class="fas fa-filter"></i> Appliquer
                        </button>
                        <a href="?" class="filter-btn reset">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
            
            <!-- TABLEAU DES JOUEURS -->
            <table class="stats-table">
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Statut</th>
                        <th>Poste préféré</th>
                        <th>Moy. notes</th>
                        <th>% victoires</th>
                        <th>Score d'impact</th>
                        <th>Facteurs</th>
                        <th>Forme</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($joueurs_filtres)): ?>
                        <?php foreach ($joueurs_filtres as $j): 
                            $impactClass = $j['pourcentage_impact'] >= 70 ? 'impact-high' : 
                                         ($j['pourcentage_impact'] >= 40 ? 'impact-medium' : 'impact-low');
                        ?>
                        <tr>
                            <td>
                                <div class="player-cell">
                                    <div class="player-avatar">
                                        <?= strtoupper(substr($j['prenom'], 0, 1) . substr($j['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?></div>
                                        <div style="font-size: 0.85rem; color: var(--gray);">
                                            <?= $j['nb_matchs'] ?> match(s) 
                                            (<?= $j['titularisations'] ?>T / <?= $j['remplacements'] ?>R)
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <span class="badge <?= statutClass($j['statut']) ?>">
                                    <?= htmlspecialchars($j['statut']) ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if ($j['poste_prefere'] !== '—'): ?>
                                    <span class="poste-badge" style="background: <?= getPosteColor($j['poste_prefere']) ?>;">
                                        <?= htmlspecialchars($j['poste_prefere']) ?>
                                    </span>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <?php if ($j['moyenne_notes']): ?>
                                    <div class="rating-stars">
                                        <?php 
                                        $note = round($j['moyenne_notes']);
                                        for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $note ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--gray); margin-top: 3px;">
                                        <?= number_format($j['moyenne_notes'], 1) ?>/6
                                    </div>
                                <?php else: ?>
                                    <span style="opacity: 0.6;">—</span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div style="font-weight: 600; min-width: 50px; text-align: right;">
                                        <?= number_format($j['pct_victoires'], 1) ?>%
                                    </div>
                                    <div style="flex: 1;">
                                        <div style="height: 6px; background: #e0e6ed; border-radius: 3px; overflow: hidden;">
                                            <div style="height: 100%; width: <?= $j['pct_victoires'] ?>%; 
                                                     background: <?= $j['pct_victoires'] >= 50 ? 'var(--secondary)' : 'var(--danger)' ?>;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <div class="impact-indicator <?= $impactClass ?>">
                                        <i class="fas fa-bolt"></i>
                                        <?= $j['pourcentage_impact'] ?>%
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        (<?= number_format($j['score_impact'], 1) ?>/100)
                                    </div>
                                </div>
                            </td>
                            
                            <td>
                                <div style="font-size: 0.75rem; color: var(--gray); line-height: 1.4;">
                                    <?php if (isset($j['facteurs_impact']) && !empty($j['facteurs_impact'])): ?>
                                        <?php foreach ($j['facteurs_impact'] as $facteur => $valeur): ?>
                                            <div><?= ucfirst($facteur) ?>: <?= $valeur ?> pts</div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div style="opacity: 0.6;">Données insuffisantes</div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            
                            <td>
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    <div style="font-weight: 700; font-size: 1.2rem; color: <?= 
                                        $j['selections_consecutives'] >= 3 ? 'var(--secondary)' : 
                                        ($j['selections_consecutives'] >= 1 ? 'var(--accent)' : 'var(--gray)') ?>;">
                                        <?= $j['selections_consecutives'] ?>
                                    </div>
                                    <div style="font-size: 0.85rem; color: var(--gray);">
                                        consécutif(s)
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8">
                                <div class="no-results">
                                    <i class="fas fa-search"></i>
                                    <h3>Aucun joueur trouvé</h3>
                                    <p>
                                        Aucun joueur ne correspond à vos critères de recherche.
                                        <?php if (!empty($recherche) || !empty($filtre_statut)): ?>
                                            <br>
                                            <a href="?"><i class="fas fa-redo"></i> Réinitialiser les filtres</a>
                                        <?php endif; ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>