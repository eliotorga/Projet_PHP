<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";
require_once "../bdd/db_joueur.php";
require_once "../bdd/db_poste.php";

// Vérification ID
if (!isset($_GET["id_match"])) die("Match non spécifié.");
$id_match = intval($_GET["id_match"]);

// Récup match
$stmt = $gestion_sportive->prepare("SELECT * FROM matchs WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();

if (!$match) die("Match introuvable.");

// Récup composition
$compo = getParticipationByMatch($gestion_sportive, $id_match);

// Récup postes pour affichage trié
$postes = getAllPostes($gestion_sportive);

include "../includes/header.php";
?>

<h2>Modifier le match : <?= htmlspecialchars($match["equipe_adverse"]) ?></h2>

<p><strong>Date :</strong> <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></p>
<p><strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"]) ?></p>
<p><strong>Résultat :</strong> <?= $match["resultat"] ?: "<i>Non renseigné</i>" ?></p>

<hr>

<h3>Composition actuelle</h3>

<?php if (count($compo) == 0): ?>
    <p style="color:red;">Aucune composition enregistrée pour ce match.</p>
<?php else: ?>

    <h4>Titulaires</h4>
    <table border="1" cellpadding="8" width="100%">
        <tr>
            <th>Poste</th>
            <th>Joueur</th>
            <th>Taille / Poids</th>
            <th>Rôle</th>
            <th>Dernière note</th>
        </tr>

        <?php foreach ($postes as $p): ?>
            <?php foreach ($compo as $c): ?>
                <?php if ($c["id_poste"] == $p["id_poste"] && $c["role"] == "TITULAIRE"): ?>

                    <?php 
                    // Infos joueur
                    $extra = getPlayerExtraInfo($gestion_sportive, $c["id_joueur"]);
                    $derniere_note = $extra["evaluations"][0]["evaluation"] ?? "<i>Aucune</i>";
                    ?>

                    <tr>
                        <td><?= $p["libelle"] ?></td>
                        <td><?= $c["nom"] . " " . $c["prenom"] ?></td>
                        <td><?= $c["taille"] ?>cm / <?= $c["poids"] ?>kg</td>
                        <td>TITULAIRE</td>
                        <td><?= $derniere_note ?></td>
                    </tr>

                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </table>

    <br>

    <h4>Remplaçants</h4>
    <table border="1" cellpadding="8" width="100%">
        <tr>
            <th>Joueur</th>
            <th>Poste</th>
            <th>Taille / Poids</th>
            <th>Dernière note</th>
        </tr>

        <?php foreach ($compo as $c): ?>
            <?php if ($c["role"] == "REMPLACANT"): ?>

                <?php 
                $extra = getPlayerExtraInfo($gestion_sportive, $c["id_joueur"]);
                $derniere_note = $extra["evaluations"][0]["evaluation"] ?? "<i>Aucune</i>";
                ?>

                <tr>
                    <td><?= $c["nom"] . " " . $c["prenom"] ?></td>
                    <td><?= $c["poste_libelle"] ?></td>
                    <td><?= $c["taille"] ?>cm / <?= $c["poids"] ?>kg</td>
                    <td><?= $derniere_note ?></td>
                </tr>

            <?php endif; ?>
        <?php endforeach; ?>
    </table>

<?php endif; ?>

<hr>

<h3>Actions</h3>

<!-- Modifier la compo -->
<a class="btn" href="../feuille_match/composition.php?id_match=<?= $id_match ?>">
    ✏ Modifier la composition
</a>

<!-- Évaluer (si match passé) -->
<?php if ($match["date_heure"] < date("Y-m-d H:i:s")): ?>
    <a class="btn" href="../feuille_match/evaluation.php?id_match=<?= $id_match ?>">
        ⭐ Évaluer les joueurs
    </a>
<?php endif; ?>

<!-- Historique -->
<a class="btn" href="../feuille_match/historique_feuille.php">
    📜 Historique des compositions
</a>

<?php include "../includes/footer.php"; ?>
