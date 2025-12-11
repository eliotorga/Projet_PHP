<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

include __DIR__ . "/../includes/header.php";

// Vérification ID
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID joueur manquant.</p>");
}

$id_joueur = intval($_GET["id"]);
$joueur = getPlayerById($gestion_sportive, $id_joueur);

if (!$joueur) {
    die("<p style='color:red; font-weight:bold;'>Joueur introuvable.</p>");
}

// Si l’utilisateur confirme la suppression :
if (isset($_POST["confirm"]) && $_POST["confirm"] === "yes") {
    deletePlayer($gestion_sportive, $id_joueur);
    header("Location: liste_joueurs.php");
    exit;
}
?>

<div class="container">

    <h1>🗑️ Supprimer un joueur</h1>

    <p style="font-size:17px;">
        Voulez-vous vraiment supprimer le joueur suivant ?
    </p>

    <ul>
        <li><strong>Nom :</strong> <?= htmlspecialchars($joueur["nom"]) ?></li>
        <li><strong>Prénom :</strong> <?= htmlspecialchars($joueur["prenom"]) ?></li>
        <li><strong>Licence :</strong> <?= htmlspecialchars($joueur["num_licence"]) ?></li>
    </ul>

    <p style="color:red; font-weight:bold;">
        ⚠️ Cette action est irréversible.
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
        >Oui, supprimer</button>

        <a href="liste_joueurs.php"
            style="
                margin-left:20px; 
                text-decoration:none;
                padding:10px 20px; 
                background:#ccc; 
                border-radius:6px;
            "
        >Annuler</a>
    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
