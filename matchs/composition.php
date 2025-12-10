<?php 
require_once "../includes/header.php"; 
require_once "../BDD/config.php"; 

// ============================================================
// 1. Vérification du match
// ============================================================

if (!isset($_GET["id"])) {
    die("Erreur : aucun match spécifié.");
}

$id_match = (int) $_GET["id"];

// Vérifier si le match existe
$stmt = $pdo->prepare("SELECT * FROM match_sportif WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();

if (!$match) {
    die("Erreur : match introuvable.");
}

// ============================================================
// 2. Récupération des joueurs actifs
// ============================================================

$joueurs = $pdo->query("SELECT * FROM joueur WHERE statut='actif' ORDER BY nom ASC")
               ->fetchAll();

// Liste des postes possibles
$postes = ["Gardien", "Défense", "Milieu", "Attaque"];

// ============================================================
// 3. Récupération de la composition existante (si modification)
// ============================================================

$stmt = $pdo->prepare("SELECT * FROM participer WHERE id_match = ?");
$stmt->execute([$id_match]);
$compositionExistante = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_UNIQUE);

// ============================================================
// 4. Traitement du formulaire
// ============================================================

if ($_POST) {

    // Vérification du nombre minimum
    $titulaires = $_POST["titulaire"] ?? [];
    $remplacants = $_POST["remplacant"] ?? [];

    $nbJoueurs = count($titulaires) + count($remplacants);

    if ($nbJoueurs < 5) {  // exemple : minimum 5 joueurs
        $erreur = "Vous devez sélectionner au moins 5 joueurs.";
    } else {

        // On supprime l'ancienne compo
        $del = $pdo->prepare("DELETE FROM participer WHERE id_match = ?");
        $del->execute([$id_match]);

        // INSERT titulaires
        foreach ($titulaires as $poste => $id_joueur) {
            if ($id_joueur) {
                $stmt = $pdo->prepare("
                    INSERT INTO participer (id_match, id_joueur, titularisation, poste_terrain)
                    VALUES (?, ?, 1, ?)
                ");
                $stmt->execute([$id_match, $id_joueur, $poste]);
            }
        }

        // INSERT remplaçants
        if (!empty($remplacants)) {
            foreach ($remplacants as $id_joueur => $poste_remp) {
                if ($poste_remp) {
                    $stmt = $pdo->prepare("
                        INSERT INTO participer (id_match, id_joueur, titularisation, poste_terrain)
                        VALUES (?, ?, 0, ?)
                    ");
                    $stmt->execute([$id_match, $id_joueur, $poste_remp]);
                }
            }
        }

        // Redirection
        header("Location: liste_matchs.php");
        exit;
    }
}

?>

<style>
.container { max-width: 900px; margin: auto; }
.card {
    background: #f7f7f7; padding: 20px; margin: 20px 0;
    border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.15);
}
label { font-weight: bold; }
input, select { padding: 7px; margin: 5px 0; }
.player-info { font-size: 12px; color: #555; }
.btn {
    padding: 10px 15px; background: #007bff; color: white;
    border-radius: 8px; text-decoration: none; border: none; cursor: pointer;
}
.btn:hover { background: #0056d2; }
.erreur { color: red; font-weight: bold; margin-bottom: 10px; }
</style>

<div class="container">
    <h2>Feuille de match – <?= htmlspecialchars($match["equipe_adverse"]) ?></h2>
    <p>Date : <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></p>

    <?php if (isset($erreur)) echo "<p class='erreur'>$erreur</p>"; ?>

    <form method="POST">

        <!-- ============================================================
             T I T U L A I R E S
             ============================================================ -->
        <div class="card">
            <h3>Titulaires</h3>

            <?php foreach ($postes as $poste): ?>
                <?php 
                    $posteKey = strtolower($poste);
                    $id_joueur_existant = null;

                    // Si compo existante → préremplir
                    foreach ($compositionExistante as $id_j => $info) {
                        if ($info['titularisation'] == 1 && $info['poste_terrain'] == $poste) {
                            $id_joueur_existant = $id_j;
                        }
                    }
                ?>

                <label><?= $poste ?></label><br>
                <select name="titulaire[<?= $poste ?>]">
                    <option value="">-- Sélectionnez un joueur --</option>
                    <?php foreach ($joueurs as $j): ?>
                        <option value="<?= $j['id_joueur'] ?>"
                            <?= ($j['id_joueur'] == $id_joueur_existant) ? "selected" : "" ?>>

                            <?= htmlspecialchars($j['nom'] . " " . $j['prenom']) ?>
                            (<?= $j['taille_cm'] ?>cm – <?= $j['poids_kg'] ?>kg)

                        </option>
                    <?php endforeach; ?>
                </select>
                <br><br>
            <?php endforeach; ?>
        </div>


        <!-- ============================================================
             R E M P L A Ç A N T S
             ============================================================ -->
        <div class="card">
            <h3>Remplaçants</h3>

            <?php foreach ($joueurs as $j): ?>
                <?php 
                    $poste_remp_existant = null;

                    if (isset($compositionExistante[$j['id_joueur']])
                        && $compositionExistante[$j['id_joueur']]['titularisation'] == 0) {
                        $poste_remp_existant = $compositionExistante[$j['id_joueur']]['poste_terrain'];
                    }
                ?>

                <p>
                    <strong><?= htmlspecialchars($j['nom'] . " " . $j['prenom']) ?></strong><br>
                    <span class="player-info">
                        <?= $j['taille_cm'] ?>cm – <?= $j['poids_kg'] ?>kg – Poste fav : <?= $j['poste_favori'] ?>
                    </span><br>

                    <select name="remplacant[<?= $j['id_joueur'] ?>]">
                        <option value="">Ne participe pas</option>
                        <?php foreach ($postes as $p): ?>
                            <option value="<?= $p ?>"
                                <?= ($poste_remp_existant == $p) ? "selected" : "" ?>>
                                <?= $p ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </p>
            <?php endforeach; ?>
        </div>

        <button class="btn">💾 Enregistrer la composition</button>
    </form>
</div>

<?php require_once "../includes/footer.php"; ?>
