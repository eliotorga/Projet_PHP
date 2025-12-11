<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_participation.php";

include __DIR__ . "/../includes/header.php";

// Vérification ID du match
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

// Récupérer la composition du match
$participations = getParticipationByMatch($gestion_sportive, $id_match);

if (empty($participations)) {
    die("<p style='color:red; font-weight:bold;'>Aucune feuille de match n'a encore été créée pour ce match.</p>");
}

// Séparer titulaires / remplaçants
$titulaires = [];
$remplacants = [];

foreach ($participations as $p) {
    if ($p["role"] === "TITULAIRE") $titulaires[] = $p;
    else $remplacants[] = $p;
}
?>

<div class="container">

    <h1>📄 Feuille de match</h1>

    <h2 style="margin-top:10px;">
        Match du <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
    </h2>

    <p>
        Adversaire : <strong><?= htmlspecialchars($match["adversaire"]) ?></strong><br>
        Lieu : <?= htmlspecialchars($match["lieu"]) ?><br>
        État : <strong><?= htmlspecialchars($match["etat"]) ?></strong>
    </p>

    <hr>

    <h2>🏆 Titulaires</h2>

    <?php if (empty($titulaires)) : ?>
        <p>Aucun titulaire défini.</p>
    <?php else: ?>

        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr style="background:#ddd;">
                <th>Joueur</th>
                <th>Poste</th>
                <?php if ($match["etat"] === "JOUE"): ?>
                    <th>Évaluation</th>
                <?php endif; ?>
            </tr>

            <?php foreach ($titulaires as $t): ?>
                <tr>
                    <td><?= htmlspecialchars($t["prenom"] . " " . $t["nom"]) ?></td>
                    <td><?= htmlspecialchars($t["poste_libelle"] ?? "-") ?></td>

                    <?php if ($match["etat"] === "JOUE"): ?>
                        <td><?= $t["evaluation"] !== null ? $t["evaluation"] . " ⭐" : "-" ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </table>

    <?php endif; ?>

    <br>

    <h2>🔄 Remplaçants</h2>

    <?php if (empty($remplacants)) : ?>
        <p>Aucun remplaçant défini.</p>
    <?php else: ?>

        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr style="background:#ddd;">
                <th>Joueur</th>
                <?php if ($match["etat"] === "JOUE"): ?>
                    <th>Évaluation</th>
                <?php endif; ?>
            </tr>

            <?php foreach ($remplacants as $r): ?>
                <tr>
                    <td><?= htmlspecialchars($r["prenom"] . " " . $r["nom"]) ?></td>

                    <?php if ($match["etat"] === "JOUE"): ?>
                        <td><?= $r["evaluation"] !== null ? $r["evaluation"] . " ⭐" : "-" ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>

        </table>

    <?php endif; ?>

    <br><br>

    <a href="../matchs/liste_matchs.php"
       style="text-decoration:none; padding:10px 20px; background:#ddd; border-radius:6px;">
        ↩️ Retour aux matchs
    </a>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
