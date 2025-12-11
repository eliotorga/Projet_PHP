<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

include __DIR__ . "/../includes/header.php";

// Récupération des statuts pour le <select>
$statuts = getAllStatuts($gestion_sportive);

$erreur = "";
$succes = "";

// Soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Nettoyage des données reçues
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => trim($_POST["num_licence"] ?? ""),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ?? null,
        "poids_kg"       => $_POST["poids_kg"] ?? null,
        "id_statut"      => $_POST["id_statut"] ?? null
    ];

    // Vérification basique
    if ($data["nom"] === "" || $data["prenom"] === "" || empty($data["id_statut"])) {
        $erreur = "Veuillez remplir au minimum Nom, Prénom et Statut.";
    } else {
        try {
            insertPlayer($gestion_sportive, $data);
            header("Location: liste_joueurs.php");
            exit;
        } catch (PDOException $e) {
            $erreur = "Erreur SQL : " . $e->getMessage();
        }
    }
}
?>

<div class="container">

    <h1>➕ Ajouter un joueur</h1>

    <?php if ($erreur): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($erreur) ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Nom *</label><br>
        <input type="text" name="nom" required><br><br>

        <label>Prénom *</label><br>
        <input type="text" name="prenom" required><br><br>

        <label>Numéro de licence</label><br>
        <input type="text" name="num_licence"><br><br>

        <label>Date de naissance</label><br>
        <input type="date" name="date_naissance"><br><br>

        <label>Taille (cm)</label><br>
        <input type="number" name="taille_cm" min="0"><br><br>

        <label>Poids (kg)</label><br>
        <input type="number" step="0.1" name="poids_kg" min="0"><br><br>

        <label>Statut *</label><br>
        <select name="id_statut" required>
            <option value="">-- Choisir un statut --</option>

            <?php foreach ($statuts as $s): ?>
                <option value="<?= $s["id_statut"] ?>">
                    <?= htmlspecialchars($s["libelle"]) ?>
                </option>
            <?php endforeach; ?>

        </select>
        <br><br>

        <button 
            type="submit"
            style="
                padding: 10px 18px; 
                background:#28a745; 
                color:white; 
                border:none; 
                border-radius:6px;
                cursor:pointer;
            "
        >💾 Enregistrer</button>

        <a href="liste_joueurs.php" 
            style="margin-left:20px; text-decoration:none;"
        >↩️ Retour</a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
