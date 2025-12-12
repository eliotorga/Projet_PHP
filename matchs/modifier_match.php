<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

require_once "../bdd/db_match.php";

if (!isset($_GET["id_match"])) {
    header("Location: liste_matchs.php");
    exit;
}

$id_match = intval($_GET["id_match"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<h3 style='color:red;'>❌ Match introuvable.</h3>");
}

include "../includes/header.php";
?>

<h2>✏️ Modifier le match</h2>

<form method="POST" action="save_modifier_match.php" class="card" style="padding:20px;">
    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <!-- DATE & HEURE -->
    <label>Date et heure :</label><br>
    <input type="datetime-local" name="date_heure"
           value="<?= date('Y-m-d\TH:i', strtotime($match["date_heure"])) ?>"
           required>
    <br><br>

    <!-- ADVERSAIRE -->
    <label>Adversaire :</label><br>
    <input type="text" name="adversaire" value="<?= htmlspecialchars($match["adversaire"]) ?>" required>
    <br><br>

    <!-- LIEU -->
    <label>Lieu :</label><br>
    <select name="lieu" required>
        <option value="DOMICILE" <?= $match["lieu"] === "DOMICILE" ? "selected" : "" ?>>Domicile</option>
        <option value="EXTERIEUR" <?= $match["lieu"] === "EXTERIEUR" ? "selected" : "" ?>>Extérieur</option>
    </select>
    <br><br>

    <!-- SCORE (si match joué ou si prof veut déjà préparer) -->
    <label>Score (optionnel) :</label><br>
    <input type="number" name="score_equipe" placeholder="Nous"
           value="<?= htmlspecialchars($match["score_equipe"]) ?>">
    <input type="number" name="score_adverse" placeholder="Adversaire"
           value="<?= htmlspecialchars($match["score_adverse"]) ?>">
    <br><br>

    <!-- RÉSULTAT (optionnel si pas encore joué) -->
    <label>Résultat du match :</label><br>
    <select name="resultat">
        <option value="">— Aucun —</option>
        <option value="VICTOIRE" <?= $match["resultat"] === "VICTOIRE" ? "selected" : "" ?>>Victoire</option>
        <option value="DEFAITE" <?= $match["resultat"] === "DEFAITE" ? "selected" : "" ?>>Défaite</option>
        <option value="NUL" <?= $match["resultat"] === "NUL" ? "selected" : "" ?>>Match nul</option>
    </select>
    <br><br>

    <!-- ÉTAT DU MATCH -->
    <label>Statut du match :</label><br>
    <select name="etat" required>
        <option value="A_PREPARER" <?= $match["etat"] === "A_PREPARER" ? "selected" : "" ?>>À préparer</option>
        <option value="PREPARE"   <?= $match["etat"] === "PREPARE" ? "selected" : "" ?>>Préparé</option>
        <option value="JOUE"      <?= $match["etat"] === "JOUE" ? "selected" : "" ?>>Joué</option>
    </select>
    <br><br>

    <button type="submit" class="btn-primary">💾 Enregistrer les modifications</button>
    <a href="liste_matchs.php" class="btn-secondary" style="margin-left:10px;">↩ Retour</a>

</form>

<?php include "../includes/footer.php"; ?>
