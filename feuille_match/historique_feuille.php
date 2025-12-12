<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_match.php";
require_once "../bdd/db_participation.php";

include "../includes/header.php";

// Tous les matchs (JOUE et PREPARE)
$matches = getAllMatches($gestion_sportive);
?>

<h2>Historique des compositions</h2>

<?php foreach ($matches as $match): ?>

    <div class="card" style="margin-bottom: 25px; padding: 20px;">

        <!-- TITRE DU MATCH -->
        <h3>
            Match vs 
            <strong><?= htmlspecialchars($match["adversaire"]) ?></strong><br>
            — <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
        </h3>

        <!-- RÉSULTAT -->
        <p>
            <strong>Résultat :</strong>
            <?php if ($match["resultat"] === null): ?>
                <span style="color: grey;">Non renseigné</span>
            <?php else: ?>
                <?php if ($match["resultat"] === "VICTOIRE") $color = "green";
                      elseif ($match["resultat"] === "DEFAITE") $color = "red";
                      else $color = "orange"; ?>
                <span style="color: <?= $color ?>; font-weight: bold;">
                    <?= $match["resultat"] ?>
                </span>
            <?php endif; ?>
        </p>

        <?php
        // Récupération des joueurs de ce match
        $compo = getParticipationByMatch($gestion_sportive, $match["id_match"]);

        if (empty($compo)): ?>
            <p style="color:red;">Aucune composition enregistrée.</p>
            </div>
            <?php continue; ?>
        <?php endif; ?>

        <!-- TABLEAU DES JOUEURS -->
        <table class="table" border="1" cellpadding="6" cellspacing="0" width="100%">
            <thead>
                <tr>
                    <th>Joueur</th>
                    <th>Poste</th>
                    <th>Rôle</th>
                    <th>Évaluation</th>
                </tr>
            </thead>

            <tbody>
            <?php foreach ($compo as $ligne): ?>
                <tr>
                    <td><?= htmlspecialchars($ligne["prenom"] . " " . $ligne["nom"]) ?></td>
                    <td><?= $ligne["poste_libelle"] ?? "-" ?></td>
                    <td><?= htmlspecialchars($ligne["role"]) ?></td>
                    <td><?= $ligne["evaluation"] !== null ? $ligne["evaluation"] : "-" ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

    </div>

<?php endforeach; ?>

<?php include "../includes/footer.php"; ?>
