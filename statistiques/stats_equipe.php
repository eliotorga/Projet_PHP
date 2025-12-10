<?php 
require_once "../includes/header.php"; 
require_once "../BDD/config.php"; 

/* ============================================================
   1. STATS BASIQUES : gagnés / perdus / nuls + pourcentages
   ============================================================ */

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
$pcNul   = $total ? round($stats['nul']   * 100 / $total, 1) : 0;

/* ============================================================
   2. MOYENNE DE LA NOTE D’EQUIPE
   ============================================================ */

$moyenneNote = $pdo->query("
    SELECT AVG(note) AS moyenne
    FROM participer
    WHERE note IS NOT NULL
")->fetchColumn();
$moyenneNote = $moyenneNote ? round($moyenneNote, 2) : "-";

/* ============================================================
   3. REPARTITION DES POSTES (titulaires seulement)
   ============================================================ */

$postes = $pdo->query("
    SELECT poste_terrain, COUNT(*) AS nb
    FROM participer
    WHERE titularisation = 1
    GROUP BY poste_terrain
")->fetchAll();

/* ============================================================
   4. Stats sur les compositions
   ============================================================ */

$statsCompos = $pdo->query("
    SELECT
        COUNT(*) AS nb_lignes,
        COUNT(DISTINCT id_match) AS nb_matchs,
        COUNT(*) / COUNT(DISTINCT id_match) AS moyenne_joueurs
    FROM participer
")->fetch();

$moyenneJoueurs = $statsCompos["moyenne_joueurs"]
    ? round($statsCompos["moyenne_joueurs"], 2)
    : "-";

/* ============================================================
   5. Historique récent (5 derniers matchs)
   ============================================================ */

$hist = $pdo->query("
    SELECT date_heure, resultat
    FROM match_sportif
    ORDER BY date_heure DESC
    LIMIT 5
")->fetchAll();

/* ============================================================
   6. Prochain match
   ============================================================ */
$prochainMatch = $pdo->query("
    SELECT *
    FROM match_sportif
    WHERE date_heure >= NOW()
    ORDER BY date_heure ASC
    LIMIT 1
")->fetch();

?>

<style>
.dashboard { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; margin-top: 30px; }
.card {
    background: #f4f4f4;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
.card h3 { margin-top: 0; }
.section { margin-top: 40px; }
ul { padding-left: 20px; }
</style>


<h1>Statistiques de l'Équipe</h1>

<!-- ============================================================
     DASHBOARD : Gagnés / Perdus / Nuls
     ============================================================ -->
<div class="dashboard">

    <div class="card">
        <h3>🏆 Victoires</h3>
        <p><strong><?= $stats['gagne'] ?></strong> matchs</p>
        <p><?= $pcGagne ?>%</p>
    </div>

    <div class="card">
        <h3>❌ Défaites</h3>
        <p><strong><?= $stats['perdu'] ?></strong> matchs</p>
        <p><?= $pcPerdu ?>%</p>
    </div>

    <div class="card">
        <h3>➖ Nuls</h3>
        <p><strong><?= $stats['nul'] ?></strong> matchs</p>
        <p><?= $pcNul ?>%</p>
    </div>

</div>


<!-- ============================================================
     MOYENNES
     ============================================================ -->
<div class="dashboard">

    <div class="card">
        <h3>📊 Nombre total de matchs</h3>
        <p><strong><?= $total ?></strong></p>
    </div>

    <div class="card">
        <h3>⭐ Note moyenne de l'équipe</h3>
        <p style="font-size:22px;"><strong><?= $moyenneNote ?></strong> / 10</p>
    </div>

    <div class="card">
        <h3>👥 Joueurs par match</h3>
        <p><strong><?= $moyenneJoueurs ?></strong></p>
    </div>

</div>


<!-- ============================================================
     REPARTITION DES POSTES
     ============================================================ -->
<div class="section">
    <h2>Répartition des postes (titulaires)</h2>
    <ul>
    <?php foreach ($postes as $p): ?>
        <li><strong><?= htmlspecialchars($p['poste_terrain']) ?></strong> : <?= $p['nb'] ?> sélections</li>
    <?php endforeach; ?>
    </ul>
</div>


<!-- ============================================================
     5 DERNIERS MATCHS
     ============================================================ -->
<div class="section">
    <h2>5 derniers résultats</h2>
    <ul>
    <?php 
        if (empty($hist)) echo "<li>Aucun match</li>";

        foreach ($hist as $h): 
            $icon = "";
            if ($h['resultat']=="gagne") $icon = "🏆 Victoire";
            if ($h['resultat']=="perdu") $icon = "❌ Défaite";
            if ($h['resultat']=="nul")   $icon = "➖ Nul";
    ?>
        <li>
            <?= date("d/m/Y", strtotime($h['date_heure'])) ?> – 
            <strong><?= $icon ?></strong>
        </li>
    <?php endforeach; ?>
    </ul>
</div>


<!-- ============================================================
     PROCHAIN MATCH
     ============================================================ -->
<div class="section">
    <h2>Prochain match</h2>

    <?php if ($prochainMatch): ?>
        <p><strong><?= htmlspecialchars($prochainMatch['equipe_adverse']) ?></strong></p>
        <p><?= date("d/m/Y H:i", strtotime($prochainMatch['date_heure'])) ?></p>
        <p>Lieu : <?= htmlspecialchars($prochainMatch['lieu']) ?></p>
    <?php else: ?>
        <p>Aucun match prévu.</p>
    <?php endif; ?>
</div>

<?php require_once "../includes/footer.php"; ?>
