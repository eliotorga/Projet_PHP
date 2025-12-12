<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

// Récupération de tous les matchs
$req = $gestion_sportive->query("SELECT * FROM matchs ORDER BY date_heure DESC");
$matchs = $req->fetchAll(PDO::FETCH_ASSOC);

// Préparer les requêtes d'état
$reqHasCompo = $gestion_sportive->prepare("
    SELECT COUNT(*) 
    FROM participation 
    WHERE id_match = ?
");

$reqMissingEval = $gestion_sportive->prepare("
    SELECT COUNT(*)
    FROM participation
    WHERE id_match = ? AND evaluation IS NULL
");
?>

<?php include "../includes/header.php"; ?>
<?php include "../includes/menu.php"; ?>

<h2>Liste des matchs</h2>

<table border="1" cellpadding="8" width="100%">
    <thead>
        <tr>
            <th>Date</th>
            <th>Adversaire</th>
            <th>Lieu</th>
            <th>Résultat</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
        <?php foreach ($matchs as $m): ?>

            <?php
            // 1) Y a-t-il une composition enregistrée ?
            $reqHasCompo->execute([$m["id_match"]]);
            $hasCompo = $reqHasCompo->fetchColumn() > 0;

            // 2) Match passé ?
            $matchPasse = ($m["date_heure"] < date("Y-m-d H:i:s"));

            // 3) Toutes les évaluations sont faites ?
            $reqMissingEval->execute([$m["id_match"]]);
            $missingEval = $reqMissingEval->fetchColumn();
            $isEvaluated = $hasCompo && $matchPasse && ($missingEval == 0);

            // Déterminer le statut
            if ($isEvaluated) {
                $status = "<span style='color:gold; font-weight:bold;'>⭐ Évalué</span>";
            } elseif ($hasCompo) {
                $status = "<span style='color:green; font-weight:bold;'>🟩 Préparé</span>";
            } else {
                $status = "<span style='color:red; font-weight:bold;'>🟥 Non préparé</span>";
            }
            ?>

            <tr>
                <td><?= date("d/m/Y H:i", strtotime($m["date_heure"])) ?></td>
                <td><?= htmlspecialchars($m["equipe_adverse"]) ?></td>
                <td><?= htmlspecialchars($m["lieu"]) ?></td>
                <td><?= $m["resultat"] ?: "-" ?></td>

                <td><?= $status ?></td>

                <td>
                    <!-- Modifier le match -->
                    <a href="modifier_match.php?id_match=<?= $m["id_match"] ?>" class="btn">📝 Modifier</a>

                    <!-- Créer une compo -->
                    <?php if (!$hasCompo && !$matchPasse): ?>
                        <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>" class="btn">➕ Créer compo</a>
                    <?php endif; ?>

                    <!-- Évaluer joueurs -->
                    <?php if ($hasCompo && $matchPasse && !$isEvaluated): ?>
                        <a href="../feuille_match/evaluation.php?id_match=<?= $m["id_match"] ?>" class="btn">⭐ Évaluer</a>
                    <?php endif; ?>

                    <!-- Résultat -->
                    <a href="resultat_match.php?id_match=<?= $m["id_match"] ?>" class="btn">🎯 Résultat</a>
                </td>
            </tr>

        <?php endforeach; ?>
    </tbody>
</table>

<?php include "../includes/footer.php"; ?>
