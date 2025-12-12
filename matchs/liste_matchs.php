<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =====================
   RÉCUP MATCHS
===================== */
$stmt = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        m.resultat,
        m.etat,
        COUNT(p.id_joueur) AS nb_joueurs
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
");

$matchs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<style>
/* =====================
   PAGE MATCHS – DA
===================== */
.page-title {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

.page-title h1 {
    font-size: 2em;
}

/* =====================
   TABLE CONTAINER
===================== */
.table-container {
    background: #fff;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: #f4f6f8;
}

th, td {
    padding: 16px;
    text-align: left;
    vertical-align: middle;
}

th {
    font-size: 0.9em;
    color: #555;
}

tbody tr {
    border-top: 1px solid #eee;
}

tbody tr:hover {
    background: #f9fbfc;
}

/* =====================
   BADGES
===================== */
.badge {
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 0.8em;
    font-weight: bold;
    white-space: nowrap;
}

.A_PREPARER { background: #fff8e1; color: #f9a825; }
.PREPARE { background: #e3f2fd; color: #1565c0; }
.JOUE { background: #e8f5e9; color: #2e7d32; }

.VICTOIRE { background: #e8f5e9; color: #2e7d32; }
.DEFAITE { background: #ffebee; color: #c62828; }
.NUL { background: #eceff1; color: #455a64; }

/* =====================
   ACTIONS
===================== */
.actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.actions a {
    padding: 8px 12px;
    border-radius: 10px;
    font-size: 0.85em;
    font-weight: bold;
    text-decoration: none;
    color: #fff;
}

.compose { background: #2e7d32; }
.modify { background: #ef6c00; }
.view { background: #546e7a; }
.eval { background: #c62828; }
.edit { background: #1976d2; }
.delete { background: #424242; }

/* =====================
   FOOTER LOGIC
===================== */
.logic {
    margin-top: 25px;
    padding: 15px;
    background: #f4f6f8;
    border-radius: 12px;
    font-style: italic;
}
</style>

<!-- ================= PAGE ================= -->

<div class="page-title">
    <h1>📅 Gestion des matchs</h1>
    <a href="ajouter_match.php" class="btn btn-green">➕ Ajouter un match</a>
</div>

<p>
Cette page est le <strong>cœur de l’application</strong>.  
Elle guide l’entraîneur de la préparation du match jusqu’à l’évaluation et aux statistiques.
</p>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Adversaire</th>
            <th>Lieu</th>
            <th>État</th>
            <th>Résultat</th>
            <th>Actions</th>
        </tr>
    </thead>

    <tbody>
    <?php foreach ($matchs as $m): ?>

        <?php
            $dateMatch = strtotime($m["date_heure"]);
            $now = time();
            $matchAVenir = $dateMatch > $now;
            $compositionComplete = ($m["nb_joueurs"] == 11);
        ?>

        <tr>
            <td>
                <strong><?= date("d/m/Y", $dateMatch) ?></strong><br>
                <span style="opacity:0.7"><?= date("H:i", $dateMatch) ?></span>
            </td>

            <td><?= htmlspecialchars($m["adversaire"]) ?></td>

            <td><?= htmlspecialchars($m["lieu"]) ?></td>

            <td>
                <span class="badge <?= $m["etat"] ?>">
                    <?= str_replace("_", " ", $m["etat"]) ?>
                </span>
            </td>

            <td>
                <?php if ($m["resultat"]): ?>
                    <span class="badge <?= $m["resultat"] ?>">
                        <?= $m["resultat"] ?>
                    </span>
                <?php else: ?>
                    —
                <?php endif; ?>
            </td>

            <td>
                <div class="actions">

                <?php if ($matchAVenir): ?>

                    <?php if (!$compositionComplete): ?>
                        <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>"
                           class="compose">
                           ⚽ Composer
                        </a>
                    <?php else: ?>
                        <a href="../feuille_match/composition.php?id_match=<?= $m["id_match"] ?>"
                           class="modify">
                           ✏️ Modifier
                        </a>
                    <?php endif; ?>

                <?php else: ?>

                    <a href="../feuille_match/voir_composition.php?id_match=<?= $m["id_match"] ?>"
                       class="view">
                       👁️ Voir
                    </a>

                    <a href="../feuille_match/evaluation.php?id_match=<?= $m["id_match"] ?>"
                       class="eval">
                       ⭐ Évaluer
                    </a>

                <?php endif; ?>

                    <a href="modifier_match.php?id_match=<?= $m["id_match"] ?>"
                       class="edit">
                       🛠️
                    </a>

                    <a href="supprimer_match.php?id_match=<?= $m["id_match"] ?>"
                       class="delete"
                       onclick="return confirm('Supprimer ce match ?');">
                       🗑️
                    </a>

                </div>
            </td>
        </tr>

    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="logic">
💡 <strong>Workflow :</strong>  
Match à venir → composition de l’équipe → match joué → évaluation → statistiques.
</div>

<?php include "../includes/footer.php"; ?>
