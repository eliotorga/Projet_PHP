<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =====================
   RÉCUPÉRATION DES MATCHS AVEC STATISTIQUES ET FILTRES
===================== */

// Récupération des filtres
$filterEtat = $_GET['etat'] ?? 'all';
$filterResultat = $_GET['resultat'] ?? 'all';
$filterDate = $_GET['date'] ?? 'all';

// Construction de la requête
$sql = "
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        m.resultat,
        m.etat,
        m.score_equipe,
        m.score_adverse,
        COUNT(p.id_joueur) AS nb_joueurs,
        AVG(p.evaluation) AS moyenne_eval
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE 1=1
";

$params = [];

// Application des filtres
if ($filterEtat !== 'all') {
    $sql .= " AND m.etat = :etat";
    $params[':etat'] = $filterEtat;
}

if ($filterResultat !== 'all') {
    if ($filterResultat === 'null') {
         $sql .= " AND m.resultat IS NULL";
    } else {
        $sql .= " AND m.resultat = :resultat";
        $params[':resultat'] = $filterResultat;
    }
}

if ($filterDate !== 'all') {
    if ($filterDate === 'future') {
        $sql .= " AND m.date_heure > NOW()";
    } elseif ($filterDate === 'past') {
        $sql .= " AND m.date_heure <= NOW()";
    } elseif ($filterDate === 'month') {
        $sql .= " AND MONTH(m.date_heure) = MONTH(CURRENT_DATE()) AND YEAR(m.date_heure) = YEAR(CURRENT_DATE())";
    }
}

$sql .= "
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
";

$stmt = $gestion_sportive->prepare($sql);
$stmt->execute($params);


$matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Matchs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/liste_matchs.css">
</head>
<body>
    <div class="page-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-calendar-alt"></i> Calendrier des Matchs</h1>
                <p>Gérez vos matchs de la préparation à l'analyse post-match</p>
            </div>
            <a href="ajouter_match.php" class="btn-add-match">
                <i class="fas fa-plus-circle"></i> Nouveau Match
            </a>
            <a href="supprimer_match.php" class="btn-add-match" style="background: linear-gradient(135deg, #e74c3c, #c0392b);">
            <i class="fas fa-trash-alt"></i> Supprimer plusieurs
        </a>
        </div>

        <!-- FILTRES -->
        <form class="filters-container" method="GET" action="">
            <div class="filter-group">
                <label class="filter-label">Filtrer par état</label>
                <select class="filter-select" name="etat">
                    <option value="all" <?= $filterEtat === 'all' ? 'selected' : '' ?>>Tous les états</option>
                    <option value="A_PREPARER" <?= $filterEtat === 'A_PREPARER' ? 'selected' : '' ?>>À préparer</option>
                    <option value="PREPARE" <?= $filterEtat === 'PREPARE' ? 'selected' : '' ?>>Préparé</option>
                    <option value="JOUE" <?= $filterEtat === 'JOUE' ? 'selected' : '' ?>>Joué</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par résultat</label>
                <select class="filter-select" name="resultat">
                    <option value="all" <?= $filterResultat === 'all' ? 'selected' : '' ?>>Tous les résultats</option>
                    <option value="VICTOIRE" <?= $filterResultat === 'VICTOIRE' ? 'selected' : '' ?>>Victoires</option>
                    <option value="DEFAITE" <?= $filterResultat === 'DEFAITE' ? 'selected' : '' ?>>Défaites</option>
                    <option value="NUL" <?= $filterResultat === 'NUL' ? 'selected' : '' ?>>Nuls</option>
                    <option value="null" <?= $filterResultat === 'null' ? 'selected' : '' ?>>Non joué</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par date</label>
                <select class="filter-select" name="date">
                    <option value="all" <?= $filterDate === 'all' ? 'selected' : '' ?>>Toutes les dates</option>
                    <option value="future" <?= $filterDate === 'future' ? 'selected' : '' ?>>À venir</option>
                    <option value="past" <?= $filterDate === 'past' ? 'selected' : '' ?>>Passés</option>
                    <option value="month" <?= $filterDate === 'month' ? 'selected' : '' ?>>Ce mois-ci</option>
                </select>
            </div>
            
            <button type="submit" class="btn-filter">
                <i class="fas fa-filter"></i> Appliquer
            </button>
        </form>

        <!-- STATISTIQUES -->
        <?php
        // Calcul des statistiques
        $total_matchs = count($matchs);
        $matchs_joues = array_filter($matchs, fn($m) => $m['etat'] === 'JOUE');
        $victoires = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'VICTOIRE');
        $nuls = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'NUL');
        $defaites = array_filter($matchs_joues, fn($m) => $m['resultat'] === 'DEFAITE');
        $matchs_a_preparer = array_filter($matchs, fn($m) => $m['etat'] === 'A_PREPARER');
        $moyenne_eval = round(array_reduce($matchs_joues, function($carry, $m) {
            return $carry + ($m['moyenne_eval'] ?: 0);
        }, 0) / max(count($matchs_joues), 1), 1);
        ?>
        
        <div class="stats-container">
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-number"><?= $total_matchs ?></div>
                <div class="stat-label">Matchs au total</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-number"><?= count($matchs_a_preparer) ?></div>
                <div class="stat-label">À préparer</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-trophy"></i>
                </div>
                <div class="stat-number"><?= count($victoires) ?></div>
                <div class="stat-label">Victoires</div>
            </div>
            
            <div class="stat-item">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-number"><?= $moyenne_eval ?></div>
                <div class="stat-label">Moy. évaluation</div>
            </div>
        </div>

        <!-- LISTE DES MATCHS -->
        <div class="matches-grid">
            <?php $nowDt = new DateTimeImmutable('now'); ?>
            <?php foreach ($matchs as $m): 
                $dateMatchObj = new DateTimeImmutable($m["date_heure"]);
                $matchAVenir = $dateMatchObj > $nowDt;
                $matchPasse = $dateMatchObj <= $nowDt;
                $resultatSaisi = ($m['resultat'] !== null && $m['score_equipe'] !== null && $m['score_adverse'] !== null);
                $etatAffichage = $m['etat'];
                if ($matchPasse && !$resultatSaisi) {
                    $etatAffichage = 'EN_RETARD';
                }
                $etatLibelle = $etatAffichage === 'EN_RETARD' ? 'En retard' : str_replace("_", " ", $etatAffichage);
                $compositionComplete = ($m["nb_joueurs"] >= 11);
                $score = ($m['score_equipe'] !== null && $m['score_adverse'] !== null) ? 
                    $m['score_equipe'] . ' - ' . $m['score_adverse'] : ' - ';
            ?>
                <div class="match-card">
                    
                    <!-- EN-TÊTE DU MATCH -->
                    <div class="match-header">
                        <div class="match-date">
                            <div class="match-day"><?= $dateMatchObj->format("d") ?></div>
                            <div class="match-month"><?= $dateMatchObj->format("M") ?></div>
                        </div>
                        <div class="match-time">
                            <i class="fas fa-calendar-alt"></i> <?= $dateMatchObj->format('d/m/Y') ?>
                            <i class="fas fa-clock"></i> <?= $dateMatchObj->format('H:i') ?>
                        </div>
                    </div>
                    
                    <!-- CORPS DU MATCH -->
                    <div class="match-body">
                        <!-- ÉQUIPES ET SCORE -->
                        <div class="match-teams">
                            <div class="team-home">
                                <div class="team-logo">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="team-name">
                                    <?= $m['lieu'] === 'DOMICILE' ? 'Notre équipe' : htmlspecialchars($m['adversaire']) ?>
                                </div>
                            </div>
                            
                            <div class="match-score">
                                <?php if ($m['etat'] === 'JOUE' && $resultatSaisi): ?>
                                    <div class="score"><?= $score ?></div>
                                    <span class="match-status <?= $m['resultat'] ?>">
                                        <?= $m['resultat'] ?>
                                    </span>
                                <?php else: ?>
                                    <div class="score">-</div>
                                    <span class="match-status <?= $etatAffichage ?>">
                                        <?= $etatLibelle ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="team-away">
                                <div class="team-logo">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="team-name">
                                    <?= $m['lieu'] === 'EXTERIEUR' ? 'Notre équipe' : htmlspecialchars($m['adversaire']) ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- DÉTAILS -->
                        <div class="match-details">
                            <div class="detail-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <div>
                                    <div class="detail-label">Lieu</div>
                                    <div class="detail-value"><?= $m['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?></div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-users"></i>
                                <div>
                                    <div class="detail-label">Joueurs</div>
                                    <div class="detail-value"><?= $m['nb_joueurs'] ?>/11</div>
                                </div>
                            </div>
                            
                            <div class="detail-item">
                                <i class="fas fa-chart-line"></i>
                                <div>
                                    <div class="detail-label">État</div>
                                    <span class="etat-badge <?= $etatAffichage ?>">
                                        <i class="fas fa-<?= $etatAffichage === 'EN_RETARD' ? 'exclamation-triangle' : ($etatAffichage === 'A_PREPARER' ? 'clock' : ($etatAffichage === 'PREPARE' ? 'check-circle' : 'play-circle')) ?>"></i>
                                        <?= $etatLibelle ?>
                                    </span>
                                </div>
                            </div>

                            <?php if (!$matchAVenir && $m['etat'] !== 'JOUE'): ?>
                                <div class="detail-item match-overdue">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <div>
                                        <div class="detail-label">Match passé</div>
                                        <div class="detail-value">Résultat non saisi</div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($m['etat'] === 'JOUE' && $m['moyenne_eval']): ?>
                                <div class="detail-item">
                                    <i class="fas fa-star"></i>
                                    <div>
                                        <div class="detail-label">Évaluation</div>
                                        <div class="evaluation-stars">
                                            <?php 
                                            $note = round($m['moyenne_eval']);
                                            for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star star <?= $i <= $note ? 'filled' : '' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="evaluation-text">(<?= number_format($m['moyenne_eval'], 1) ?>)</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- ACTIONS -->
                    <div class="match-actions">
                        <?php if ($matchAVenir): ?>
                            <a href="../feuille_match/composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-compose">
                                <i class="fas fa-futbol"></i> Composer
                            </a>
                        <?php else: ?>
                            <?php if ($m['etat'] === 'A_PREPARER'): ?>
                                <a href="../feuille_match/composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-compose">
                                    <i class="fas fa-futbol"></i> Composer
                                </a>
                            <?php elseif ($m['etat'] === 'JOUE'): ?>
                                <?php if ((int)$m['nb_joueurs'] > 0): ?>
                                    <a href="../feuille_match/voir_composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-view">
                                        <i class="fas fa-eye"></i> Voir compo
                                    </a>

                                    <a href="../feuille_match/evaluation.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-eval">
                                        <i class="fas fa-star"></i> Évaluer
                                    </a>
                                <?php else: ?>
                                    <a href="../feuille_match/composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-compose">
                                        <i class="fas fa-futbol"></i> Composer
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="../feuille_match/voir_composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-view">
                                    <i class="fas fa-eye"></i> Voir
                                </a>

                                <?php if ((int)$m['nb_joueurs'] > 0): ?>
                                    <a href="../feuille_match/evaluation.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-eval">
                                        <i class="fas fa-star"></i> Évaluer
                                    </a>
                                <?php else: ?>
                                    <a href="../feuille_match/composition.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-compose">
                                        <i class="fas fa-futbol"></i> Composer
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($matchAVenir || $m['etat'] !== 'JOUE'): ?>
                            <a href="modifier_match.php?id_match=<?= $m['id_match'] ?>" class="btn-action btn-edit">
                                <i class="fas fa-cog"></i> Modifier
                            </a>
                        <?php endif; ?>

                        <a href="supprimer_match.php?id=<?= $m['id_match'] ?>" class="btn-action btn-delete">
                            <i class="fas fa-trash"></i> Supprimer
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- WORKFLOW -->
        <div class="workflow-container">
            <div class="workflow-title">
                <i class="fas fa-project-diagram"></i>
                <h2>Workflow de Gestion des Matchs</h2>
            </div>
            
            <div class="workflow-steps">
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-calendar-plus"></i>
                    </div>
                    <h3 class="step-title">1. Planification</h3>
                    <p class="step-desc">Ajouter un nouveau match dans le calendrier</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <h3 class="step-title">2. Composition</h3>
                    <p class="step-desc">Sélectionner les titulaires et remplaçants</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    <h3 class="step-title">3. Match</h3>
                    <p class="step-desc">Le match se joue, résultat enregistré</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3 class="step-title">4. Évaluation</h3>
                    <p class="step-desc">Noter les performances des joueurs</p>
                </div>
                
                <div class="workflow-step">
                    <div class="step-icon">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="step-title">5. Analyse</h3>
                    <p class="step-desc">Statistiques et analyses post-match</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php include "../includes/footer.php"; ?>