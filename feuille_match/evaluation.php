<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* Vérification ID match */
if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}
$id_match = (int) $_GET["id_match"];

/* Récupération du match */
$stmt = $gestion_sportive->prepare("
    SELECT date_heure, adversaire, lieu, resultat
    FROM matchs
    WHERE id_match = ?
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match introuvable.");
}

/* Récupération des joueurs ayant participé */
$stmt = $gestion_sportive->prepare("
    SELECT 
        p.id_joueur,
        p.role,
        p.evaluation,
        j.nom,
        j.prenom,
        po.libelle AS poste
    FROM participation p
    JOIN joueur j ON j.id_joueur = p.id_joueur
    LEFT JOIN poste po ON po.id_poste = p.id_poste
    WHERE p.id_match = ?
    ORDER BY p.role DESC, po.libelle
");
$stmt->execute([$id_match]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Enregistrement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $resultat = $_POST["resultat"] ?? null;
    $evaluations = $_POST["evaluation"] ?? [];

    /* Mise à jour du résultat + état du match */
    $stmt = $gestion_sportive->prepare("
        UPDATE matchs
        SET resultat = ?, etat = 'JOUE'
        WHERE id_match = ?
    ");
    $stmt->execute([$resultat, $id_match]);

    /* Mise à jour des évaluations */
    $stmtEval = $gestion_sportive->prepare("
        UPDATE participation
        SET evaluation = ?
        WHERE id_match = ? AND id_joueur = ?
    ");

    foreach ($evaluations as $id_joueur => $note) {
        if ($note !== "") {
            $stmtEval->execute([(int)$note, $id_match, (int)$id_joueur]);
        }
    }

    header("Location: ../matchs/liste_matchs.php");
    exit;
}

include "../includes/header.php";
?>

<style>
.card {
    background: #fff;
    padding: 20px;
    border-radius: 14px;
    box-shadow: 0 6px 18px rgba(0,0,0,0.08);
    max-width: 900px;
}

table {
    width: 100%;
    border-collapse: collapse;
}

th, td {
    padding: 12px;
    border-bottom: 1px solid #eee;
}

th {
    background: #f4f6f8;
    text-align: left;
}

select {
    padding: 6px;
    border-radius: 6px;
}

.btn {
    padding: 10px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
}

.btn-green { background: #2e7d32; color: #fff; }
.btn-grey { background: #607d8b; color: #fff; }

.badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 0.8em;
    font-weight: bold;
}

.badge-titulaire {
    background: #e3f2fd;
    color: #0d47a1;
}

.badge-remplacant {
    background: #fff3cd;
    color: #856404;
}
</style>

<h1>⭐ Évaluation du match</h1>

<div class="card">
<p>
    <strong>Date :</strong> <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?><br>
    <strong>Adversaire :</strong> <?= htmlspecialchars($match["adversaire"]) ?><br>
    <strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"]) ?>
</p>

<form method="post">

    <h3>🏁 Résultat du match</h3>
    <select name="resultat" required>
        <option value="">-- Choisir --</option>
        <option value="VICTOIRE" <?= $match["resultat"] === "VICTOIRE" ? "selected" : "" ?>>Victoire</option>
        <option value="DEFAITE" <?= $match["resultat"] === "DEFAITE" ? "selected" : "" ?>>Défaite</option>
        <option value="NUL" <?= $match["resultat"] === "NUL" ? "selected" : "" ?>>Match nul</option>
    </select>

    <h3 style="margin-top:25px;">👥 Évaluation des joueurs</h3>

    <table>
        <tr>
            <th>Joueur</th>
            <th>Poste</th>
            <th>Rôle</th>
            <th>Note (1 à 5)</th>
        </tr>

        <?php foreach ($participants as $p): ?>
        <tr>
            <td><?= htmlspecialchars($p["nom"] . " " . $p["prenom"]) ?></td>
            <td><?= htmlspecialchars($p["poste"]) ?></td>
            <td>
                <span class="badge <?= $p["role"] === "TITULAIRE" ? "badge-titulaire" : "badge-remplacant" ?>">
                    <?= $p["role"] ?>
                </span>
            </td>
            <td>
                <select name="evaluation[<?= $p["id_joueur"] ?>]">
                    <option value="">-</option>
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <option value="<?= $i ?>" <?= $p["evaluation"] == $i ? "selected" : "" ?>>
                            <?= $i ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <br>

    <button type="submit" class="btn btn-green">💾 Enregistrer</button>
    <a href="../matchs/liste_matchs.php" class="btn btn-grey">⬅ Retour</a>

</form>
</div>

<?php include "../includes/footer.php"; ?>
