<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

// Récupération des matchs
$matchs = getAllMatches($gestion_sportive);

// Couleur en fonction de l'état
function colorEtat($etat) {
    return match ($etat) {
        "A_PREPARER" => "red",
        "PREPARE"    => "orange",
        "JOUE"       => "green",
        default      => "black"
    };
}
?>

<div class="container">

    <h1>📅 Liste des matchs</h1>

    <p>
        <a href="ajouter_match.php"
           style="
                padding: 8px 14px; 
                background:#007bff; 
                color:white; 
                border-radius: 6px; 
                text-decoration: none;
           ">➕ Ajouter un match</a>
    </p>

    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr style="background:#ddd;">
            <th>ID</th>
            <th>Date & heure</th>
            <th>Adversaire</th>
            <th>Lieu</th>
            <th>État</th>
            <th>Score</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($matchs as $m): ?>
            <tr>
                <td><?= $m["id_match"] ?></td>

                <td><?= date("d/m/Y H:i", strtotime($m["date_heure"])) ?></td>

                <td><?= htmlspecialchars($m["adversaire"]) ?></td>

                <td><?= htmlspecialchars($m["lieu"]) ?></td>

                <td style="color: <?= colorEtat($m["etat"]); ?>; font-weight:bold;">
                    <?= htmlspecialchars($m["etat"]) ?>
                </td>

                <td>
                    <?php if ($m["etat"] === "JOUE"): ?>
                        <strong><?= $m["score_equipe"] ?> - <?= $m["score_adverse"] ?></strong>
                        (<?= strtolower($m["resultat"]) ?>)
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>

                <td>

                    <!-- Modifier -->
                    <a href="modifier_match.php?id=<?= $m["id_match"] ?>">✏️ Modifier</a> |

                    <!-- Supprimer -->
                    <a href="supprimer_match.php?id=<?= $m["id_match"] ?>"
                       onclick="return confirm('Supprimer ce match ?');"
                       style="color:red;">🗑️ Supprimer</a> |

                    <?php if ($m["etat"] !== "JOUE"): ?>
                        <!-- Composer feuille de match -->
                        <a href="/feuille_match/composer.php?id=<?= $m["id_match"] ?>">📝 Feuille</a> |
                    <?php endif; ?>

                    <?php if ($m["etat"] === "PREPARE"): ?>
                        <!-- Saisir résultat -->
                        <a href="resultat_match.php?id=<?= $m["id_match"] ?>">🏆 Résultat</a>
                    <?php endif; ?>

                </td>

            </tr>
        <?php endforeach; ?>

        <?php if (count($matchs) === 0): ?>
            <tr><td colspan="7" style="text-align:center; padding:15px;">Aucun match enregistré.</td></tr>
        <?php endif; ?>

    </table>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
