<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* ==========================
   RÉCUPÉRATION JOUEURS AVEC STATISTIQUES
========================== */
$joueurs = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        j.num_licence,
        j.date_naissance,
        j.taille_cm,
        j.poids_kg,
        s.id_statut,
        s.code AS statut_code,
        s.libelle AS statut_libelle,
        COUNT(DISTINCT p.id_match) AS nb_matchs,
        ROUND(AVG(p.evaluation), 1) AS note_moyenne,
        COUNT(c.id_commentaire) AS nb_commentaires
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    LEFT JOIN participation p ON p.id_joueur = j.id_joueur
    LEFT JOIN commentaire c ON c.id_joueur = j.id_joueur
    GROUP BY j.id_joueur, j.nom, j.prenom, j.num_licence, j.date_naissance, 
             j.taille_cm, j.poids_kg, s.id_statut, s.code, s.libelle
    ORDER BY j.nom, j.prenom
")->fetchAll(PDO::FETCH_ASSOC);

// Calcul de l'âge pour chaque joueur
foreach ($joueurs as &$joueur) {
    if ($joueur['date_naissance']) {
        $naissance = new DateTime($joueur['date_naissance']);
        $aujourdhui = new DateTime();
        $joueur['age'] = $aujourdhui->diff($naissance)->y;
    } else {
        $joueur['age'] = 'N/A';
    }
}
unset($joueur); // Détruire la référence

// Statistiques globales
$stats = $gestion_sportive->query("
    SELECT 
        s.code,
        s.libelle,
        COUNT(j.id_joueur) AS nb_joueurs
    FROM statut s
    LEFT JOIN joueur j ON j.id_statut = s.id_statut
    GROUP BY s.id_statut, s.code, s.libelle
    ORDER BY s.id_statut
")->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Joueurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    /* =============================
       VARIABLES & RESET
    ============================= */
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --secondary: #2ecc71;
        --accent: #f39c12;
        --danger: #e74c3c;
        --info: #3498db;
        --purple: #9b59b6;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.15);
        --radius: 16px;
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

    /* =============================
       LAYOUT PRINCIPAL
    ============================= */
    .page-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 30px 20px;
    }

    /* =============================
       HEADER ET FILTRES
    ============================= */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .page-title h1 {
        font-size: 2.4rem;
        color: var(--dark);
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 15px;
    }

    .page-title p {
        color: var(--gray);
        font-size: 1.1rem;
        max-width: 600px;
    }

    .btn-add-player {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
        padding: 14px 28px;
        border-radius: 50px;
        text-decoration: none;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        transition: var(--transition);
        box-shadow: var(--shadow);
        white-space: nowrap;
    }

    .btn-add-player:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(46, 204, 113, 0.3);
    }

    /* =============================
       STATISTIQUES PAR STATUT
    ============================= */
    .status-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .status-card {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 20px;
        box-shadow: var(--shadow);
        transition: var(--transition);
        border-top: 4px solid transparent;
    }

    .status-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .status-card.ACT { border-color: var(--secondary); }
    .status-card.BLE { border-color: var(--accent); }
    .status-card.SUS { border-color: var(--danger); }
    .status-card.ABS { border-color: var(--gray); }

    .status-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .ACT .status-icon { background: linear-gradient(135deg, var(--secondary), #27ae60); }
    .BLE .status-icon { background: linear-gradient(135deg, var(--accent), #e67e22); }
    .SUS .status-icon { background: linear-gradient(135deg, var(--danger), #c0392b); }
    .ABS .status-icon { background: linear-gradient(135deg, var(--gray), #7f8c8d); }

    .status-info {
        flex: 1;
    }

    .status-count {
        font-size: 2rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 5px;
    }

    .status-label {
        font-size: 0.9rem;
        color: var(--gray);
    }

    /* =============================
       FILTRES ET RECHERCHE
    ============================= */
    .filters-container {
        background: white;
        border-radius: var(--radius);
        padding: 20px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
        align-items: center;
    }

    .search-box {
        flex: 1;
        min-width: 300px;
        position: relative;
    }

    .search-input {
        width: 100%;
        padding: 14px 50px 14px 20px;
        border: 2px solid #e0e6ed;
        border-radius: 50px;
        font-size: 1rem;
        transition: var(--transition);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.1);
    }

    .search-icon {
        position: absolute;
        right: 20px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray);
    }

    .filter-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .filter-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: var(--dark);
    }

    .filter-select {
        padding: 12px 15px;
        border: 2px solid #e0e6ed;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 500;
        min-width: 150px;
        transition: var(--transition);
    }

    .filter-select:focus {
        outline: none;
        border-color: var(--secondary);
    }

    /* =============================
       CARTES DES JOUEURS
    ============================= */
    .players-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 40px;
    }

    @media (max-width: 768px) {
        .players-grid {
            grid-template-columns: 1fr;
        }
    }

    .player-card {
        background: white;
        border-radius: var(--radius);
        overflow: hidden;
        box-shadow: var(--shadow);
        transition: var(--transition);
        position: relative;
    }

    .player-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    }

    .player-header {
        padding: 25px 25px 20px;
        border-bottom: 1px solid #f0f3f8;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }

    .player-identity {
        flex: 1;
    }

    .player-name {
        font-size: 1.5rem;
        font-weight: 700;
        margin-bottom: 5px;
        color: var(--dark);
    }

    .player-license {
        font-size: 0.9rem;
        color: var(--gray);
        font-family: 'Courier New', monospace;
    }

    .player-status {
        padding: 8px 16px;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        white-space: nowrap;
    }

    .ACT { background: linear-gradient(135deg, #e8f5e9, #c8e6c9); color: #2e7d32; }
    .BLE { background: linear-gradient(135deg, #fff8e1, #ffe082); color: #f57c00; }
    .SUS { background: linear-gradient(135deg, #ffebee, #ffcdd2); color: #c62828; }
    .ABS { background: linear-gradient(135deg, #eceff1, #cfd8dc); color: #455a64; }

    .player-body {
        padding: 20px 25px;
    }

    .player-stats {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-bottom: 20px;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px;
        background: #f8fafc;
        border-radius: 8px;
    }

    .stat-icon {
        width: 36px;
        height: 36px;
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1rem;
    }

    .stat-value {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark);
    }

    .stat-label {
        font-size: 0.85rem;
        color: var(--gray);
    }

    .player-rating {
        margin-bottom: 20px;
    }

    .rating-stars {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 5px;
    }

    .star {
        color: #ddd;
        font-size: 1rem;
    }

    .star.filled {
        color: var(--accent);
    }

    .rating-text {
        font-size: 0.9rem;
        color: var(--gray);
    }

    /* =============================
       ACTIONS
    ============================= */
    .player-actions {
        display: flex;
        gap: 10px;
        padding: 15px 25px;
        background: #f8fafc;
        border-top: 1px solid #f0f3f8;
        border-bottom-left-radius: var(--radius);
        border-bottom-right-radius: var(--radius);
    }

    .btn-action {
        flex: 1;
        padding: 12px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: var(--transition);
        text-align: center;
    }

    .btn-view {
        background: linear-gradient(135deg, var(--info), #3498db);
        color: white;
    }

    .btn-view:hover {
        background: linear-gradient(135deg, #3498db, #2980b9);
        transform: translateY(-2px);
    }

    .btn-edit {
        background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        color: white;
    }

    .btn-edit:hover {
        background: linear-gradient(135deg, #7f8c8d, #6c7b7d);
        transform: translateY(-2px);
    }

    .btn-delete {
        background: linear-gradient(135deg, var(--danger), #c0392b);
        color: white;
    }

    .btn-delete:hover {
        background: linear-gradient(135deg, #c0392b, #a93226);
        transform: translateY(-2px);
    }

    /* =============================
       EMPTY STATE
    ============================= */
    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px 20px;
        background: white;
        border-radius: var(--radius);
        box-shadow: var(--shadow);
    }

    .empty-icon {
        font-size: 4rem;
        color: #e0e6ed;
        margin-bottom: 20px;
    }

    .empty-title {
        font-size: 1.5rem;
        color: var(--dark);
        margin-bottom: 10px;
    }

    .empty-text {
        color: var(--gray);
        margin-bottom: 20px;
        max-width: 500px;
        margin-left: auto;
        margin-right: auto;
    }

    /* =============================
       ANIMATIONS
    ============================= */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .player-card {
        animation: fadeInUp 0.5s ease forwards;
    }

    .player-card:nth-child(1) { animation-delay: 0.1s; }
    .player-card:nth-child(2) { animation-delay: 0.2s; }
    .player-card:nth-child(3) { animation-delay: 0.3s; }
    .player-card:nth-child(4) { animation-delay: 0.4s; }
    .player-card:nth-child(5) { animation-delay: 0.5s; }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 768px) {
        .page-header {
            flex-direction: column;
            align-items: stretch;
        }
        
        .btn-add-player {
            width: 100%;
            justify-content: center;
        }
        
        .filters-container {
            flex-direction: column;
            align-items: stretch;
        }
        
        .search-box {
            min-width: 100%;
        }
        
        .player-stats {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-users"></i> Gestion des Joueurs</h1>
                <p>Gérez l'effectif complet de votre équipe avec toutes les informations et statistiques</p>
            </div>
            <a href="ajouter_joueur.php" class="btn-add-player">
                <i class="fas fa-plus-circle"></i> Nouveau Joueur
            </a>
        </div>

        <!-- STATISTIQUES PAR STATUT -->
        <div class="status-stats">
            <?php foreach ($stats as $stat): ?>
                <div class="status-card <?= $stat['code'] ?>">
                    <div class="status-icon">
                        <?php 
                        $icons = [
                            'ACT' => 'fas fa-check-circle',
                            'BLE' => 'fas fa-band-aid',
                            'SUS' => 'fas fa-ban',
                            'ABS' => 'fas fa-user-slash'
                        ];
                        echo '<i class="' . ($icons[$stat['code']] ?? 'fas fa-user') . '"></i>';
                        ?>
                    </div>
                    <div class="status-info">
                        <div class="status-count"><?= $stat['nb_joueurs'] ?></div>
                        <div class="status-label"><?= htmlspecialchars($stat['libelle']) ?></div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- FILTRES ET RECHERCHE -->
        <div class="filters-container">
            <div class="search-box">
                <input type="text" 
                       class="search-input" 
                       placeholder="Rechercher un joueur..." 
                       id="searchPlayers">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par statut</label>
                <select class="filter-select" id="filterStatus">
                    <option value="all">Tous les statuts</option>
                    <?php foreach ($stats as $stat): ?>
                        <option value="<?= $stat['code'] ?>"><?= htmlspecialchars($stat['libelle']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Trier par</label>
                <select class="filter-select" id="sortPlayers">
                    <option value="nom">Nom A-Z</option>
                    <option value="note_desc">Note décroissante</option>
                    <option value="matchs_desc">Matchs joués</option>
                    <option value="age_asc">Âge croissant</option>
                </select>
            </div>
        </div>

        <!-- LISTE DES JOUEURS -->
        <div class="players-grid" id="playersList">
            <?php if (!empty($joueurs)): ?>
                <?php foreach ($joueurs as $joueur): ?>
                    <div class="player-card" 
                         data-name="<?= htmlspecialchars(strtolower($joueur['nom'] . ' ' . $joueur['prenom'])) ?>"
                         data-status="<?= $joueur['statut_code'] ?>"
                         data-rating="<?= $joueur['note_moyenne'] ?? 0 ?>"
                         data-matchs="<?= $joueur['nb_matchs'] ?? 0 ?>"
                         data-age="<?= $joueur['age'] ?>">
                        
                        <!-- EN-TÊTE DU JOUEUR -->
                        <div class="player-header">
                            <div class="player-identity">
                                <div class="player-name">
                                    <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?>
                                </div>
                                <div class="player-license">
                                    <i class="fas fa-id-card"></i> <?= htmlspecialchars($joueur['num_licence']) ?>
                                </div>
                            </div>
                            <div class="player-status <?= $joueur['statut_code'] ?>">
                                <?= htmlspecialchars($joueur['statut_libelle']) ?>
                            </div>
                        </div>
                        
                        <!-- CORPS DE LA CARTE -->
                        <div class="player-body">
                            <!-- STATISTIQUES -->
                            <div class="player-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-birthday-cake"></i>
                                    </div>
                                    <div>
                                        <div class="stat-value"><?= $joueur['age'] ?> ans</div>
                                        <div class="stat-label">Âge</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-ruler-combined"></i>
                                    </div>
                                    <div>
                                        <div class="stat-value">
                                            <?= $joueur['taille_cm'] ? $joueur['taille_cm'] . ' cm' : 'N/A' ?>
                                        </div>
                                        <div class="stat-label">Taille</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-weight"></i>
                                    </div>
                                    <div>
                                        <div class="stat-value">
                                            <?= $joueur['poids_kg'] ? $joueur['poids_kg'] . ' kg' : 'N/A' ?>
                                        </div>
                                        <div class="stat-label">Poids</div>
                                    </div>
                                </div>
                                
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fas fa-gamepad"></i>
                                    </div>
                                    <div>
                                        <div class="stat-value"><?= $joueur['nb_matchs'] ?? 0 ?></div>
                                        <div class="stat-label">Matchs</div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- ÉVALUATION -->
                            <?php if ($joueur['note_moyenne']): ?>
                                <div class="player-rating">
                                    <div class="rating-stars">
                                        <?php 
                                        $note = round($joueur['note_moyenne']);
                                        for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star star <?= $i <= $note ? 'filled' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <div class="rating-text">
                                        Note moyenne : <strong><?= $joueur['note_moyenne'] ?></strong>/5 
                                        (<?= $joueur['nb_commentaires'] ?> avis)
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="player-rating" style="opacity: 0.6;">
                                    <i class="fas fa-star"></i> Aucune évaluation disponible
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- ACTIONS -->
                        <div class="player-actions">
                            <a href="fiche_joueur.php?id=<?= $joueur['id_joueur'] ?>" 
                               class="btn-action btn-view">
                                <i class="fas fa-eye"></i> Voir
                            </a>
                            
                            <a href="modifier_joueur.php?id_joueur=<?= $joueur["id_joueur"] ?>" 
                               class="btn-action btn-edit">
                                <i class="fas fa-edit"></i> Modifier
                            </a>
                            
                            <a href="supprimer_joueur.php?id_joueur=<?= $joueur["id_joueur"] ?>"
                               class="btn-action btn-delete"
                               onclick="return confirm('⚠️ Êtes-vous sûr de vouloir supprimer ce joueur ?\n\nCette action supprimera également tous ses commentaires et participations aux matchs.');">
                                <i class="fas fa-trash"></i> Supprimer
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="fas fa-user-slash"></i>
                    </div>
                    <h2 class="empty-title">Aucun joueur enregistré</h2>
                    <p class="empty-text">
                        Commencez par ajouter des joueurs à votre effectif pour pouvoir gérer votre équipe.
                    </p>
                    <a href="ajouter_joueur.php" class="btn-add-player">
                        <i class="fas fa-plus-circle"></i> Ajouter votre premier joueur
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // =============================
    // FILTRES ET RECHERCHE
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('searchPlayers');
        const filterStatus = document.getElementById('filterStatus');
        const sortSelect = document.getElementById('sortPlayers');
        
        function applyFilters() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusFilter = filterStatus.value;
            const sortBy = sortSelect.value;
            
            const playerCards = document.querySelectorAll('.player-card');
            const cardsArray = Array.from(playerCards);
            
            // Filtrer les cartes
            cardsArray.forEach(card => {
                let show = true;
                const playerName = card.dataset.name;
                const playerStatus = card.dataset.status;
                
                // Filtre par recherche
                if (searchTerm && !playerName.includes(searchTerm)) {
                    show = false;
                }
                
                // Filtre par statut
                if (statusFilter !== 'all' && playerStatus !== statusFilter) {
                    show = false;
                }
                
                // Afficher ou cacher
                card.style.display = show ? 'block' : 'none';
            });
            
            // Trier les cartes visibles
            sortPlayers(cardsArray.filter(card => card.style.display !== 'none'), sortBy);
        }
        
        function sortPlayers(cards, sortBy) {
            const playersList = document.getElementById('playersList');
            
            cards.sort((a, b) => {
                switch(sortBy) {
                    case 'nom':
                        return a.dataset.name.localeCompare(b.dataset.name);
                        
                    case 'note_desc':
                        return parseFloat(b.dataset.rating) - parseFloat(a.dataset.rating);
                        
                    case 'matchs_desc':
                        return parseInt(b.dataset.matchs) - parseInt(a.dataset.matchs);
                        
                    case 'age_asc':
                        const ageA = parseInt(a.dataset.age) || 999;
                        const ageB = parseInt(b.dataset.age) || 999;
                        return ageA - ageB;
                        
                    default:
                        return 0;
                }
            });
            
            // Réorganiser dans le DOM
            cards.forEach(card => {
                playersList.appendChild(card);
            });
        }
        
        // Événements
        searchInput.addEventListener('input', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
        sortSelect.addEventListener('change', applyFilters);
        
        // Animation au scroll
        function animateOnScroll() {
            const cards = document.querySelectorAll('.player-card');
            
            cards.forEach(card => {
                const cardTop = card.getBoundingClientRect().top;
                const windowHeight = window.innerHeight;
                
                if (cardTop < windowHeight - 100) {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }
            });
        }
        
        window.addEventListener('scroll', animateOnScroll);
        animateOnScroll(); // Initial call
    });
    
    // =============================
    // ANIMATION AU SURVOL DES CARTES
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        const playerCards = document.querySelectorAll('.player-card');
        
        playerCards.forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.boxShadow = '0 25px 50px rgba(0,0,0,0.2)';
                this.style.zIndex = '10';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.boxShadow = 'var(--shadow)';
                this.style.zIndex = '1';
            });
        });
    });
    
    // =============================
    // NOTIFICATION VISUELLE
    // =============================
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? 'var(--secondary)' : 
                        type === 'warning' ? 'var(--accent)' : 
                        type === 'error' ? 'var(--danger)' : 'var(--info)'};
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            z-index: 1000;
            transform: translateX(100%);
            opacity: 0;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        `;
        
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check-circle' : 
                              type === 'warning' ? 'exclamation-triangle' : 
                              type === 'error' ? 'times-circle' : 'info-circle'}"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
            notification.style.opacity = '1';
        }, 100);
        
        setTimeout(() => {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 5000);
    }
    
    // Exemple de notification basée sur les données
    <?php 
    $joueurs_blesses = array_filter($joueurs, fn($j) => $j['statut_code'] === 'BLE');
    if (!empty($joueurs_blesses)): ?>
        setTimeout(() => {
            showNotification('⚠️ <?= count($joueurs_blesses) ?> joueur(s) blessé(s) dans l\'effectif', 'warning');
        }, 2000);
    <?php endif; ?>
    </script>
</body>
</html>
<?php include "../includes/footer.php"; ?>