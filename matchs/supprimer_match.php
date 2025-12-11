<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";

include __DIR__ . "/../includes/header.php";

// Vérifier paramètre ID
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

// Si l'utilisateur confirme :
if (isset($_POST["confirm"]) && $_POST["confirm"] === "yes") {
    deleteMatch($gestion_sportive, $id_match);
    header("Location: liste_matchs.php");
    exit;
}
?>

<div class="container">

    <h1>🗑️ Supprimer le match</h1>

    <p>Voulez-vous vraiment supprimer ce match ?</p>

    <ul>
        <li><strong>Date :</strong> <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></li>
        <li><strong>Adversaire :</strong> <?= htmlspecialchars($match["adversaire"]) ?></li>
        <li><strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"]) ?></li>
        <li><strong>État :</strong> <?= htmlspecialchars($match["etat"]) ?></li>
    </ul>

    <p style="color:red; font-weight:bold;">
        ⚠️ Attention : Cette action est définitive.
    </p>

    <form method="POST">

        <button 
            type="submit"
            name="confirm"
            value="yes"
            style="
                padding:10px 20px; 
                background:red; 
                color:white;
                border:none;
                border-radius:6px;
                cursor:pointer;
            "
        >
            Oui, supprimer le match
        </button>

        <a href="liste_matchs.php"
           style="
                margin-left:20px; 
                text-decoration:none;
                padding:10px 20px; 
                background:#ccc; 
                border-radius:6px;
           "
        >
            Annuler
        </a>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
