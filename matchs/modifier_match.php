<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

// Vérifier si ID présent
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

$error = "";

// Soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = [
        "date_heure" => $_POST["date_heure"] ?? null,
        "adversaire" => trim($_POST["adversaire"] ?? ""),
        "lieu"       => $_POST["lieu"] ?? "",
        "etat"       => $_POST["etat"] ?? "A_PREPARER"
    ];

    if ($data["date_heure"] === null || $data["adversaire"] === "" || $data["lieu"] === "") {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        updateMatch($gestion_sportive, $id_match, $data);
        header("Location: liste_matchs.php");
        exit;
    }
}
?>

<div class="container">

    <h1>✏️ Modifier un match</h1>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Date & Heure *</label><br>
        <input type="datetime-local" 
               name="date_heure" 
               value="<?= date('Y-m-d\TH:i', strtotime($match['date_heure'])) ?>" 
               required>
        <br><br>

        <label>Adversaire *</label><br>
        <input type="text" 
               name="adversaire" 
               value="<?= htmlspecialchars($match['adversaire']) ?>" 
               required>
        <br><br>

        <label>Lieu *</label><br>
        <select name="lieu" required>
            <option value="DOMICILE" <?= $match["lieu"] === "DOMICILE" ? "selected" : "" ?>>Domicile</option>
            <option value="EXTERIEUR" <?= $match["lieu"] === "EXTERIEUR" ? "selected" : "" ?>>Extérieur</option>
        </select>
        <br><br>

        <label>État du match *</label><br>
        <select name="etat">
            <option value="A_PREPARER" <?= $match["etat"] === "A_PREPARER" ? "selected" : "" ?>>À préparer</option>
            <option value="PREPARE"    <?= $match["etat"] === "PREPARE" ? "selected" : "" ?>>Préparé</option>
            <option value="JOUE"       <?= $match["etat"] === "JOUE" ? "selected" : "" ?>>Joué</option>
        </select>
        <br><br>

        <button 
            type="submit"
            style="
                padding:10px 18px; 
                background:#007bff; 
                color:white; 
                border:none; 
                border-radius:6px;
                cursor:pointer;
            "
        >💾 Enregistrer les modifications</button>

        <a href="liste_matchs.php" 
           style="margin-left:20px; text-decoration:none;">↩️ Retour</a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
