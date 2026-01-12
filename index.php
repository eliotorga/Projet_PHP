<?php
// page d'accueil du dashboard
// affiche les stats principales, le prochain match et les meilleurs joueurs

require_once "includes/auth_check.php";
require_once "includes/config.php";
require_once __DIR__ . "/bdd/db_joueur.php";
require_once __DIR__ . "/bdd/db_stats.php";

/* =====================
   DONNÉES DASHBOARD COMPLET
===================== */

/* STATISTIQUES PRINCIPALES */
// Joueurs actifs
$nbJoueursActifs = getActivePlayersCount($gestion_sportive);

// Matchs par statut
$stats_matchs = getMatchStatusCounts($gestion_sportive);

// Statistiques de victoires
$stats_victoires = getMatchResultCounts($gestion_sportive);

// Performance moyenne des joueurs
$performance_moyenne = getAverageEvaluationValue($gestion_sportive);
$performance_moyenne = $performance_moyenne !== null ? round($performance_moyenne, 1) : null;

// Joueurs blessés/suspendus
$joueurs_indisponibles = getUnavailablePlayersCount($gestion_sportive);

// Dernières activités
$dernieres_activites = getRecentMatchActivities($gestion_sportive, 3);

/* PROCHAIN MATCH */
$prochainMatch = getNextMatchSummary($gestion_sportive);

/* DERNIER MATCH */
$dernierMatch = getLastPlayedMatchSummary($gestion_sportive);

$css_version = @filemtime(__DIR__ . "/assets/css/index.css") ?: time();
$theme_version = @filemtime(__DIR__ . "/assets/css/theme.css") ?: time();

include "includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Équipe</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/index.css?v=<?= $css_version ?>">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css?v=<?= $theme_version ?>">
</head>
<body>
    <div class="dashboard-container">
        <!-- HERO SECTION -->
        <div class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h1><i class="fas fa-tachometer-alt"></i> Dashboard Entraîneur</h1>
                    <p>Pilotez votre équipe avec précision : matchs, joueurs, performances et statistiques en temps réel.</p>
                    <div class="hero-actions">
                        <?php if ($prochainMatch): ?>
                            <a class="btn-hero" href="feuille_match/composition.php?id_match=<?= $prochainMatch["id_match"] ?>">
                                <i class="fas fa-futbol"></i> Composer l'équipe
                            </a>
                            <a class="btn-hero btn-ghost" href="matchs/liste_matchs.php">
                                <i class="fas fa-calendar-alt"></i> Voir le calendrier
                            </a>
                        <?php else: ?>
                            <a class="btn-hero" href="matchs/ajouter_match.php">
                                <i class="fas fa-plus-circle"></i> Planifier un match
                            </a>
                            <a class="btn-hero btn-ghost" href="matchs/liste_matchs.php">
                                <i class="fas fa-calendar-alt"></i> Voir le calendrier
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $nbJoueursActifs ?></span>
                        <span class="hero-stat-label">Joueurs Actifs</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats_matchs['joues'] ?? 0 ?></span>
                        <span class="hero-stat-label">Matchs Joués</span>
                    </div>
                    <div class="hero-stat">
                        <span class="hero-stat-number"><?= $stats_victoires['victoires'] ?? 0 ?></span>
                        <span class="hero-stat-label">Victoires</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- GRID PRINCIPAL -->
        <div class="dashboard-grid">
            <!-- COLONNE GAUCHE -->
            <div class="left-column">
                <!-- STATISTIQUES RAPIDES -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number"><?= $joueurs_indisponibles ?></div>
                        </div>
                        <div class="stat-label">Joueurs indisponibles</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="stat-number">
                                <?= $stats_victoires['total'] > 0 ? 
                                    round(($stats_victoires['victoires'] / $stats_victoires['total']) * 100) : 0 ?>%
                            </div>
                        </div>
                        <div class="stat-label">Taux de victoire</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-number"><?= $performance_moyenne ?: '0.0' ?></div>
                        </div>
                        <div class="stat-label">Performance moyenne</div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <div class="stat-icon">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-number"><?= $stats_matchs['a_venir'] ?? 0 ?></div>
                        </div>
                        <div class="stat-label">Matchs à venir</div>
                    </div>
                </div>

                <!-- PROCHAIN MATCH -->
                <div class="action-card-large">
                    <div class="action-content">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div>
                                <div class="action-title">Prochain Match</div>
                                <div class="action-subtitle">Préparez votre équipe</div>
                            </div>
                        </div>
                        
                        <?php if ($prochainMatch): ?>
                            <div class="action-details">
                                <div class="match-info">
                                    <i class="fas fa-flag"></i>
                                    <div>
                                        <div class="match-title"><?= htmlspecialchars($prochainMatch["adversaire"]) ?></div>
                                        <div class="match-subtitle"><?= $prochainMatch['lieu'] === 'DOMICILE' ? 'Match à domicile' : 'Match à l\'extérieur' ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-calendar-alt"></i>
                                    <div>
                                        <div class="match-date"><?= date("l d F Y", strtotime($prochainMatch["date_heure"])) ?></div>
                                        <div class="match-time">À <?= date("H:i", strtotime($prochainMatch["date_heure"])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-users"></i>
                                    <div>
                                        <div class="match-meta"><?= $prochainMatch['nb_joueurs'] ?> joueurs sélectionnés</div>
                                        <div class="match-subtitle"><?= 11 - $prochainMatch['nb_joueurs'] ?> places restantes</div>
                                    </div>
                                </div>
                            </div>
                            
                            <a href="feuille_match/composition.php?id_match=<?= $prochainMatch["id_match"] ?>" 
                               class="btn-action">
                                <i class="fas fa-futbol"></i> Composer l'équipe
                            </a>
                        <?php else: ?>
                            <div class="action-details empty-state">
                                <i class="fas fa-calendar-plus empty-icon"></i>
                                <div class="empty-title">Aucun match à venir</div>
                                <p class="empty-text">Planifiez un nouveau match pour commencer la préparation.</p>
                                <a href="matchs/ajouter_match.php" class="btn-action">
                                    <i class="fas fa-plus-circle"></i> Ajouter un match
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- DERNIER MATCH -->
                <div class="action-card-large">
                    <div class="action-content">
                        <div class="action-header">
                            <div class="action-icon">
                                <i class="fas fa-history"></i>
                            </div>
                            <div>
                                <div class="action-title">Dernier Match</div>
                                <div class="action-subtitle">Analyse et évaluation</div>
                            </div>
                        </div>
                        
                        <?php if ($dernierMatch): ?>
                            <?php
                                $result_class = $dernierMatch['resultat'] === 'VICTOIRE' ? 'result--win' :
                                    ($dernierMatch['resultat'] === 'NUL' ? 'result--draw' : 'result--loss');
                            ?>
                            <div class="action-details">
                                <div class="match-info">
                                    <i class="fas fa-flag"></i>
                                    <div>
                                        <div class="match-title"><?= htmlspecialchars($dernierMatch["adversaire"]) ?></div>
                                        <div class="match-subtitle"><?= date("d/m/Y H:i", strtotime($dernierMatch["date_heure"])) ?></div>
                                    </div>
                                </div>
                                
                                <div class="match-info">
                                    <i class="fas fa-chart-line"></i>
                                    <div class="match-grow">
                                        <div class="result-text">Résultat : 
                                            <span class="result-tag <?= $result_class ?>"><?= $dernierMatch['resultat'] ?></span>
                                        </div>
                                        <?php if ($dernierMatch['score_equipe'] !== null): ?>
                                            <div class="match-subtitle">Score : <?= $dernierMatch['score_equipe'] ?> - <?= $dernierMatch['score_adverse'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <?php if ($dernierMatch['moyenne_eval']): ?>
                                    <div class="match-info">
                                        <i class="fas fa-star"></i>
                                        <div>
                                            <div class="match-meta">Évaluation moyenne : <?= $dernierMatch['moyenne_eval'] ?>/5</div>
                                            <div class="match-subtitle">Performance de l'équipe</div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="action-cta-row">
                                <a href="feuille_match/evaluation.php?id_match=<?= $dernierMatch["id_match"] ?>" 
                                   class="btn-action btn-warning">
                                    <i class="fas fa-star"></i> Évaluer les joueurs
                                </a>
                                <a href="feuille_match/voir_composition.php?id_match=<?= $dernierMatch["id_match"] ?>" 
                                   class="btn-action">
                                    <i class="fas fa-eye"></i> Voir la composition
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="action-details empty-state">
                                <i class="fas fa-futbol empty-icon"></i>
                                <div class="empty-title">Aucun match joué</div>
                                <p class="empty-text">Les statistiques s'afficheront après votre premier match.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- COLONNE DROITE -->
            <div class="right-column">
                <!-- ACTIVITÉS RÉCENTES -->
                <div class="activities-card activities-card--spaced">
                    <div class="activities-header">
                        <i class="fas fa-history"></i>
                        <h3>Activités Récentes</h3>
                    </div>
                    
                    <div class="activity-list">
                        <?php if (!empty($dernieres_activites)): ?>
                            <?php foreach ($dernieres_activites as $activite): ?>
                                <?php
                                    $activity_class = $activite['resultat'] === 'VICTOIRE' ? 'result--win' :
                                        ($activite['resultat'] === 'NUL' ? 'result--draw' : 'result--loss');
                                ?>
                                <div class="activity-item">
                                    <div class="activity-icon">
                                        <i class="fas fa-futbol"></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-title"><?= htmlspecialchars($activite['adversaire']) ?></div>
                                        <div class="activity-meta">
                                            <span><?= date("d/m/Y", strtotime($activite['date_heure'])) ?></span>
                                            <span class="result-tag <?= $activity_class ?>"><?= $activite['resultat'] ?></span>
                                            <span><?= $activite['nb_participants'] ?> joueurs</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state empty-state--compact">
                                <i class="fas fa-calendar-times empty-icon empty-icon--compact"></i>
                                <div class="empty-title empty-title--compact">Aucune activité récente</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- RACCOURCIS RAPIDES -->
        <div class="quick-actions">
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-users"></i>
                </div>
                <div class="quick-title">Effectif</div>
                <div class="quick-desc">Gérez votre liste de joueurs et leurs statuts</div>
                <a href="joueurs/liste_joueurs.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div class="quick-title">Calendrier</div>
                <div class="quick-desc">Planifiez et gérez tous vos matchs</div>
                <a href="matchs/liste_matchs.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="quick-title">Statistiques</div>
                <div class="quick-desc">Analyses détaillées et performances</div>
                <a href="stats/stats_equipe.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
            
            <div class="quick-action-card">
                <div class="quick-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="quick-title">Compositions</div>
                <div class="quick-desc">Historique des compositions d'équipe</div>
                <a href="feuille_match/historique_feuille.php" class="btn-quick">
                    <i class="fas fa-arrow-right"></i> Accéder
                </a>
            </div>
        </div>
    </div>

    <?php if ($prochainMatch && (strtotime($prochainMatch['date_heure']) - time()) < 86400): ?>
        <div class="toast-warning">
            <i class="fas fa-exclamation-triangle"></i>
        </div>
    <?php endif; ?>
</body>
</html>
<?php include "includes/footer.php"; ?>
