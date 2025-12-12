<?php
require_once "includes/auth_check.php";
require_once "includes/config.php";

/* =====================
   DONNÉES DASHBOARD
===================== */

/* Joueurs actifs */
$nbJoueursActifs = $gestion_sportive->query("
    SELECT COUNT(*)
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE s.code = 'ACT'
")->fetchColumn();

/* Matchs à venir */
$nbMatchsAVenir = $gestion_sportive->query("
    SELECT COUNT(*)
    FROM matchs
    WHERE etat IN ('A_PREPARER', 'PREPARE')
")->fetchColumn();


/* % Victoires */
$totalJoues = $gestion_sportive->query("
    SELECT COUNT(*) FROM matchs WHERE resultat IS NOT NULL
")->fetchColumn();

$victoires = $gestion_sportive->query("
    SELECT COUNT(*) FROM matchs WHERE resultat = 'VICTOIRE'
")->fetchColumn();

$pctVictoires = $totalJoues > 0 ? round(($victoires / $totalJoues) * 100) : 0;

/* Prochain match */
$prochainMatch = $gestion_sportive->query("
    SELECT id_match, date_heure, adversaire
    FROM matchs
    WHERE etat IN ('A_PREPARER', 'PREPARE')
    ORDER BY date_heure ASC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);


/* Dernier match joué */
$dernierMatch = $gestion_sportive->query("
    SELECT id_match, date_heure, adversaire, resultat
    FROM matchs
    WHERE resultat IS NOT NULL
    ORDER BY date_heure DESC
    LIMIT 1
")->fetch(PDO::FETCH_ASSOC);

include "includes/header.php";
?>

<style>
/* =====================
   DA FOOT – GLOBAL
===================== */
body {
    background: linear-gradient(180deg, #0f2027, #203a43, #2c5364);
    color: #fff;
}

h1, h2, h3 {
    margin: 0;
}

.container {
    max-width: 1200px;
    margin: auto;
    padding: 20px;
}

/* =====================
   HERO
===================== */
.hero {
    background: linear-gradient(135deg, #1e7c3a, #0b3d1e);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.4);
}

.hero h1 {
    font-size: 2.5em;
}

.hero p {
    opacity: 0.9;
    margin-top: 10px;
}

/* =====================
   ACTIONS PRINCIPALES
===================== */
.actions {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
    margin-bottom: 30px;
}

.action-card {
    background: rgba(255,255,255,0.08);
    backdrop-filter: blur(10px);
    border-radius: 18px;
    padding: 25px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.action-card h3 {
    font-size: 1.4em;
    margin-bottom: 10px;
}

.action-card p {
    opacity: 0.85;
}

.btn {
    display: inline-block;
    margin-top: 15px;
    padding: 14px 22px;
    border-radius: 12px;
    font-weight: bold;
    text-decoration: none;
    color: #fff;
    transition: transform 0.2s, box-shadow 0.2s;
}

.btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.4);
}

.btn-green { background: linear-gradient(135deg, #2ecc71, #27ae60); }
.btn-red   { background: linear-gradient(135deg, #e74c3c, #c0392b); }

/* =====================
   STATS
===================== */
.stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat {
    background: rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 20px;
    text-align: center;
}

.stat h2 {
    font-size: 2.5em;
}

.stat span {
    opacity: 0.8;
}

/* =====================
   SHORTCUTS
===================== */
.shortcuts {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
}

.shortcut {
    background: rgba(255,255,255,0.08);
    padding: 20px;
    border-radius: 14px;
    text-align: center;
}
</style>

<div class="container">

    <!-- HERO -->
    <div class="hero">
        <h1>⚽ Tableau de bord de l’entraîneur</h1>
        <p>Préparez vos matchs, gérez votre effectif et analysez les performances de votre équipe.</p>
    </div>

    <!-- ACTIONS MAJEURES -->
    <div class="actions">

        <div class="action-card">
            <h3>⏳ Prochain match à préparer</h3>
            <?php if ($prochainMatch): ?>
                <p>
                    <strong><?= htmlspecialchars($prochainMatch["adversaire"]) ?></strong><br>
                    <?= date("d/m/Y H:i", strtotime($prochainMatch["date_heure"])) ?>
                </p>
                <a href="feuille_match/composition.php?id_match=<?= $prochainMatch["id_match"] ?>"
                   class="btn btn-green">
                    ⚽ Composer la feuille
                </a>
            <?php else: ?>
                <p>Aucun match à venir.<br>Ajoutez un match pour préparer l’équipe.</p>
            <?php endif; ?>
        </div>

        <div class="action-card">
            <h3>⭐ Dernier match joué</h3>
            <?php if ($dernierMatch): ?>
                <p>
                    <strong><?= htmlspecialchars($dernierMatch["adversaire"]) ?></strong><br>
                    <?= date("d/m/Y H:i", strtotime($dernierMatch["date_heure"])) ?><br>
                    Résultat : <?= $dernierMatch["resultat"] ?>
                </p>
                <a href="feuille_match/evaluation.php?id_match=<?= $dernierMatch["id_match"] ?>"
                   class="btn btn-red">
                    ⭐ Évaluer les joueurs
                </a>
            <?php else: ?>
                <p>Aucun match joué pour le moment.</p>
            <?php endif; ?>
        </div>

    </div>

    <!-- STATS -->
    <div class="stats">
        <div class="stat">
            <h2><?= $nbJoueursActifs ?></h2>
            <span>Joueurs actifs</span>
        </div>
        <div class="stat">
            <h2><?= $nbMatchsAVenir ?></h2>
            <span>Matchs à venir</span>
        </div>
        <div class="stat">
            <h2><?= $pctVictoires ?>%</h2>
            <span>Taux de victoires</span>
        </div>
    </div>

    <!-- RACCOURCIS -->
    <div class="shortcuts">
        <div class="shortcut">
            👥<br><strong>Gestion des joueurs</strong><br>
            <a href="joueurs/liste_joueurs.php" class="btn btn-green">Accéder</a>
        </div>
        <div class="shortcut">
            📅<br><strong>Gestion des matchs</strong><br>
            <a href="matchs/liste_matchs.php" class="btn btn-green">Accéder</a>
        </div>
        <div class="shortcut">
            📊<br><strong>Statistiques</strong><br>
            <a href="stats/stats_equipe.php" class="btn btn-green">Accéder</a>
        </div>
    </div>

</div>

<?php include "includes/footer.php"; ?>
