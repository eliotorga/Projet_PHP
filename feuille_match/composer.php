<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_joueur.php";
require_once __DIR__ . "/../bdd/db_participation.php";

include __DIR__ . "/../includes/header.php";

// Vérification de l'ID du match
if (!isset($_GET["id"])) {
    die("<p style='color:red; font-weight:bold;'>ID match manquant.</p>");
}

$id_match = intval($_GET["id"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<p style='color:red; font-weight:bold;'>Match introuvable.</p>");
}

$joueurs_actifs = getActivePlayers($gestion_sportive);
$postes = getAllPostes($gestion_sportive);

// Récupérer participation existante (si modification)
$participation_existante = getParticipationByMatch($gestion_sportive, $id_match);

$error = "";

// Soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $titulaires    = $_POST["titulaires"] ?? [];
    $postesChoisis = $_POST["poste"] ?? [];
    $remplacants   = $_POST["remplacants"] ?? [];

    // ⚠️ Contrôle : au moins un titulaire
    if (count($titulaires) === 0) {
        $error = "Vous devez sélectionner au moins un titulaire.";
    } else {

        // ⚠️ Contrôle : pas de doublons entre titulaires et remplaçants
        $doublons = array_intersect($titulaires, $remplacants);
        if (!empty($doublons)) {
            $error = "Un joueur ne peut pas être à la fois titulaire et remplaçant.";
        } else {

            // ⚠️ On vide d'abord l'ancienne composition
            clearParticipation($gestion_sportive, $id_match);

            // Enregistrement des titulaires
            foreach ($titulaires as $id_joueur) {
                $id_poste = $postesChoisis[$id_joueur] ?? null;
                addParticipation($gestion_sportive, $id_match, $id_joueur, "TITULAIRE", $id_poste);
            }

            // Enregistrement des remplaçants
            foreach ($remplacants as $id_joueur) {
                addParticipation($gestion_sportive, $id_match, $id_joueur, "REMPLACANT", null);
            }

            // Match passe en état PREPARE
            updateMatch($gestion_sportive, $id_match, [
                "date_heure" => $match["date_heure"],
                "adversaire" => $match["adversaire"],
                "lieu"       => $match["lieu"],
                "etat"       => "PREPARE"
            ]);

            header("Location: ../matchs/liste_matchs.php");
            exit;
        }
    }
}
?>

<div class="container">

    <h1>📝 Composer la feuille de match</h1>

    <p>
        <strong><?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></strong><br>
        Adversaire : <strong><?= htmlspecialchars($match["adversaire"]) ?></strong><br>
        Lieu : <?= htmlspecialchars($match["lieu"]) ?><br>
    </p>

    <?php if ($error): ?>
        <p style="color:red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">

        <h2>🏆 Titulaires (sélection + poste)</h2>

        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr style="background:#ddd;">
                <th>Choisir</th>
                <th>Joueur</th>
                <th>Poste</th>
            </tr>

            <?php foreach ($joueurs_actifs as $j): ?>
                <tr>
                    <td>
                        <input 
                            type="checkbox" 
                            name="titulaires[]" 
                            value="<?= $j["id_joueur"] ?>"
                            <?= isset($participation_existante[$j["id_joueur"]]) && $participation_existante[$j["id_joueur"]]["role"] === "TITULAIRE" ? "checked" : "" ?>
                        >
                    </td>

                    <td><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></td>

                    <td>
                        <select name="poste[<?= $j["id_joueur"] ?>]">
                            <option value="">-- poste --</option>
                            <?php foreach ($postes as $p): ?>
                                <option value="<?= $p["id_poste"] ?>">
                                    <?= htmlspecialchars($p["libelle"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>

        </table>

        <br>

        <h2>🔄 Remplaçants</h2>

        <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
            <tr style="background:#ddd;">
                <th>Choisir</th>
                <th>Joueur</th>
            </tr>

            <?php foreach ($joueurs_actifs as $j): ?>
                <tr>
                    <td>
                        <input 
                            type="checkbox" 
                            name="remplacants[]" 
                            value="<?= $j["id_joueur"] ?>"
                            <?= isset($participation_existante[$j["id_joueur"]]) && $participation_existante[$j["id_joueur"]]["role"] === "REMPLACANT" ? "checked" : "" ?>
                        >
                    </td>

                    <td><?= htmlspecialchars($j["prenom"]) . " " . htmlspecialchars($j["nom"]) ?></td>
                </tr>
            <?php endforeach; ?>

        </table>

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
        >
            💾 Enregistrer la feuille de match
        </button>

    </form>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
