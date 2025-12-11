<?php
require_once __DIR__ . "/includes/auth_check.php";
require_once __DIR__ . "/includes/config.php";
include __DIR__ . "/includes/header.php";

/***************************************************
 * RÉSUMÉ GLOBAL DE L'ÉQUIPE (Accueil)
 ***************************************************/

// Nombre de joueurs
$stmt = $gestion_sportive->query("SELECT COUNT(*) AS total FROM joueur");
$nb_joueurs = $stmt->fetch()["total"];

// Nombre de matchs
$stmt = $gestion_sportive->query("SELECT COUNT(*) AS total FROM matchs");
$nb_matchs = $stmt->fetch()["total"];

// Matchs à préparer
$stmt = $gestion_sportive->query("SELECT COUNT(*) AS total FROM matchs WHERE etat = 'A_PREPARER'");
$matchs_a_preparer = $stmt->fetch()["total"];

// Matchs préparés mais pas encore joués
$stmt = $gestion_sportive->query("SELECT COUNT(*) AS total FROM matchs WHERE etat = 'PREPARE'");
$matchs_prepares = $stmt->fetch()["total"];

// Prochain match
$stmt = $gestion_sportive->query("
    SELECT * FROM matchs 
    WHERE etat != 'JOUE'
    ORDER BY date_heure ASC 
    LIMIT 1
");
$prochain_match = $stmt->fetch();

// Matchs déjà joués (limité à 5 récents)
$stmt = $gestion_sportive->query("
    SELECT * FROM matchs 
    WHERE etat = 'JOUE'
    ORDER BY date_heure DESC 
    LIMIT 5
");
$matchs_joues = $stmt->fetchAll();

?>

<div class="container">
    <h1>🏠 Tableau de bord</h1>
    <p>Bienvenue sur l'application de gestion de votre équipe !</p>

    <hr>

    <h2>📌 Résumé global</h2>
    <ul>
        <li><strong>Nombre de joueurs :</strong> <?= $nb_joueurs ?></li>
        <li><strong>Nombre de matchs :</strong> <?= $nb_matchs ?></li>
        <li><strong>Matchs à préparer :</strong> <?= $matchs_a_preparer ?></li>
        <li><strong>Matchs préparés :</strong> <?= $matchs_prepares ?></li>
    </ul>

    <hr>

    <h2>📅 Prochain match</h2>
    <?php if ($prochain_match): ?>
        <p>
            <strong><?= date("d/m/Y H:i", strtotime($prochain_match["date_heure"])) ?></strong><br>
            Adversaire : <strong><?= htmlspecialchars($prochain_match["adversaire"]) ?></strong><br>
            Lieu : <?= htmlspecialchars($prochain_match["lieu"]) ?><br>
            État : <strong><?= htmlspecialchars($prochain_match["etat"]) ?></strong><br><br>

            <a href="/matchs/modifier_match.php?id=<?= $prochain_match["id_match"] ?>">✏️ Modifier match</a> |
            <a href="/feuille_match/composer.php?id=<?= $prochain_match["id_match"] ?>">📝 Faire la feuille de match</a>
        </p>
    <?php else: ?>
        <p>Aucun match à venir.</p>
    <?php endif; ?>

    <hr>

    <h2>🏆 Matchs récemment joués</h2>
    <?php if (count($matchs_joues) > 0): ?>
        <ul>
            <?php foreach ($matchs_joues as $m): ?>
                <li>
                    <?= date("d/m/Y", strtotime($m["date_heure"])) ?> - 
                    vs <strong><?= htmlspecialchars($m["adversaire"]) ?></strong> :
                    <strong><?= $m["score_equipe"] ?> - <?= $m["score_adverse"] ?></strong>
                    (<?= strtolower($m["resultat"]) ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Aucun match joué pour l’instant.</p>
    <?php endif; ?>

</div>

<?php include __DIR__ . "/includes/footer.php"; ?>
