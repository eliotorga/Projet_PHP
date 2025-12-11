<?php
// Vérifier l'authentification
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Vérifier l'état de la BDD
$db_status = false;
if (file_exists('../BDD/config.php')) {
    require_once '../BDD/config.php';
    if (function_exists('isDatabaseConnected')) {
        $db_status = isDatabaseConnected();
    }
}

// Charger les fonctions BDD si disponible
if ($db_status && file_exists('../BDD/joueurs.php')) {
    require_once '../BDD/joueurs.php';
}

// Traitement des actions
$action = $_GET['action'] ?? '';
$message = '';
$message_type = '';

// Gérer la suppression
if ($action === 'supprimer' && isset($_GET['id'])) {
    if ($db_status && function_exists('deleteJoueur')) {
        $id = intval($_GET['id']);
        deleteJoueur($id);
        $message = "Joueur supprimé avec succès";
        $message_type = "success";
    }
}

// Récupérer les joueurs
$joueurs = [];
if ($db_status && function_exists('getAllJoueurs')) {
    $joueurs = getAllJoueurs();
} else {
    // Données de démonstration
    $joueurs = [
        [
            'id' => 1,
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'numero_licence' => 'LIC001',
            'date_naissance' => '1995-03-15',
            'taille_cm' => 185,
            'poids_kg' => 78,
            'statut' => 'Actif'
        ],
        [
            'id' => 2,
            'nom' => 'Martin',
            'prenom' => 'Paul',
            'numero_licence' => 'LIC002',
            'date_naissance' => '1998-07-22',
            'taille_cm' => 178,
            'poids_kg' => 72,
            'statut' => 'Blessé'
        ],
        [
            'id' => 3,
            'nom' => 'Durand',
            'prenom' => 'Pierre',
            'numero_licence' => 'LIC003',
            'date_naissance' => '1996-11-05',
            'taille_cm' => 182,
            'poids_kg' => 75,
            'statut' => 'Actif'
        ],
        [
            'id' => 4,
            'nom' => 'Leroy',
            'prenom' => 'Michel',
            'numero_licence' => 'LIC004',
            'date_naissance' => '1999-01-30',
            'taille_cm' => 175,
            'poids_kg' => 68,
            'statut' => 'Suspendu'
        ]
    ];
}

// Filtrer par statut
$filtre_statut = $_GET['statut'] ?? '';
if ($filtre_statut) {
    $joueurs = array_filter($joueurs, function($joueur) use ($filtre_statut) {
        return $joueur['statut'] === $filtre_statut;
    });
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Joueurs - Football Team Manager</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Indicateur BDD */
        .db-status-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 1000;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.8em;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            background: <?php echo $db_status ? '#d4edda' : '#f8d7da'; ?>;
            color: <?php echo $db_status ? '#155724' : '#721c24'; ?>;
            border: 1px solid <?php echo $db_status ? '#c3e6cb' : '#f5c6cb'; ?>;
        }
        
        /* Header et menu */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 600;
            transition: background 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-success {
            background: #28a745;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-warning {
            background: #ffc107;
            color: #212529;
        }
        
        .btn-warning:hover {
            background: #e0a800;
        }
        
        /* Filtres */
        .filters {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .filter-group {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .filter-label {
            font-weight: 600;
            color: #495057;
        }
        
        .filter-select {
            padding: 8px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 5px;
            background: white;
            color: #495057;
            font-size: 14px;
        }
        
        .filter-select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #2c3e50;
            color: white;
        }
        
        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 0.5px;
        }
        
        tbody tr {
            border-bottom: 1px solid #e9ecef;
            transition: background 0.2s ease;
        }
        
        tbody tr:hover {
            background: #f8f9fa;
        }
        
        tbody tr:nth-child(even) {
            background: #f8f9fa;
        }
        
        tbody tr:hover:nth-child(even) {
            background: #e9ecef;
        }
        
        td {
            padding: 15px;
            color: #495057;
        }
        
        /* Badges statut */
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .badge-actif {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-blesse {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-suspendu {
            background: #f8d7da;
            color: #721c24;
        }
        
        .badge-absent {
            background: #e2e3e5;
            color: #383d41;
        }
        
        /* Actions */
        .actions {
            display: flex;
            gap: 8px;
        }
        
        .action-btn {
            padding: 5px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.85em;
            transition: all 0.2s ease;
        }
        
        .action-view {
            background: #17a2b8;
            color: white;
        }
        
        .action-edit {
            background: #ffc107;
            color: #212529;
        }
        
        .action-delete {
            background: #dc3545;
            color: white;
        }
        
        .action-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        
        /* Message info */
        .demo-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .demo-info i {
            color: #856404;
            margin-right: 10px;
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            margin-top: 20px;
            padding: 20px;
        }
        
        .page-link {
            padding: 8px 12px;
            background: #f8f9fa;
            color: #007bff;
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid #dee2e6;
        }
        
        .page-link:hover {
            background: #e9ecef;
        }
        
        .page-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: stretch;
                gap: 15px;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
                align-items: stretch;
            }
            
            table {
                display: block;
                overflow-x: auto;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Indicateur d'état BDD -->
    <div class="db-status-indicator">
        <i class="fas fa-database"></i>
        BDD: <?php echo $db_status ? 'CONNECTÉE' : 'HORS LIGNE'; ?>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div>
                <h1><i class="fas fa-users"></i> Gestion des Joueurs</h1>
                <p>Liste complète des joueurs de l'équipe</p>
            </div>
            <div>
                <a href="ajouter_joueur.php" class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Ajouter un joueur
                </a>
                <a href="../index.php" class="btn">
                    <i class="fas fa-arrow-left"></i> Retour à l'accueil
                </a>
            </div>
        </div>
        
        <!-- Menu navigation -->
        <nav class="menu">
            <ul>
                <li><a href="../index.php"><i class="fas fa-home"></i> Accueil</a></li>
                <li><a href="liste_joueurs.php" style="background: #34495e;"><i class="fas fa-users"></i> Joueurs</a></li>
                <li><a href="../matchs/liste_matchs.php"><i class="fas fa-calendar-alt"></i> Matchs</a></li>
                <li><a href="../feuilles/composer_match.php"><i class="fas fa-clipboard-list"></i> Feuilles</a></li>
                <li><a href="../stats/stats_equipes_joueurs.php"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
            </ul>
        </nav>
        
        <div class="user-info">
            <p><i class="fas fa-user"></i> Connecté en tant que : <strong><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Invité'); ?></strong></p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type === 'success' ? 'check-circle' : 'info-circle'; ?>"></i> 
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$db_status): ?>
            <div class="demo-info">
                <h3><i class="fas fa-info-circle"></i> Mode démonstration</h3>
                <p>Vous visualisez des données de démonstration. Pour gérer les vraies données, connectez la base de données.</p>
            </div>
        <?php endif; ?>
        
        <!-- Filtres -->
        <div class="filters">
            <div class="filter-group">
                <span class="filter-label">Filtrer par statut :</span>
                <select class="filter-select" onchange="window.location.href='?statut='+this.value">
                    <option value="">Tous les statuts</option>
                    <option value="Actif" <?php echo $filtre_statut === 'Actif' ? 'selected' : ''; ?>>Actif</option>
                    <option value="Blessé" <?php echo $filtre_statut === 'Blessé' ? 'selected' : ''; ?>>Blessé</option>
                    <option value="Suspendu" <?php echo $filtre_statut === 'Suspendu' ? 'selected' : ''; ?>>Suspendu</option>
                    <option value="Absent" <?php echo $filtre_statut === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                </select>
            </div>
            
            <div class="filter-group">
                <span class="filter-label">Tri :</span>
                <select class="filter-select" onchange="sortTable(this.value)">
                    <option value="nom">Nom (A-Z)</option>
                    <option value="nom_desc">Nom (Z-A)</option>
                    <option value="statut">Statut</option>
                    <option value="taille">Taille</option>
                </select>
            </div>
            
            <div style="margin-left: auto;">
                <span class="filter-label">Total : <?php echo count($joueurs); ?> joueur(s)</span>
            </div>
        </div>
        
        <!-- Tableau des joueurs -->
        <div class="table-container">
            <table id="joueursTable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom & Prénom</th>
                        <th>Numéro licence</th>
                        <th>Date naissance</th>
                        <th>Taille</th>
                        <th>Poids</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($joueurs)): ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #6c757d;">
                                <i class="fas fa-info-circle" style="font-size: 2em; margin-bottom: 10px; display: block;"></i>
                                Aucun joueur trouvé
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($joueurs as $joueur): ?>
                            <?php 
                            // Calculer l'âge
                            $date_naissance = new DateTime($joueur['date_naissance']);
                            $aujourdhui = new DateTime();
                            $age = $aujourdhui->diff($date_naissance)->y;
                            ?>
                            <tr>
                                <td>#<?php echo $joueur['id']; ?></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($joueur['nom'] . ' ' . $joueur['prenom']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($joueur['numero_licence']); ?></td>
                                <td>
                                    <?php echo $date_naissance->format('d/m/Y'); ?><br>
                                    <small style="color: #6c757d;">(<?php echo $age; ?> ans)</small>
                                </td>
                                <td><?php echo $joueur['taille_cm']; ?> cm</td>
                                <td><?php echo $joueur['poids_kg']; ?> kg</td>
                                <td>
                                    <?php 
                                    $badge_class = 'badge-' . strtolower($joueur['statut']);
                                    ?>
                                    <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $joueur['statut']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <a href="details_joueur.php?id=<?php echo $joueur['id']; ?>" class="action-btn action-view" title="Voir détails">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="modifier_joueur.php?id=<?php echo $joueur['id']; ?>" class="action-btn action-edit" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?action=supprimer&id=<?php echo $joueur['id']; ?>" 
                                           class="action-btn action-delete" 
                                           title="Supprimer"
                                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce joueur ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <a href="ajouter_commentaire.php?joueur_id=<?php echo $joueur['id']; ?>" class="action-btn" style="background: #6f42c1; color: white;" title="Ajouter commentaire">
                                            <i class="fas fa-comment"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Statistiques rapides -->
        <div class="quick-stats" style="margin-top: 30px;">
            <h3><i class="fas fa-chart-pie"></i> Répartition par statut</h3>
            <div class="stats-grid">
                <?php
                // Compter les statuts
                $statuts_count = [
                    'Actif' => 0,
                    'Blessé' => 0,
                    'Suspendu' => 0,
                    'Absent' => 0
                ];
                
                foreach ($joueurs as $joueur) {
                    if (isset($statuts_count[$joueur['statut']])) {
                        $statuts_count[$joueur['statut']]++;
                    }
                }
                
                $total_joueurs = count($joueurs);
                ?>
                
                <div class="stat-item">
                    <h4>Actifs</h4>
                    <p class="stat-number"><?php echo $statuts_count['Actif']; ?></p>
                    <?php if ($total_joueurs > 0): ?>
                        <p><?php echo round(($statuts_count['Actif'] / $total_joueurs) * 100, 1); ?>%</p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-item">
                    <h4>Blessés</h4>
                    <p class="stat-number"><?php echo $statuts_count['Blessé']; ?></p>
                    <?php if ($total_joueurs > 0): ?>
                        <p><?php echo round(($statuts_count['Blessé'] / $total_joueurs) * 100, 1); ?>%</p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-item">
                    <h4>Suspendus</h4>
                    <p class="stat-number"><?php echo $statuts_count['Suspendu']; ?></p>
                    <?php if ($total_joueurs > 0): ?>
                        <p><?php echo round(($statuts_count['Suspendu'] / $total_joueurs) * 100, 1); ?>%</p>
                    <?php endif; ?>
                </div>
                
                <div class="stat-item">
                    <h4>Total</h4>
                    <p class="stat-number"><?php echo $total_joueurs; ?></p>
                    <p>Joueurs</p>
                </div>
            </div>
        </div>
        
        <div style="text-align: center; margin-top: 30px;">
            <a href="ajouter_joueur.php" class="btn btn-success" style="padding: 15px 30px; font-size: 1.1em;">
                <i class="fas fa-user-plus"></i> Ajouter un nouveau joueur
            </a>
        </div>
    </div>
    
    <footer style="margin-top: 50px; padding: 20px; text-align: center; background: #2c3e50; color: white;">
        <p>Football Team Manager &copy; <?php echo date('Y'); ?> - Gestion des joueurs</p>
        <p style="font-size: 0.9em; color: #bdc3c7;">
            <?php echo count($joueurs); ?> joueur(s) listé(s) | 
            BDD: <strong><?php echo $db_status ? 'Connectée' : 'Hors ligne (démo)'; ?></strong>
        </p>
    </footer>
    
    <script>
        // Fonction de tri
        function sortTable(criteria) {
            const rows = Array.from(document.querySelectorAll('#joueursTable tbody tr'));
            
            rows.sort((a, b) => {
                const aCells = a.querySelectorAll('td');
                const bCells = b.querySelectorAll('td');
                
                switch(criteria) {
                    case 'nom':
                        return aCells[1].textContent.localeCompare(bCells[1].textContent);
                    case 'nom_desc':
                        return bCells[1].textContent.localeCompare(aCells[1].textContent);
                    case 'statut':
                        return aCells[6].textContent.localeCompare(bCells[6].textContent);
                    case 'taille':
                        const aTaille = parseInt(aCells[4].textContent);
                        const bTaille = parseInt(bCells[4].textContent);
                        return bTaille - aTaille;
                    default:
                        return 0;
                }
            });
            
            const tbody = document.querySelector('#joueursTable tbody');
            tbody.innerHTML = '';
            rows.forEach(row => tbody.appendChild(row));
        }
        
        // Recherche en temps réel
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.createElement('input');
            searchInput.type = 'text';
            searchInput.placeholder = 'Rechercher un joueur...';
            searchInput.style.cssText = 'padding: 8px 12px; border: 2px solid #e0e0e0; border-radius: 5px; width: 300px; max-width: 100%; margin-bottom: 15px;';
            
            const filtersDiv = document.querySelector('.filters');
            filtersDiv.insertBefore(searchInput, filtersDiv.firstChild);
            
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#joueursTable tbody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        });
    </script>
</body>
</html>