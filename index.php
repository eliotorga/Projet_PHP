<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/BDD/config.php";

// Définition des constantes pour la configuration
define('STATUT_ACTIF', 'actif');
define('DEFAULT_TIMEZONE', 'Europe/Paris');

// Configuration du fuseau horaire
date_default_timezone_set(DEFAULT_TIMEZONE);

/* ============================================================
   CLASSES ET FONCTIONS UTILITAIRES
   ============================================================ */

/**
 * Classe de gestion des statistiques du tableau de bord
 */
class DashboardStats
{
    private PDO $pdo;
    private array $cache = [];

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getActivePlayersCount(): int
    {
        if (!isset($this->cache['active_players'])) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM joueur 
                WHERE statut = :statut
            ");
            $stmt->execute(['statut' => STATUT_ACTIF]);
            $this->cache['active_players'] = (int)$stmt->fetchColumn();
        }
        return $this->cache['active_players'];
    }

    public function getTotalPlayersCount(): int
    {
        if (!isset($this->cache['total_players'])) {
            $this->cache['total_players'] = (int)$this->pdo
                ->query("SELECT COUNT(*) FROM joueur")
                ->fetchColumn();
        }
        return $this->cache['total_players'];
    }

    public function getNextMatch(): ?array
    {
        if (!isset($this->cache['next_match'])) {
            $stmt = $this->pdo->prepare("
                SELECT 
                    id,
                    equipe_adverse,
                    lieu,
                    date_heure,
                    competition
                FROM match_sportif 
                WHERE date_heure >= NOW()
                ORDER BY date_heure ASC
                LIMIT 1
            ");
            $stmt->execute();
            $this->cache['next_match'] = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        return $this->cache['next_match'];
    }

    public function getTeamStatistics(): array
    {
        if (!isset($this->cache['team_stats'])) {
            $stmt = $this->pdo->query("
                SELECT
                    COALESCE(SUM(resultat = 'gagne'), 0) AS gagne,
                    COALESCE(SUM(resultat = 'perdu'), 0) AS perdu,
                    COALESCE(SUM(resultat = 'nul'), 0) AS nul,
                    COALESCE(COUNT(*), 0) AS total
                FROM match_sportif
            ");
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $total = (int)$stats['total'];
            
            $this->cache['team_stats'] = [
                'counts' => [
                    'gagne' => (int)$stats['gagne'],
                    'perdu' => (int)$stats['perdu'],
                    'nul' => (int)$stats['nul'],
                    'total' => $total
                ],
                'percentages' => [
                    'gagne' => $total ? round($stats['gagne'] * 100 / $total, 1) : 0,
                    'perdu' => $total ? round($stats['perdu'] * 100 / $total, 1) : 0,
                    'nul' => $total ? round($stats['nul'] * 100 / $total, 1) : 0
                ]
            ];
        }
        return $this->cache['team_stats'];
    }
}

/* ============================================================
   TRAITEMENT PRINCIPAL
   ============================================================ */

try {
    $dashboard = new DashboardStats($pdo);
    
    // Récupération des données
    $nbJoueursActifs = $dashboard->getActivePlayersCount();
    $nbJoueursTotal = $dashboard->getTotalPlayersCount();
    $prochainMatch = $dashboard->getNextMatch();
    $statsEquipe = $dashboard->getTeamStatistics();
    
    // Formatage des dates
    $dateProchainMatch = $prochainMatch 
        ? (new DateTime($prochainMatch['date_heure']))->format('d/m/Y H:i')
        : null;
        
} catch (PDOException $e) {
    // Journalisation de l'erreur (à adapter selon votre système de logs)
    error_log("Erreur Dashboard: " . $e->getMessage());
    
    // Valeurs par défaut en cas d'erreur
    $nbJoueursActifs = $nbJoueursTotal = 0;
    $prochainMatch = null;
    $statsEquipe = [
        'counts' => ['gagne' => 0, 'perdu' => 0, 'nul' => 0, 'total' => 0],
        'percentages' => ['gagne' => 0, 'perdu' => 0, 'nul' => 0]
    ];
}
?>

<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Gestion Sportive</title>
    
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --dark-color: #1f2937;
            --light-color: #f9fafb;
            --card-bg: #ffffff;
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --radius: 12px;
            --transition: all 0.3s ease;
        }

        [data-theme="dark"] {
            --primary-color: #3b82f6;
            --secondary-color: #10b981;
            --dark-color: #f9fafb;
            --light-color: #1f2937;
            --card-bg: #374151;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            color: var(--dark-color);
            min-height: 100vh;
            padding: 20px;
        }

        [data-theme="dark"] body {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Header */
        .dashboard-header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .dashboard-header h1 {
            font-size: 2.5rem;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
        }

        .dashboard-header p {
            color: #6b7280;
            font-size: 1.1rem;
        }

        /* Grid Dashboard */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        /* Cards */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 1.5rem;
            box-shadow: var(--shadow);
            transition: var(--transition);
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        [data-theme="dark"] .card {
            border-color: rgba(255, 255, 255, 0.1);
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-hover);
        }

        .card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .card-icon {
            width: 48px;
            height: 48px;
            background: var(--primary-color);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }

        .card-icon i {
            font-size: 1.5rem;
            color: white;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        /* Stats */
        .stats-container {
            display: grid;
            gap: 1rem;
        }

        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: var(--light-color);
            border-radius: 8px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .stat-value {
            font-weight: 700;
            font-size: 1.1rem;
        }

        /* Progress Bars */
        .progress-container {
            margin-top: 1rem;
        }

        .progress-bar {
            height: 8px;
            background: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease-in-out;
        }

        .progress-won { background: var(--secondary-color); }
        .progress-lost { background: var(--danger-color); }
        .progress-draw { background: var(--warning-color); }

        /* Quick Actions */
        .quick-actions {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            margin-bottom: 3rem;
            box-shadow: var(--shadow);
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
        }

        .btn-icon {
            margin-right: 0.5rem;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        /* Match Card */
        .match-card {
            background: linear-gradient(135deg, var(--primary-color) 0%, #3b82f6 100%);
            color: white;
        }

        .match-info {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
        }

        .match-time {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .dashboard-header h1 {
                font-size: 2rem;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .card {
            animation: fadeIn 0.6s ease-out;
        }

        .card:nth-child(2) { animation-delay: 0.1s; }
        .card:nth-child(3) { animation-delay: 0.2s; }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }

        .toggle-btn {
            background: var(--card-bg);
            border: 2px solid var(--primary-color);
            color: var(--dark-color);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: var(--transition);
        }

        /* Project Info */
        .project-info {
            background: var(--card-bg);
            border-radius: var(--radius);
            padding: 2rem;
            box-shadow: var(--shadow);
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .feature-item {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 1rem;
            background: var(--light-color);
            border-radius: 8px;
        }

        .feature-icon {
            color: var(--primary-color);
            font-size: 1.25rem;
        }
    </style>
    
    <!-- Font Awesome pour les icônes -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <!-- Theme Toggle -->
        <div class="theme-toggle">
            <button class="toggle-btn" id="themeToggle">
                <i class="fas fa-moon"></i> Mode Sombre
            </button>
        </div>

        <!-- Header -->
        <header class="dashboard-header">
            <h1>Tableau de Bord Sportif</h1>
            <p>Consultez et gérez l'ensemble des activités de votre équipe</p>
        </header>

        <!-- Main Dashboard Grid -->
        <div class="dashboard-grid">
            <!-- Card 1: Joueurs -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <h3 class="card-title">Gestion des Joueurs</h3>
                </div>
                
                <div class="stats-container">
                    <div class="stat-item">
                        <span class="stat-label">Joueurs Actifs</span>
                        <span class="stat-value"><?= $nbJoueursActifs ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Total Joueurs</span>
                        <span class="stat-value"><?= $nbJoueursTotal ?></span>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="joueurs/liste_joueurs.php" class="btn btn-primary">
                        <i class="fas fa-list btn-icon"></i> Liste des Joueurs
                    </a>
                    <a href="joueurs/ajouter_joueur.php" class="btn btn-primary">
                        <i class="fas fa-plus btn-icon"></i> Ajouter
                    </a>
                </div>
            </div>

            <!-- Card 2: Prochain Match -->
            <div class="card match-card">
                <div class="card-header">
                    <div class="card-icon" style="background: rgba(255,255,255,0.2);">
                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3 class="card-title">Prochain Match</h3>
                </div>

                <div class="match-info">
                    <?php if ($prochainMatch): ?>
                        <div class="match-time">
                            <i class="far fa-clock"></i> 
                            <?= htmlspecialchars($dateProchainMatch) ?>
                        </div>
                        <p><strong>Adversaire :</strong> <?= htmlspecialchars($prochainMatch['equipe_adverse']) ?></p>
                        <p><strong>Lieu :</strong> <?= htmlspecialchars($prochainMatch['lieu']) ?></p>
                        <?php if (!empty($prochainMatch['competition'])): ?>
                            <p><strong>Compétition :</strong> <?= htmlspecialchars($prochainMatch['competition']) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Aucun match programmé</p>
                    <?php endif; ?>
                </div>

                <div class="btn-group">
                    <a href="matchs/liste_matchs.php" class="btn btn-primary" style="background: rgba(255,255,255,0.2); border: 1px solid white;">
                        <i class="fas fa-futbol btn-icon"></i> Tous les Matchs
                    </a>
                </div>
            </div>

            <!-- Card 3: Statistiques -->
            <div class="card">
                <div class="card-header">
                    <div class="card-icon" style="background: var(--secondary-color);">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h3 class="card-title">Statistiques Équipe</h3>
                </div>

                <div class="stats-container">
                    <div class="stat-item">
                        <span class="stat-label">Victoires</span>
                        <span class="stat-value">
                            <?= $statsEquipe['counts']['gagne'] ?> 
                            (<?= $statsEquipe['percentages']['gagne'] ?>%)
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Défaites</span>
                        <span class="stat-value">
                            <?= $statsEquipe['counts']['perdu'] ?> 
                            (<?= $statsEquipe['percentages']['perdu'] ?>%)
                        </span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Matchs Nuls</span>
                        <span class="stat-value">
                            <?= $statsEquipe['counts']['nul'] ?> 
                            (<?= $statsEquipe['percentages']['nul'] ?>%)
                        </span>
                    </div>
                </div>

                <div class="progress-container">
                    <div class="progress-bar">
                        <div class="progress-fill progress-won" style="width: <?= $statsEquipe['percentages']['gagne'] ?>%"></div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill progress-lost" style="width: <?= $statsEquipe['percentages']['perdu'] ?>%"></div>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill progress-draw" style="width: <?= $statsEquipe['percentages']['nul'] ?>%"></div>
                    </div>
                </div>

                <div class="btn-group">
                    <a href="statistiques/stats_equipe.php" class="btn btn-primary">
                        <i class="fas fa-chart-line btn-icon"></i> Détails
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <section class="quick-actions">
            <h2 style="margin-bottom: 1rem; color: var(--dark-color);">
                <i class="fas fa-bolt"></i> Actions Rapides
            </h2>
            
            <div class="actions-grid">
                <a href="joueurs/ajouter_joueur.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-plus btn-icon"></i> Nouveau Joueur
                </a>
                
                <a href="matchs/ajouter_match.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-plus-circle btn-icon"></i> Nouveau Match
                </a>
                
                <a href="statistiques/stats_joueur.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-user-chart btn-icon"></i> Stats Joueurs
                </a>
                
                <a href="feuille_match/nouveau.php" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-clipboard-list btn-icon"></i> Feuille de Match
                </a>
            </div>
        </section>

        <!-- Project Information -->
        <section class="project-info">
            <h2 style="margin-bottom: 1rem; color: var(--dark-color);">
                <i class="fas fa-info-circle"></i> Fonctionnalités du Système
            </h2>
            
            <div class="features-grid">
                <div class="feature-item">
                    <i class="fas fa-user-cog feature-icon"></i>
                    <div>
                        <h4>Gestion des Joueurs</h4>
                        <p style="font-size: 0.9rem; color: #6b7280;">
                            Création, modification, archivage et suivi des joueurs
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-calendar-check feature-icon"></i>
                    <div>
                        <h4>Planning des Matchs</h4>
                        <p style="font-size: 0.9rem; color: #6b7280;">
                            Organisation complète des matchs et compétitions
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-clipboard-check feature-icon"></i>
                    <div>
                        <h4>Feuilles de Match</h4>
                        <p style="font-size: 0.9rem; color: #6b7280;">
                            Composition d'équipe, notes et performances
                        </p>
                    </div>
                </div>
                
                <div class="feature-item">
                    <i class="fas fa-chart-pie feature-icon"></i>
                    <div>
                        <h4>Analyses Statistiques</h4>
                        <p style="font-size: 0.9rem; color: #6b7280;">
                            Rapports détaillés équipe et joueurs
                        </p>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script>
        // Theme Toggle
        const themeToggle = document.getElementById('themeToggle');
        const themeIcon = themeToggle.querySelector('i');
        
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            html.setAttribute('data-theme', newTheme);
            
            // Mise à jour de l'icône et du texte
            if (newTheme === 'dark') {
                themeIcon.className = 'fas fa-sun';
                themeToggle.innerHTML = '<i class="fas fa-sun"></i> Mode Clair';
            } else {
                themeIcon.className = 'fas fa-moon';
                themeToggle.innerHTML = '<i class="fas fa-moon"></i> Mode Sombre';
            }
            
            // Sauvegarde du thème
            localStorage.setItem('theme', newTheme);
        });
        
        // Chargement du thème sauvegardé
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        
        if (savedTheme === 'dark') {
            themeIcon.className = 'fas fa-sun';
            themeToggle.innerHTML = '<i class="fas fa-sun"></i> Mode Clair';
        }
        
        // Animation des barres de progression
        document.addEventListener('DOMContentLoaded', () => {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0';
                setTimeout(() => {
                    bar.style.width = width;
                }, 300);
            });
        });
    </script>
</body>
</html>

<?php require_once __DIR__ . "/includes/footer.php"; ?>