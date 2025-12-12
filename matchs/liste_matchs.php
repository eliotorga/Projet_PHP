<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_match.php";

include "../includes/header.php";

// Récupération de tous les matchs
$matchs = getAllMatches($gestion_sportive);
?>

<h2>Liste des matchs</h2>

<table class="table" border="1" cellpadding="8" cellspacing="0" width="100%">
    <thead>
        <tr style="background:#f2f2f2; text-align:center;">
            <th>Date</th>
            <th>Adversaire</th>
            <th>Lieu</th>
            <th>Résultat</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($matchs as $match): ?>
        <tr style="text-align:center;">

            <!-- DATE & HEURE -->
            <td><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></td>

            <!-- ADVERSAIRE -->
            <td><?= htmlspecialchars($match["adversaire"]) ?></td>

            <!-- LIEU -->
            <td><?= htmlspecialchars($match["lieu"]) ?></td>

            <!-- RÉSULTAT -->
            <td>
                <?php if ($match["resultat"] === null): ?>
                    -
                <?php else: ?>
                    <?php
                    $color = "black";
                    if ($match["resultat"] === "VICTOIRE") $color = "green";
                    if ($match["resultat"] === "DEFAITE")  $color = "red";
                    if ($match["resultat"] === "NUL")      $color = "orange";
                    ?>
                    <span style="color:<?= $color ?>; font-weight:bold;">
                        <?= $match["resultat"] ?>
                    </span>
                <?php endif; ?>
            </td>

            <!-- STATUT (A_PREPARER / PREPARE / JOUE) -->
            <td>
                <?php if ($match["etat"] === "A_PREPARER"): ?>
                    <span style="color:red; font-weight:bold;">■ Non préparé</span>

                <?php elseif ($match["etat"] === "PREPARE"): ?>
                    <span style="color:orange; font-weight:bold;">★ Préparé</span>

                <?php elseif ($match["etat"] === "JOUE"): ?>
                    <span style="color:gold; font-weight:bold;">⭐ Évalué</span>
                <?php endif; ?>
            </td>

            <!-- ACTIONS -->
            <td style="text-align:center;">

                <!-- Modifier -->
                <a href="modifier_match.php?id_match=<?= $match["id_match"] ?>" 
                   style="margin-right:10px;">✏️ Modifier</a>

                <!-- Résultat -->
                <a href="resultat_match.php?id_match=<?= $match["id_match"] ?>" 
                   style="margin-right:10px;">🎯 Résultat</a>

                <!-- Feuille de match (composition) -->
                <a href="../feuille_match/composition.php?id_match=<?= $match["id_match"] ?>">
                    📋 Feuille
                </a>

            </td>

        </tr>
    <?php endforeach; ?>
    </tbody>

</table>

<?php include "../includes/footer.php"; ?>
