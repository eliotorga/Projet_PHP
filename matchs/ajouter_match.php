<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

$error = "";

// Formulaire envoyé ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = [
        "date_heure" => $_POST["date_heure"] ?? null,
        "adversaire" => trim($_POST["adversaire"] ?? ""),
        "lieu"       => $_POST["lieu"] ?? ""
    ];

    // Vérifications simples
    if ($data["date_heure"] === null || $data["adversaire"] === "" || $data["lieu"] === "") {
        $error = "Veuillez remplir tous les champs obligatoires.";
    } else {
        insertMatch($gestion_sportive, $data);
        header("Location: liste_matchs.php");
        exit;
    }
}
?>

<div class="container">

    <h1>➕ Ajouter un match</h1>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Date & Heure *</label><br>
        <input type="datetime-local" name="date_heure" required>
        <br><br>

        <label>Adversaire *</label><br>
        <input type="text" name="adversaire" required>
        <br><br>

        <label>Lieu *</label><br>
        <select name="lieu" required>
            <option value="">-- Choisir le lieu --</option>
            <option value="DOMICILE">Domicile</option>
            <option value="EXTERIEUR">Extérieur</option>
        </select>
        <br><br>

        <button 
            type="submit"
            style="
                padding:10px 18px; 
                background:#28a745; 
                color:white; 
                border:none;
                border-radius:6px;
                cursor:pointer;
            "
        >💾 Enregistrer</button>

        <a href="liste_matchs.php" 
           style="margin-left:20px; text-decoration:none;">↩️ Retour</a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
