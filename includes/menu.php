<?php
// Menu visible uniquement si connecté
if (isset($_SESSION["user_id"])) :
?>
    <nav style="
        background:#222; 
        padding: 12px; 
        display:flex; 
        gap:25px;
        align-items:center;
    ">
        <a href="/index.php" style="color:white; text-decoration:none;">🏠 Accueil</a>
        <a href="/joueurs/liste_joueurs.php" style="color:white; text-decoration:none;">👥 Joueurs</a>
        <a href="/matchs/liste_matchs.php" style="color:white; text-decoration:none;">📅 Matchs</a>
        <a href="/feuille_match/composer.php" style="color:white; text-decoration:none;">📝 Feuille de match</a>
        <a href="/stats/stats_equipe.php" style="color:white; text-decoration:none;">📊 Statistiques</a>

        <div style="margin-left:auto;">
            <a href="/logout.php" style="color:#ff4d4d; text-decoration:none; font-weight:bold;">🚪 Déconnexion</a>
        </div>
    </nav>
<?php endif; ?>
