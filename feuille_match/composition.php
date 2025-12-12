<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php"; // IMPORTANT
require_once "../bdd/db_joueur.php";
require_once "../bdd/db_poste.php";

// 1) Récup match
if (!isset($_GET["id_match"])) die("Match non spécifié.");
$id_match = intval($_GET["id_match"]);

$stmt = $gestion_sportive->prepare("SELECT * FROM matchs WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();

if (!$match) die("Match introuvable.");

if ($match["date_heure"] < date("Y-m-d H:i:s")) {
    die("❌ Impossible : ce match est déjà passé.");
}

// 2) Récup joueurs actifs
$joueurs = getAllPlayers($gestion_sportive); // ta fonction existante récupère tout
$joueurs = array_filter($joueurs, fn($j) => $j["id_statut"] == 1);

// 3) Liste des postes disponibles
$postes = getAllPostes($gestion_sportive);

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Composition — Match vs <?= htmlspecialchars($match["equipe_adverse"]) ?></h2>

<form method="POST" action="sauvegarde_compo.php">

    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <h3>Titulaires</h3>

<?php foreach ($joueurs as $j): ?>
    <?php $extra = getPlayerExtraInfo($gestion_sportive, $j["id_joueur"]); ?>

    <div class="player-card">
        <label>
            <?= $j["nom"] . " " . $j["prenom"] ?>  
            (<?= $j["taille"] ?> cm / <?= $j["poids"] ?> kg)
        </label>

        <select name="titulaire[<?= $j["id_joueur"] ?>]" 
                class="player-select"
                data-player="<?= htmlspecialchars(json_encode([
                    "nom" => $j["nom"],
                    "prenom" => $j["prenom"],
                    "taille" => $j["taille"],
                    "poids" => $j["poids"],
                    "moyenne" => $extra["moyenne"],
                    "evaluations" => $extra["evaluations"],
                    "commentaires" => $extra["commentaires"]
                ])) ?>">
            <option value="">-- Choisir poste --</option>
            <?php foreach ($postes as $p): ?>
                <option value="<?= $p["id_poste"] ?>"><?= $p["libelle"] ?></option>
            <?php endforeach; ?>
        </select>

        <div class="player-info"></div>
    </div>
<?php endforeach; ?>


    <br><h3>Remplaçants</h3>

    <?php foreach ($joueurs as $j): ?>
        <div>
            <input type="checkbox" name="remplacants[]" value="<?= $j["id_joueur"] ?>">
            <?= $j["nom"] . " " . $j["prenom"] ?>

            <select name="poste_remplacant[<?= $j["id_joueur"] ?>]">
                <option value="">-- Poste --</option>
                <?php foreach ($postes as $p): ?>
                    <option value="<?= $p["id_poste"] ?>"><?= $p["libelle"] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endforeach; ?>


    <br>
    <button type="submit" class="btn">💾 Enregistrer la composition</button>
</form>
<script src="/Projet_PHP/assets/js/feuille_match.js"></script>

<?php include "../includes/footer.php"; ?>
