<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

require_once "../bdd/db_match.php";
require_once "../bdd/db_joueur.php";
require_once "../bdd/db_poste.php";
require_once "../bdd/db_participation.php";
require_once "../bdd/db_commentaire.php"; // <-- IMPORTANT pour récupérer commentaires

// Vérification id_match
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php");
    exit;
}

$id_match = intval($_GET["id_match"]);
$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("❌ Match introuvable.");
}

// Récup joueurs actifs
$joueurs = getAllPlayers($gestion_sportive);
$joueurs = array_filter($joueurs, fn($j) => $j["id_statut"] == 1);

// Récup postes
$postes = getAllPostes($gestion_sportive);

// Récup composition existante
$compo = getParticipationByMatch($gestion_sportive, $id_match);

include "../includes/header.php";
?>

<h2>📝 Composition du match — vs <?= htmlspecialchars($match["adversaire"]) ?></h2>

<style>
.player-info-box {
    padding: 10px;
    border: 1px solid #ccc;
    background: #fafafa;
    margin: 10px 0;
    display: none;
    border-radius: 8px;
}
.player-info-title {
    font-weight: bold;
    margin-bottom: 5px;
}
.player-info-comment {
    font-style: italic;
    color: #666;
}
</style>


<form method="POST" action="sauvegarde_compo.php">
    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <h3>🎯 Titulaires (un par poste)</h3>

    <?php foreach ($postes as $poste): 
        $selected = null;

        foreach ($compo as $c) {
            if ($c["id_poste"] == $poste["id_poste"] && $c["role"] === "TITULAIRE") {
                $selected = $c["id_joueur"];
            }
        }
    ?>

        <label><strong><?= htmlspecialchars($poste["libelle"]) ?></strong></label>

        <select name="titulaire[<?= $poste["id_poste"] ?>]" class="player-select"
            data-poste="<?= $poste["libelle"] ?>">
            <option value="">— Aucun joueur —</option>

            <?php foreach ($joueurs as $j): ?>

                <?php
                // Préparation des données joueur pour JS
                $comments = getCommentairesForJoueur($gestion_sportive, $j["id_joueur"]);
                $notes    = getEvaluationsForJoueur($gestion_sportive, $j["id_joueur"]);
                $avgNote  = $notes ? round(array_sum($notes)/count($notes), 2) : "Aucune";
                ?>

                <option value="<?= $j["id_joueur"] ?>"
                    data-joueur='<?= json_encode([
                        "nom" => $j["nom"],
                        "prenom" => $j["prenom"],
                        "taille" => $j["taille_cm"],
                        "poids" => $j["poids_kg"],
                        "commentaires" => $comments,
                        "moyenne" => $avgNote
                    ]) ?>'
                    <?= ($selected == $j["id_joueur"]) ? "selected" : "" ?>
                >
                    <?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?>
                </option>

            <?php endforeach; ?>
        </select>

        <!-- Bloc infos joueur -->
        <div class="player-info-box"></div>

        <br>

    <?php endforeach; ?>


    <h3>🔄 Remplaçants</h3>

    <div id="remplacants-zone">
        <button type="button" id="add-remp">➕ Ajouter un remplaçant</button>

        <?php foreach ($compo as $c): if ($c["role"] === "REMPLACANT"): ?>
            <div class="remp-row">

                <select name="remplacant[]" class="player-select">
                    <option value="">— Choisir un joueur —</option>

                    <?php foreach ($joueurs as $j): ?>
                        <option value="<?= $j["id_joueur"] ?>"
                            data-joueur='<?= json_encode([
                                "nom" => $j["nom"],
                                "prenom" => $j["prenom"],
                                "taille" => $j["taille_cm"],
                                "poids" => $j["poids_kg"],
                                "commentaires" => getCommentairesForJoueur($gestion_sportive, $j["id_joueur"]),
                                "moyenne" => $avgNote
                            ]) ?>'
                            <?= ($j["id_joueur"] == $c["id_joueur"]) ? "selected" : "" ?>
                        >
                            <?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="button" class="remove-remp">❌</button>

                <div class="player-info-box"></div>
            </div>
        <?php endif; endforeach; ?>
    </div>

    <br>
    <button type="submit" class="btn-primary">💾 Enregistrer la composition</button>
</form>


<script>
// AFFICHAGE DES INFOS JOUEURS
document.querySelectorAll(".player-select").forEach(select => {

    select.addEventListener("change", function () {

        const infoBox = this.parentNode.querySelector(".player-info-box");
        const raw = this.options[this.selectedIndex].dataset.joueur;

        if (!raw) {
            infoBox.style.display = "none";
            return;
        }

        const data = JSON.parse(raw);

        let html = `
            <div class="player-info-title">${data.prenom} ${data.nom}</div>
            Taille : ${data.taille} cm<br>
            Poids : ${data.poids} kg<br>
            Moyenne des évaluations : <b>${data.moyenne}</b><br><br>
            <div><u>Commentaires récents :</u></div>
        `;

        if (data.commentaires.length === 0) {
            html += "<span class='player-info-comment'>Aucun commentaire.</span>";
        } else {
            data.commentaires.slice(0,3).forEach(c => {
                html += `<div class='player-info-comment'>• ${c}</div>`;
            });
        }

        infoBox.innerHTML = html;
        infoBox.style.display = "block";
    });

});

// AJOUT DYNAMIQUE DE REMPLAÇANTS
document.getElementById("add-remp").addEventListener("click", () => {

    const wrapper = document.createElement("div");
    wrapper.classList.add("remp-row");

    wrapper.innerHTML = `
        <select name="remplacant[]" class="player-select">
            <option value="">— Choisir un joueur —</option>
            <?php foreach ($joueurs as $j): ?>
            <option value="<?= $j["id_joueur"] ?>"
                data-joueur='<?= json_encode([
                    "nom" => $j["nom"],
                    "prenom" => $j["prenom"],
                    "taille" => $j["taille_cm"],
                    "poids" => $j["poids_kg"],
                    "commentaires" => getCommentairesForJoueur($gestion_sportive, $j["id_joueur"]),
                    "moyenne" => $avgNote
                ]) ?>'>
                <?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?>
            </option>
            <?php endforeach; ?>
        </select>

        <button type="button" class="remove-remp">❌</button>
        <div class="player-info-box"></div>
    `;

    document.getElementById("remplacants-zone").appendChild(wrapper);

    wrapper.querySelector(".remove-remp").addEventListener("click", () => wrapper.remove());
});
</script>

<?php include "../includes/footer.php"; ?>
