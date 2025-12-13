<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

// Vérifier si l'ID du joueur est passé en paramètre
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: liste_joueurs.php");
    exit;
}

$id_joueur = intval($_GET['id']);

// Récupérer les informations du joueur
$stmt = $gestion_sportive->prepare("
    SELECT 
        j.*,
        s.code as statut_code,
        s.libelle as statut_libelle,
        YEAR(CURDATE()) - YEAR(date_naissance) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(date_naissance, '%m%d')) as age
    FROM joueur j
    LEFT JOIN statut s ON j.id_statut = s.id_statut
    WHERE j.id_joueur = ?
");

$stmt->execute([$id_joueur]);
$joueur = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$joueur) {
    $_SESSION['error_message'] = "Joueur non trouvé.";
    header("Location: liste_joueurs.php");
    exit;
}

// Récupérer les statistiques des matchs du joueur
$stmt = $gestion_sportive->prepare("
    SELECT 
        COUNT(*) as total_matchs,
        SUM(CASE WHEN p.role = 'TITULAIRE' THEN 1 ELSE 0 END) as matchs_titulaire,
        SUM(CASE WHEN p.role = 'REMPLACANT' THEN 1 ELSE 0 END) as matchs_remplacant,
        AVG(p.evaluation) as moyenne_evaluation,
        COUNT(p.evaluation) as matchs_evalues,
        MIN(p.evaluation) as min_evaluation,
        MAX(p.evaluation) as max_evaluation
    FROM participation p
    INNER JOIN matchs m ON p.id_match = m.id_match
    WHERE p.id_joueur = ? AND m.etat = 'JOUE'
");

$stmt->execute([$id_joueur]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// Récupérer l'historique des matchs du joueur
$stmt = $gestion_sportive->prepare("
    SELECT 
        m.*,
        p.id_poste,
        po.libelle as poste_libelle,
        p.role,
        p.evaluation,
        CASE 
            WHEN m.resultat = 'VICTOIRE' THEN 'victoire'
            WHEN m.resultat = 'DEFAITE' THEN 'defaite'
            WHEN m.resultat = 'NUL' THEN 'nul'
            ELSE 'indetermine'
        END as resultat_class
    FROM participation p
    INNER JOIN matchs m ON p.id_match = m.id_match
    LEFT JOIN poste po ON p.id_poste = po.id_poste
    WHERE p.id_joueur = ?
    ORDER BY m.date_heure DESC
");

$stmt->execute([$id_joueur]);
$matchs_joueur = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les commentaires du joueur
$stmt = $gestion_sportive->prepare("
    SELECT *
    FROM commentaire
    WHERE id_joueur = ?
    ORDER BY date_commentaire DESC
");

$stmt->execute([$id_joueur]);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la répartition des postes
$stmt = $gestion_sportive->prepare("
    SELECT 
        po.libelle as poste,
        COUNT(*) as nb_matchs,
        ROUND(AVG(p.evaluation), 2) as moyenne_eval
    FROM participation p
    LEFT JOIN poste po ON p.id_poste = po.id_poste
    WHERE p.id_joueur = ?
    GROUP BY p.id_poste, po.libelle
    ORDER BY nb_matchs DESC
");

$stmt->execute([$id_joueur]);
$postes = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fiche Joueur - <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --secondary: #2ecc71;
        --warning: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --dark: #2c3e50;
        --light: #ecf0f1;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.15);
        --radius: 12px;
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f5f7fa 0%, #e4e8f0 100%);
        color: var(--dark);
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* HEADER */
    .header-back {
        margin-bottom: 30px;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        color: var(--dark);
        text-decoration: none;
        font-weight: 600;
        padding: 10px 20px;
        background: white;
        border-radius: 50px;
        box-shadow: var(--shadow);
        transition: var(--transition);
    }

    .back-btn:hover {
        transform: translateX(-5px);
        background: var(--primary);
        color: white;
    }

    /* PROFIL DU JOUEUR */
    .player-profile {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }

    .profile-header {
        background: linear-gradient(135deg, var(--primary-dark), var(--primary));
        color: white;
        padding: 40px;
        display: flex;
        align-items: center;
        gap: 30px;
        flex-wrap: wrap;
    }

    .player-photo {
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, #34495e, #2c3e50);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 4rem;
        color: white;
        border: 5px solid rgba(255,255,255,0.2);
    }

    .player-info h1 {
        font-size: 2.5rem;
        margin-bottom: 10px;
    }

    .player-details {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
        margin-top: 15px;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(255,255,255,0.1);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 0.9rem;
    }

    .player-body {
        padding: 30px;
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    /* STATISTIQUES */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        text-align: center;
        transition: var(--transition);
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }

    .stat-card.matchs { border-top: 4px solid var(--info); }
    .stat-card.evaluation { border-top: 4px solid var(--warning); }
    .stat-card.titulaire { border-top: 4px solid var(--secondary); }
    .stat-card.postes { border-top: 4px solid #9b59b6; }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 700;
        margin: 10px 0;
    }

    .stat-matchs .stat-value { color: var(--info); }
    .stat-evaluation .stat-value { color: var(--warning); }
    .stat-titulaire .stat-value { color: var(--secondary); }

    .stat-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    /* LISTE DES MATCHS */
    .matchs-container {
        background: white;
        border-radius: var(--radius);
        padding: 30px;
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }

    .section-title {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid #f0f3f8;
    }

    .section-title h2 {
        font-size: 1.5rem;
        color: var(--dark);
    }

    .matchs-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .match-item {
        display: flex;
        align-items: center;
        padding: 15px;
        background: #f8fafc;
        border-radius: 10px;
        transition: var(--transition);
        border-left: 4px solid #ddd;
    }

    .match-item:hover {
        background: #edf2f7;
        transform: translateX(5px);
    }

    .match-item.victoire { border-left-color: var(--secondary); }
    .match-item.defaite { border-left-color: var(--danger); }
    .match-item.nul { border-left-color: #f39c12; }

    .match-date {
        min-width: 100px;
        font-weight: 600;
        color: var(--dark);
    }

    .match-info {
        flex: 1;
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .match-adversaire {
        font-weight: 600;
        color: var(--dark);
        min-width: 200px;
    }

    .match-details {
        display: flex;
        gap: 15px;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .match-evaluation {
        display: flex;
        align-items: center;
        gap: 5px;
        color: #f39c12;
        font-weight: 600;
    }

    /* COMMENTAIRES */
    .comments-container {
        background: white;
        border-radius: var(--radius);
        padding: 30px;
        box-shadow: var(--shadow);
    }

    .comment-item {
        padding: 15px;
        background: #f8fafc;
        border-radius: 10px;
        margin-bottom: 15px;
        border-left: 4px solid var(--info);
    }

    .comment-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .comment-text {
        line-height: 1.6;
        color: var(--dark);
    }

    /* BADGES */
    .badge {
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .badge-statut-ACT { background: #e8f5e9; color: #2e7d32; }
    .badge-statut-BLE { background: #fff8e1; color: #f57c00; }
    .badge-statut-SUS { background: #ffebee; color: #c62828; }
    .badge-statut-ABS { background: #eceff1; color: #455a64; }

    /* POSTES */
    .postes-container {
        margin-top: 30px;
    }

    .poste-bar {
        margin-bottom: 15px;
    }

    .poste-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }

    .poste-label {
        font-weight: 600;
        color: var(--dark);
    }

    .poste-count {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .poste-progress {
        height: 10px;
        background: #e0e6ed;
        border-radius: 5px;
        overflow: hidden;
    }

    .poste-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 5px;
    }

    /* RESPONSIVE */
    @media (max-width: 768px) {
        .profile-header {
            flex-direction: column;
            text-align: center;
            padding: 30px 20px;
        }
        
        .player-details {
            justify-content: center;
        }
        
        .match-info {
            flex-direction: column;
            align-items: flex-start;
            gap: 10px;
        }
        
        .match-details {
            flex-wrap: wrap;
        }
        
        .stats-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
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
                        
                        // Déterminer le texte du résultat
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

    <script>
    // Animation des statistiques
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des barres de progression
        const progressBars = document.querySelectorAll('.poste-progress-fill');
        progressBars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.transition = 'width 1.5s ease-in-out';
                bar.style.width = width;
            }, 300);
        });

        // Animation des cartes au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les cartes de stats
        document.querySelectorAll('.stat-card').forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            observer.observe(card);
        });

        // Observer les matchs
        document.querySelectorAll('.match-item').forEach((item, index) => {
            item.style.opacity = '0';
            item.style.transform = 'translateX(-20px)';
            item.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
            item.style.transitionDelay = (index * 0.1) + 's';
            observer.observe(item);
        });
    });
    </script>
</body>
</html>
<?php include "../includes/footer.php"; ?>