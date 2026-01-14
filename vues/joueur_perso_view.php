<!-- Vue: affichage du profil détaillé d'un joueur -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Joueur - <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/joueur_perso.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
</head>
<body>
    <div class="container">
        <!-- BOUTON RETOUR -->
        <div class="header-back">
            <a href="liste_joueurs.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>

        <!-- PROFIL DU JOUEUR -->
        <div class="player-profile">
            <div class="profile-header">
                <div class="player-photo">
                    <i class="fas fa-user"></i>
                </div>
                <div class="player-info">
                    <h1><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></h1>
                    <div class="player-details">
                        <span class="detail-item">
                            <i class="fas fa-hashtag"></i>
                            <?= htmlspecialchars($joueur['num_licence']) ?>
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            Né le <?= date('d/m/Y', strtotime($joueur['date_naissance'])) ?> (<?= $joueur['age'] ?> ans)
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-ruler-vertical"></i>
                            <?= $joueur['taille_cm'] ?> cm
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-weight"></i>
                            <?= $joueur['poids_kg'] ?> kg
                        </span>
                        <span class="detail-item badge badge-statut-<?= $joueur['statut_code'] ?>">
                            <i class="fas fa-<?=
                                $joueur['statut_code'] == 'ACT' ? 'check-circle' :
                                ($joueur['statut_code'] == 'BLE' ? 'band-aid' :
                                ($joueur['statut_code'] == 'SUS' ? 'ban' : 'user-slash'))
                            ?>"></i>
                            <?= htmlspecialchars($joueur['statut_libelle']) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="stats-grid">
            <div class="stat-card matchs">
                <div class="stat-icon">
                    <i class="fas fa-futbol"></i>
                </div>
                <div class="stat-value stat-matchs">
                    <?= $stats['total_matchs'] ?: 0 ?>
                </div>
                <div class="stat-label">Matchs joués</div>
            </div>

            <div class="stat-card evaluation">
                <div class="stat-icon">
                    <i class="fas fa-star"></i>
                </div>
                <div class="stat-value stat-evaluation">
                    <?= number_format($stats['moyenne_evaluation'] ?: 0, 1) ?>
                    <small>/5</small>
                </div>
                <div class="stat-label">Moyenne d'évaluation</div>
            </div>

            <div class="stat-card titulaire">
                <div class="stat-icon">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-value stat-titulaire">
                    <?= $stats['matchs_titulaire'] ?: 0 ?>
                </div>
                <div class="stat-label">Matchs titulaire</div>
            </div>

            <div class="stat-card postes">
                <div class="stat-icon">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-value">
                    <?= count($postes) ?>
                </div>
                <div class="stat-label">Postes différents</div>
            </div>
        </div>

        <!-- HISTORIQUE DES MATCHS -->
        <div class="matchs-container">
            <div class="section-title">
                <i class="fas fa-history"></i>
                <h2>Historique des Matchs</h2>
                <span class="badge" style="background: #e3f2fd; color: #1565c0;">
                    <?= count($matchs_joueur) ?> match(s)
                </span>
            </div>

            <?php if (!empty($matchs_joueur)): ?>
                <div class="matchs-list">
                    <?php foreach ($matchs_joueur as $match):
                        $date_match = new DateTime($match['date_heure']);
                        $formatted_date = $date_match->format('d/m/Y');
                        $formatted_time = $date_match->format('H:i');

                        $resultat_texte = '';
                        if ($match['resultat']) {
                            $resultat_texte = ' - ' . $match['resultat'];
                        }
                    ?>
                        <div class="match-item <?= $match['resultat_class'] ?>">
                            <div class="match-date">
                                <?= $formatted_date ?><br>
                                <small><?= $formatted_time ?></small>
                            </div>
                            <div class="match-info">
                                <div class="match-adversaire">
                                    <?= htmlspecialchars($match['adversaire']) ?>
                                    <small style="color: <?= $match['lieu'] === 'DOMICILE' ? '#2e7d32' : '#1565c0' ?>;">
                                        (<?= $match['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>)
                                    </small>
                                </div>
                                <div class="match-details">
                                    <span>
                                        <i class="fas fa-tshirt"></i>
                                        <?= $match['poste_libelle'] ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-user-tag"></i>
                                        <?= $match['role'] ?>
                                    </span>
                                    <?php if ($match['score_equipe'] !== null): ?>
                                        <span>
                                            <i class="fas fa-futbol"></i>
                                            <?= $match['score_equipe'] ?>-<?= $match['score_adverse'] ?>
                                            <?= $resultat_texte ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if ($match['evaluation']): ?>
                                        <span class="match-evaluation">
                                            <i class="fas fa-star"></i>
                                            <?= $match['evaluation'] ?>/5
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 40px; color: var(--gray);">
                    <i class="fas fa-futbol" style="font-size: 3rem; margin-bottom: 15px; opacity: 0.3;"></i>
                    <p>Aucun match joué pour le moment.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- POSTES PRINCIPAUX -->
        <?php if (!empty($postes)): ?>
        <div class="matchs-container">
            <div class="section-title">
                <i class="fas fa-map-marker-alt"></i>
                <h2>Répartition des Postes</h2>
            </div>
            <div class="postes-container">
                <?php
                $total_matchs = $stats['total_matchs'] ?: 1;
                foreach ($postes as $poste):
                    $pourcentage = round(($poste['nb_matchs'] / $total_matchs) * 100);
                ?>
                    <div class="poste-bar">
                        <div class="poste-header">
                            <span class="poste-label"><?= $poste['poste'] ?></span>
                            <span class="poste-count">
                                <?= $poste['nb_matchs'] ?> matchs
                                <?php if ($poste['moyenne_eval']): ?>
                                    - Moy: <?= number_format($poste['moyenne_eval'], 1) ?>/5
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="poste-progress">
                            <div class="poste-progress-fill" style="width: <?= $pourcentage ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- COMMENTAIRES -->
        <?php if (!empty($commentaires)): ?>
        <div class="comments-container">
            <div class="section-title">
                <i class="fas fa-comment-alt"></i>
                <h2>Commentaires</h2>
                <span class="badge" style="background: #e3f2fd; color: #1565c0;">
                    <?= count($commentaires) ?> commentaire(s)
                </span>
            </div>

            <?php foreach ($commentaires as $commentaire):
                $date_comment = new DateTime($commentaire['date_commentaire']);
            ?>
                <div class="comment-item">
                    <div class="comment-header">
                        <span>
                            <i class="fas fa-calendar"></i>
                            <?= $date_comment->format('d/m/Y à H:i') ?>
                        </span>
                    </div>
                    <div class="comment-text">
                        <?= nl2br(htmlspecialchars($commentaire['texte'])) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
