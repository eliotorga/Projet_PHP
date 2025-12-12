<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";

$stmt = $gestion_sportive->query("SELECT * FROM matchs WHERE date_heure < NOW() ORDER BY date_heure DESC");
$matchs = $stmt->fetchAll();

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Historique des compositions</h2>

<?php foreach ($matchs as $m): ?>
    <?php $compo = getParticipationByMatch($gestion_sportive, $m["id_match"]); ?>

    <div style="border:1px solid #ccc; padding:10px; margin-bottom:20px;">
        <h3>Match vs <?= $m["equipe_adverse"] ?> — <?= date("d/m/Y H:i", strtotime($m["date_heure"])) ?></h3>

        <p><strong>Résultat :</strong> <?= $m["resultat"] ?: "Non renseigné" ?></p>

        <?php if (count($compo)==0): ?>
            <p style="color:red;">Aucune composition enregistrée.</p>
        <?php else: ?>
            <table border="1" cellpadding="8" width="100%">
                <thead>
                    <tr>
                        <th>Joueur</th>
                        <th>Poste</th>
                        <th>Rôle</th>
                        <th>Évaluation</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($compo as $p): ?>
                    <tr>
                        <td><?= $p["nom"] . " " . $p["prenom"] ?></td>
                        <td><?= $p["poste_libelle"] ?></td>
                        <td><?= $p["role"] ?></td>
                        <td><?= $p["evaluation"] ?? "<i>Non évalué</i>" ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php endforeach; ?>

<?php include "../includes/footer.php"; ?>
