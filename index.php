<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Vérifier l'état de la BDD
$db_status = false;
if (file_exists('BDD/config.php')) {
    require_once 'BDD/config.php';
    $db_status = isDatabaseConnected();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football Team Manager - Accueil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Indicateur d'état BDD -->
    <div style="position: fixed; top: 10px; right: 10px; z-index: 1000; padding: 8px 12px; border-radius: 4px; font-size: 0.8em; font-weight: bold; box-shadow: 0 2px 5px rgba(0,0,0,0.2); background: <?php echo $db_status ? '#d4edda' : '#f8d7da'; ?>; color: <?php echo $db_status ? '#155724' : '#721c24'; ?>; border: 1px solid <?php echo $db_status ? '#c3e6cb' : '#f5c6cb'; ?>;">
        <i class="fas fa-database"></i>
        BDD: <?php echo $db_status ? 'CONNECTÉE' : 'HORS LIGNE'; ?>
    </div>

    <div class="container">
        <h1><i class="fas fa-futbol"></i> Football Team Manager</h1>
        
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type'] ?? 'info'; ?>">
                <i class="fas fa-info-circle"></i> <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>
        
        <!-- Menu de navigation -->
        <nav class="menu">
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="joueurs/liste_joueurs.php"><i class="fas fa-users"></i> Joueurs</a></li>
                <li><a href="matchs/liste_matchs.php"><i class="fas fa-calendar-alt"></i> Matchs</a></li>
                <li><a href="feuilles/composer_match.php"><i class="fas fa-clipboard-list"></i> Feuilles de Match</a></li>
                <li><a href="stats/stats_equipes_joueurs.php"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
                <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>
        
        <div class="user-info">
            <p><i class="fas fa-user"></i> Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Invité'); ?></strong></p>
        </div>
        
        <?php if (!$db_status): ?>
            <div class="db-info-box">
                <h3><i class="fas fa-info-circle"></i> Mode démonstration</h3>
                <p>L'application fonctionne en mode démonstration. Les données sont simulées.</p>
                <p><strong>Pour utiliser les vraies données :</strong></p>
                <ol>
                    <li>Démarrez MySQL dans XAMPP</li>
                    <li>Créez la base "gestion_equipe" dans phpMyAdmin</li>
                    <li>Importez le fichier SQL fourni</li>
                </ol>
            </div>
        <?php endif; ?>
        
        <div class="dashboard">
            <!-- Gestion des Joueurs -->
            <div class="dashboard-card">
                <a href="joueurs/liste_joueurs.php">
                    <div class="card-content">
                        <i class="fas fa-users fa-3x"></i>
                        <h3>Gestion des Joueurs</h3>
                        <p>Ajouter, modifier ou supprimer des joueurs</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Gestion des Matchs -->
            <div class="dashboard-card">
                <a href="matchs/liste_matchs.php">
                    <div class="card-content">
                        <i class="fas fa-calendar-alt fa-3x"></i>
                        <h3>Gestion des Matchs</h3>
                        <p>Planifier et gérer les matchs</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Feuilles de Match -->
            <div class="dashboard-card">
                <a href="feuilles/composer_match.php">
                    <div class="card-content">
                        <i class="fas fa-clipboard-list fa-3x"></i>
                        <h3>Feuilles de Match</h3>
                        <p>Composer les équipes pour les matchs</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Statistiques -->
            <div class="dashboard-card">
                <a href="stats/stats_equipes_joueurs.php">
                    <div class="card-content">
                        <i class="fas fa-chart-bar fa-3x"></i>
                        <h3>Statistiques</h3>
                        <p>Consulter les stats de l'équipe et des joueurs</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Ajouter un Joueur -->
            <div class="dashboard-card">
                <a href="joueurs/ajouter_joueur.php">
                    <div class="card-content">
                        <i class="fas fa-user-plus fa-3x"></i>
                        <h3>Ajouter un Joueur</h3>
                        <p>Ajouter un nouveau joueur à l'effectif</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
            
            <!-- Ajouter un Match -->
            <div class="dashboard-card">
                <a href="matchs/ajouter_match.php">
                    <div class="card-content">
                        <i class="fas fa-plus-circle fa-3x"></i>
                        <h3>Ajouter un Match</h3>
                        <p>Planifier un nouveau match</p>
                        <?php if (!$db_status): ?>
                            <span style="color: #6c757d; font-size: 0.9em;">
                                <i class="fas fa-eye"></i> Mode démo
                            </span>
                        <?php endif; ?>
                    </div>
                </a>
            </div>
        </div>
        
        <div class="quick-stats">
            <h2><i class="fas fa-tachometer-alt"></i> Aperçu Rapide</h2>
            <div class="stats-grid">
                <?php if ($db_status): ?>
                    <!-- Si BDD connectée, afficher les vraies données -->
                    <?php
                    // Ces fonctions doivent être définies dans BDD/joueurs.php et BDD/matchs.php
                    $joueursActifs = 0;
                    $matchsAVenir = 0;
                    $joueursBlesses = 0;
                    $dernierMatch = null;
                    
                    if (function_exists('countJoueursByStatut')) {
                        $joueursActifs = countJoueursByStatut('Actif');
                        $joueursBlesses = countJoueursByStatut('Blessé');
                    }
                    
                    if (function_exists('countMatchsAVenir')) {
                        $matchsAVenir = countMatchsAVenir();
                    }
                    ?>
                    
                    <div class="stat-item">
                        <h4>Joueurs Actifs</h4>
                        <p class="stat-number"><?php echo $joueursActifs; ?></p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Matchs à Venir</h4>
                        <p class="stat-number"><?php echo $matchsAVenir; ?></p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Joueurs Blessés</h4>
                        <p class="stat-number"><?php echo $joueursBlesses; ?></p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Statut</h4>
                        <p class="stat-number" style="color: #28a745;">✓</p>
                        <p>BDD connectée</p>
                    </div>
                <?php else: ?>
                    <!-- Mode démonstration avec données fictives -->
                    <div class="stat-item">
                        <h4>Joueurs Actifs</h4>
                        <p class="stat-number">15</p>
                        <p style="font-size: 0.8em; color: #6c757d;">(démo)</p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Matchs à Venir</h4>
                        <p class="stat-number">3</p>
                        <p style="font-size: 0.8em; color: #6c757d;">(démo)</p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Joueurs Blessés</h4>
                        <p class="stat-number">2</p>
                        <p style="font-size: 0.8em; color: #6c757d;">(démo)</p>
                    </div>
                    
                    <div class="stat-item">
                        <h4>Mode</h4>
                        <p class="stat-number" style="color: #6c757d;">👁️</p>
                        <p>Démonstration</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer style="margin-top: 50px; padding: 20px; text-align: center; background: #2c3e50; color: white;">
        <p>Football Team Manager &copy; <?php echo date('Y'); ?> - Application de gestion d'équipe de football</p>
        <p style="font-size: 0.9em; color: #bdc3c7;">
            Connecté en tant que: <strong><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Invité'); ?></strong> | 
            BDD: <strong><?php echo $db_status ? 'Connectée' : 'Hors ligne (démo)'; ?></strong>
        </p>
    </footer>
    
    <style>
        /* Styles CSS */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            padding-bottom: 50px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            padding: 20px 0;
            border-bottom: 3px solid #3498db;
        }
        
        h2 {
            color: #34495e;
            margin: 25px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        
        h3 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        /* Dashboard */
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .dashboard-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            overflow: hidden;
        }
        
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.15);
        }
        
        .dashboard-card a {
            text-decoration: none;
            color: inherit;
            display: block;
            padding: 30px 20px;
        }
        
        .card-content {
            text-align: center;
        }
        
        .card-content i {
            color: #3498db;
            margin-bottom: 20px;
        }
        
        /* Menu */
        .menu {
            background: #2c3e50;
            padding: 15px 0;
            margin-bottom: 30px;
        }
        
        .menu ul {
            display: flex;
            justify-content: center;
            list-style: none;
            flex-wrap: wrap;
        }
        
        .menu li {
            margin: 0 15px;
        }
        
        .menu a {
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            transition: background 0.3s ease;
        }
        
        .menu a:hover {
            background: #34495e;
        }
        
        /* User info */
        .user-info {
            background: #ecf0f1;
            padding: 10px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            text-align: right;
            font-size: 0.9em;
            color: #7f8c8d;
        }
        
        /* Stats */
        .quick-stats {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        
        .stat-item {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #2c3e50;
            margin: 10px 0;
        }
        
        /* Alerts */
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        /* DB info box */
        .db-info-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .db-info-box h3 {
            margin-top: 0;
            color: #856404;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .dashboard {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .menu ul {
                flex-direction: column;
                align-items: center;
            }
            
            .menu li {
                margin: 5px 0;
                width: 100%;
                text-align: center;
            }
        }
    </style>
</body>
</html>