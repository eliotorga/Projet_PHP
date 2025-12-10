<?php 
require_once __DIR__ . "/includes/header.php"; 
require_once __DIR__ . "/BDD/config.php";

/* ============================================================
   Récupération des données pour le tableau de bord
   ============================================================ */

// Nombre de joueurs actifs
$nbJoueursActifs = $pdo->query("SELECT COUNT(*) FROM joueur WHERE statut='actif'")
                       ->fetchColumn();

// Nombre total de joueurs
$nbJoueursTotal = $pdo->query("SELECT COUNT(*) FROM joueur")
                      ->fetchColumn();

// Prochain match
$prochainMatch = $pdo->query("
    SELECT * FROM match_sportif 
    WHERE date_heure >= NOW()
    ORDER BY date_heure ASC
    LIMIT 1
")->fetch();

// Statistiques générales (gagnés/perdus/nuls)
$stats = $pdo->query("
    SELECT
        SUM(resultat = 'gagne') AS gagne,
        SUM(resultat = 'perdu') AS perdu,
        SUM(resultat = 'nul') AS nul,
        COUNT(*) AS total
    FROM match_sportif
")->fetch();

$total = (int)$stats['total'];
$pcGagne = $total ? round($stats['gagne'] * 100 / $total, 1) : 0;
$pcPerdu = $total ? round($stats['perdu'] * 100 / $total, 1) : 0;
$pcNul = $total ? round($stats['nul'] * 100 / $total, 1) : 0;

?>

<style>
.dashboard {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin: 30px 0;
}

.card {
    background: #f4f4f4;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}

.card h3 {
    margin-top: 0;
}

.section {
    margin-top: 50px;
}

.btn {
    display: inline-block;
    padding: 10px 15px;
    background: #0073ff;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    transition: 0.2s;
}
.btn:hover {
    background: #005cd1;
}
</style>

<h1>Bienvenue dans l’application de Gestion Sportive</h1>
<p>Utilisez le tableau de bord ci-dessous pour gérer les joueurs, les matchs et consulter les statistiques.</p>

<!-- ============================================================
     TABLEAU DE BORD
     ============================================================ -->
<div class="dashboard">

    <div class="card">
        <h3>Joueurs</h3>
        <p><strong><?= $nbJoueursActifs ?></strong> joueurs actifs</p>
        <p><strong><?= $nbJoueursTotal ?></strong> joueurs au total</p>
        <a class="btn" href="joueurs/liste_joueurs.php">Gérer les joueurs</a>
    </div>

    <div class="card">
        <h3>Matchs</h3>
        <?php if ($prochainMatch): ?>
            <p><strong>Prochain match :</strong></p>
            <p><?= date("d/m/Y H:i", strtotime($prochainMatch['date_heure'])) ?></p>
            <p>Adversaire : <strong><?= htmlspecialchars($prochainMatch['equipe_adverse']) ?></strong></p>
            <p>Lieu : <?= htmlspecialchars($prochainMatch['lieu']) ?></p>
        <?php else: ?>
            <p>Aucun match à venir.</p>
        <?php endif; ?>
        <a class="btn" href="matchs/liste_matchs.php">Gérer les matchs</a>
    </div>

    <div class="card">
        <h3>Statistiques équipe</h3>
        <p>Gagnés : <?= $stats['gagne'] ?> (<?= $pcGagne ?>%)</p>
        <p>Perdus : <?= $stats['perdu'] ?> (<?= $pcPerdu ?>%)</p>
        <p>Nuls : <?= $stats['nul'] ?> (<?= $pcNul ?>%)</p>
        <a class="btn" href="statistiques/stats_equipe.php">Voir les statistiques</a>
    </div>

</div>

<!-- ============================================================
     SECTIONS D'ACTION RAPIDE (Navigation)
     ============================================================ -->

<div class="section">
    <h2>Actions rapides</h2>

    <p>
        <a class="btn" href="joueurs/ajouter_joueur.php">➕ Ajouter un joueur</a>
        <a class="btn" href="matchs/ajouter_match.php">➕ Ajouter un match</a>
        <a class="btn" href="statistiques/stats_joueur.php">📊 Statistiques joueurs</a>
    </p>
</div>

<!-- ============================================================
     AIDE POUR LE SUJET
     ============================================================ -->
<div class="section">
    <h2>Informations du projet</h2>
    <ul>
        <li>Gestion des joueurs : affichage, ajout, modification, suppression.</li>
        <li>Gestion des matchs : affichage, ajout, modification, suppression.</li>
        <li>Feuilles de match : sélection titulaires/remplaçants, notes, postes joués.</li>
        <li>Historique des commentaires par joueur.</li>
        <li>Statistiques détaillées : équipe + joueurs.</li>
    </ul>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
