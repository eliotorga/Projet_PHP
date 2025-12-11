<?php
// Menu de navigation
require_once __DIR__ . '/auth.php'; // Chemin correct depuis le dossier includes

// Vérifier la connexion BDD
$db_status = false;
if (file_exists(__DIR__ . '/../BDD/config.php')) {
    require_once __DIR__ . '/../BDD/config.php';
    if (function_exists('isDatabaseConnected')) {
        $db_status = isDatabaseConnected();
    }
}
?>

<nav class="menu">
    <ul>
        <li><a href="../index.php"><i class="fas fa-home"></i> Accueil</a></li>
        
        <?php if ($db_status): ?>
            <li><a href="../joueurs/liste_joueurs.php"><i class="fas fa-users"></i> Joueurs</a></li>
            <li><a href="../matchs/liste_matchs.php"><i class="fas fa-calendar-alt"></i> Matchs</a></li>
            <li><a href="../feuilles/composer_match.php"><i class="fas fa-clipboard-list"></i> Feuilles de Match</a></li>
            <li><a href="../stats/stats_equipes_joueurs.php"><i class="fas fa-chart-bar"></i> Statistiques</a></li>
        <?php else: ?>
            <li><a href="#" onclick="alert('Cette fonctionnalité nécessite la base de données.'); return false;" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-users"></i> Joueurs <small>(hors ligne)</small>
            </a></li>
            <li><a href="#" onclick="alert('Cette fonctionnalité nécessite la base de données.'); return false;" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-calendar-alt"></i> Matchs <small>(hors ligne)</small>
            </a></li>
            <li><a href="#" onclick="alert('Cette fonctionnalité nécessite la base de données.'); return false;" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-clipboard-list"></i> Feuilles <small>(hors ligne)</small>
            </a></li>
            <li><a href="#" onclick="alert('Cette fonctionnalité nécessite la base de données.'); return false;" style="opacity: 0.6; cursor: not-allowed;">
                <i class="fas fa-chart-bar"></i> Statistiques <small>(hors ligne)</small>
            </a></li>
        <?php endif; ?>
        
        <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a></li>
    </ul>
</nav>

<div class="user-info">
    <p>
        <i class="fas fa-user"></i> Connecté: <strong><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Invité'); ?></strong>
        <?php if (isset($db_status)): ?>
            <span style="margin-left: 15px; font-size: 0.9em;">
                <i class="fas fa-database" style="color: <?php echo $db_status ? '#28a745' : '#dc3545'; ?>"></i>
                BDD: <?php echo $db_status ? 'Connectée' : 'Hors ligne'; ?>
            </span>
        <?php endif; ?>
    </p>
</div>

<style>
    .user-info {
        background: #ecf0f1;
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-size: 0.9em;
        color: #7f8c8d;
    }
    
    .user-info i {
        color: #3498db;
        margin-right: 5px;
    }
</style>