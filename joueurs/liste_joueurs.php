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

// Filtrage et Tri PHP
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? 'all';
$sortBy = $_GET['sort'] ?? 'nom';

if ($search || $statusFilter !== 'all') {
    $joueurs = array_filter($joueurs, function($j) use ($search, $statusFilter) {
        $matchSearch = true;
        if ($search) {
            $fullName = strtolower($j['nom'] . ' ' . $j['prenom']);
            $matchSearch = strpos($fullName, strtolower($search)) !== false;
        }
        
        $matchStatus = true;
        if ($statusFilter !== 'all') {
            $matchStatus = $j['statut_code'] === $statusFilter;
        }
        
        return $matchSearch && $matchStatus;
    });
}

usort($joueurs, function($a, $b) use ($sortBy) {
    switch ($sortBy) {
        case 'note_desc':
            return ($b['note_moyenne'] ?? 0) <=> ($a['note_moyenne'] ?? 0);
        case 'matchs_desc':
            return ($b['nb_matchs'] ?? 0) <=> ($a['nb_matchs'] ?? 0);
        case 'age_asc':
            $ageA = is_numeric($a['age']) ? $a['age'] : 999;
            $ageB = is_numeric($b['age']) ? $b['age'] : 999;
            return $ageA <=> $ageB;
        case 'nom':
        default:
            return strcasecmp($a['nom'], $b['nom']);
    }
});

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
    <link rel="stylesheet" href="../assets/css/liste_joueurs.css">
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
        <form method="GET" class="filters-container">
            <div class="search-box">
                <input type="text" 
                       name="search"
                       class="search-input" 
                       placeholder="Rechercher un joueur..." 
                       value="<?= htmlspecialchars($search) ?>">
                <button type="submit" style="background:none; border:none; position:absolute; right:20px; top:50%; transform:translateY(-50%); cursor:pointer; color:var(--gray);">
                    <i class="fas fa-search"></i>
                </button>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Filtrer par statut</label>
                <select name="status" class="filter-select">
                    <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                    <?php foreach ($stats as $stat): ?>
                        <option value="<?= $stat['code'] ?>" <?= $statusFilter === $stat['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($stat['libelle']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label class="filter-label">Trier par</label>
                <select name="sort" class="filter-select">
                    <option value="nom" <?= $sortBy === 'nom' ? 'selected' : '' ?>>Nom A-Z</option>
                    <option value="note_desc" <?= $sortBy === 'note_desc' ? 'selected' : '' ?>>Note décroissante</option>
                    <option value="matchs_desc" <?= $sortBy === 'matchs_desc' ? 'selected' : '' ?>>Matchs joués</option>
                    <option value="age_asc" <?= $sortBy === 'age_asc' ? 'selected' : '' ?>>Âge croissant</option>
                </select>
            </div>

            <button type="submit" class="btn-add-player" style="border:none; cursor:pointer; padding: 12px 24px;">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </form>

        <!-- NOTIFICATIONS -->
        <?php 
        $joueurs_blesses = array_filter($joueurs, fn($j) => $j['statut_code'] === 'BLE');
        if (!empty($joueurs_blesses)): ?>
            <div style="background: linear-gradient(135deg, #fff8e1, #ffe082); color: #f57c00; padding: 15px 25px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; box-shadow: var(--shadow);">
                <i class="fas fa-band-aid"></i>
                <span>⚠️ <strong><?= count($joueurs_blesses) ?></strong> joueur(s) blessé(s) dans l'effectif actuel</span>
            </div>
        <?php endif; ?>

        <!-- LISTE DES JOUEURS -->
        <div class="players-grid">
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
                            <a href="joueur_perso.php?id=<?= $joueur['id_joueur'] ?>" class="btn-action btn-view">
                            <i class="fas fa-eye"></i> Voir
                            </a>
                            
                            <a href="modifier_joueur.php?id=<?= $joueur['id_joueur'] ?>" 
       class="btn-action btn-modify">
        <i class="fas fa-edit"></i> Modifier
    </a>
                            
                            <a href="supprimer_joueur.php"
                            class="btn-action btn-delete">
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
</body>
</html>
<?php include "../includes/footer.php"; ?>