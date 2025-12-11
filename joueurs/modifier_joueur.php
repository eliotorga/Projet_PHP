<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

include __DIR__ . "/../includes/header.php";

// Vérifier qu'un ID a été envoyé
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID joueur manquant.</p>");
}

$id_joueur = intval($_GET["id"]);

// Récupération du joueur à modifier
$joueur = getPlayerById($gestion_sportive, $id_joueur);

if (!$joueur) {
    die("<p style='color:red; font-weight:bold;'>Joueur introuvable.</p>");
}

// Récupérer la liste des statuts
$statuts = getAllStatuts($gestion_sportive);

$error = "";

// Formulaire soumis ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => trim($_POST["num_licence"] ?? ""),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ?? null,
        "poids_kg"       => $_POST["poids_kg"] ?? null,
        "id_statut"      => $_POST["id_statut"] ?? null
    ];

    if ($data["nom"] === "" || $data["prenom"] === "" || empty($data["id_statut"])) {
        $error = "Veuillez remplir au minimum Nom, Prénom et Statut.";
    } else {
        updatePlayer($gestion_sportive, $id_joueur, $data);
        header("Location: liste_joueurs.php");
        exit;
    }
}

?>

<div class="container">

    <h1>✏️ Modifier le joueur</h1>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Nom *</label><br>
        <input type="text" name="nom" value="<?= htmlspecialchars($joueur['nom']) ?>" required><br><br>

        <label>Prénom *</label><br>
        <input type="text" name="prenom" value="<?= htmlspecialchars($joueur['prenom']) ?>" required><br><br>

        <label>Numéro de licence</label><br>
        <input type="text" name="num_licence" value="<?= htmlspecialchars($joueur['num_licence']) ?>"><br><br>

        <label>Date de naissance</label><br>
        <input type="date" name="date_naissance" value="<?= htmlspecialchars($joueur['date_naissance']) ?>"><br><br>

        <label>Taille (cm)</label><br>
        <input type="number" name="taille_cm" min="0" value="<?= htmlspecialchars($joueur['taille_cm']) ?>"><br><br>

        <label>Poids (kg)</label><br>
        <input type="number" step="0.1" name="poids_kg" min="0" value="<?= htmlspecialchars($joueur['poids_kg']) ?>"><br><br>

        <label>Statut *</label><br>
        <select name="id_statut" required>

            <?php foreach ($statuts as $s): ?>
                <option value="<?= $s["id_statut"] ?>"
                    <?= ($s["id_statut"] == $joueur["id_statut"]) ? "selected" : "" ?>>
                    <?= htmlspecialchars($s["libelle"]) ?>
                </option>
            <?php endforeach; ?>

        </select>
        <br><br>

        <button
            type="submit"
            style="
                padding: 10px 18px; 
                background:#007bff; 
                color:white; 
                border:none; 
                border-radius:6px;
                cursor:pointer;
            "
        >💾 Enregistrer les modifications</button>

        <a href="liste_joueurs.php" 
           style="margin-left:20px; text-decoration:none;">↩️ Retour</a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
