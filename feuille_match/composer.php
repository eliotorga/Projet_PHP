<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

require_once __DIR__ . "/../bdd/db_joueur.php";
require_once __DIR__ . "/../bdd/db_participation.php";
require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_poste.php";
require_once __DIR__ . "/../bdd/db_statut.php";

// --- Récupération des matchs à venir (A_PREPARER) ---
$matchsAvenir = getUpcomingMatches($gestion_sportive);

$successMessage = "";
$errorMessage = "";

// Si un match est sélectionné dans l’URL
$id_match = $_GET["id_match"] ?? null;

// Lorsqu’on soumet la feuille de match
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $id_match = intval($_POST["id_match"] ?? 0);
    $roles = $_POST["role"] ?? [];
    $postes = $_POST["poste"] ?? [];

    // Validation : 11 titulaires + max 7 remplaçants
    $nbTitulaires = 0;
    $nbRemplacants = 0;

    foreach ($roles as $id_joueur => $role) {
        if ($role === "TITULAIRE") $nbTitulaires++;
        if ($role === "REMPLACANT") $nbRemplacants++;
    }

    if ($nbTitulaires !== 11) {
        $errorMessage = "❌ Vous devez sélectionner exactement 11 titulaires. ($nbTitulaires sélectionnés)";
    }
    elseif ($nbRemplacants > 7) {
        $errorMessage = "❌ Maximum 7 remplaçants autorisés. ($nbRemplacants sélectionnés)";
    } 
    else {
        // Validation OK → on enregistre
        deleteParticipationForMatch($gestion_sportive, $id_match);

        foreach ($roles as $id_joueur => $role) {
            if ($role === "NONE") continue;

            $poste = $postes[$id_joueur] ?? null;
            insertParticipation($gestion_sportive, $id_match, $id_joueur, $poste, $role);
        }

        updateMatchEtat($gestion_sportive, $id_match, "PREPARE");

        $successMessage = "✔ Feuille de match sauvegardée avec succès !";
    }
}

// Si un match est choisi, charger les joueurs actifs
$joueursActifs = [];
$commentaires = [];
$evaluations = [];
$postes = getAllPostes($gestion_sportive);

if ($id_match) {
    $joueursActifs = getActivePlayers($gestion_sportive);
}

include __DIR__ . "/../includes/header.php";
?>


<h1>📝 Composer la feuille de match</h1>

<!-- Sélecteur de match -->
<form method="GET" action="">
    <label for="id_match">Match à préparer :</label>
    <select name="id_match" onchange="this.form.submit()" required>
        <option value="">-- Sélectionner un match --</option>
        <?php foreach ($matchsAvenir as $m): ?>
            <option value="<?= $m['id_match'] ?>" 
                <?= ($id_match == $m['id_match']) ? "selected" : "" ?>>
                <?= htmlspecialchars($m['adversaire']) ?> 
                (<?= $m['date_heure'] ?>)
            </option>
        <?php endforeach; ?>
    </select>
</form>

<hr><br>

<?php if ($id_match && empty($joueursActifs)): ?>
    <p>Aucun joueur actif disponible.</p>
<?php endif; ?>

<?php if ($errorMessage): ?>
    <p style="color: red; font-weight: bold;"><?= $errorMessage ?></p>
<?php endif; ?>

<?php if ($successMessage): ?>
    <p style="color: green; font-weight: bold;"><?= $successMessage ?></p>
<?php endif; ?>


<?php if ($id_match && $joueursActifs): ?>

<form method="POST" action="">
    <input type="hidden" name="id_match" value="<?= $id_match ?>">

    <!-- Compteurs -->
    <div style="margin-bottom: 15px; font-size: 18px;">
        <strong>Titulaires requis :</strong> 11  
        &nbsp;&nbsp;|&nbsp;&nbsp;
        <strong>Remplaçants max :</strong> 7
    </div>

    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">

        <?php foreach ($joueursActifs as $j): ?>

        <div style="
            border: 1px solid #ccc; 
            padding: 15px; 
            border-radius: 10px;
            background: #f9f9f9;
        ">
            <h3><?= htmlspecialchars($j["prenom"] . " " . $j["nom"]) ?></h3>

            <p>📏 <?= $j["taille_cm"] ?> cm — ⚖ <?= $j["poids_kg"] ?> kg</p>

            <p><strong>Derniers commentaires :</strong></p>
            <ul style="font-size: 14px;">
                <?php 
                    $coms = getCommentsByPlayer($gestion_sportive, $j["id_joueur"]);
                    if (empty($coms)) echo "<li>Aucun commentaire</li>";
                    else {
                        foreach ($coms as $c)
                            echo "<li>" . htmlspecialchars($c['texte']) . "</li>";
                    }
                ?>
            </ul>

            <p><strong>Moyenne évaluations :</strong>
                <?php 
                    $avg = getAverageEvaluation($gestion_sportive, $j["id_joueur"]);
                    echo $avg ? number_format($avg, 2) . "/5" : "Aucune note";
                ?>
            </p>

            <hr>

            <!-- Sélection rôle -->
            <label>Rôle :</label><br>
            <select name="role[<?= $j['id_joueur'] ?>]" required>
                <option value="NONE">-- Aucun --</option>
                <option value="TITULAIRE">Titulaire</option>
                <option value="REMPLACANT">Remplaçant</option>
            </select>

            <!-- Sélection poste -->
            <label>Poste :</label><br>
            <select name="poste[<?= $j['id_joueur'] ?>]" required>
                <?php foreach ($postes as $p): ?>
                    <option value="<?= $p['id_poste'] ?>">
                        <?= htmlspecialchars($p['libelle']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php endforeach; ?>

    </div>

    <br><br>
    <button type="submit" style="padding: 10px 25px; font-size: 18px;">
        💾 Enregistrer la feuille de match
    </button>

</form>

<?php endif; ?>


<?php include __DIR__ . "/../includes/footer.php"; ?>
