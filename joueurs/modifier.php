<?php
session_start();
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/joueur.php';

if (!isset($_GET['id'])) {
    header("Location: liste.php");
    exit;
}

$id = (int) $_GET['id'];
$joueur = get_joueur($id);

if (!$joueur) {
    die("Joueur introuvable.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    update_joueur($id, $_POST);
    header("Location: liste.php");
    exit;
}

include "../header.php";
include "../menu.php";
?>

<h1>Modifier un joueur</h1>

<form method="post">
    Nom : <input type="text" name="nom" value="<?= htmlspecialchars($joueur['nom']) ?>" required><br>
    Prénom : <input type="text" name="prenom" value="<?= htmlspecialchars($joueur['prenom']) ?>" required><br>
    Licence : <input type="text" name="num_licence" value="<?= htmlspecialchars($joueur['num_licence']) ?>" required><br>
    Poids (kg) : <input type="number" step="0.1" name="poids_kg" value="<?= htmlspecialchars($joueur['poids_kg']) ?>"><br>
    Taille (cm) : <input type="number" name="taille_cm" value="<?= htmlspecialchars($joueur['taille_cm']) ?>"><br>
    Date de naissance : <input type="date" name="date_naissance" value="<?= htmlspecialchars($joueur['date_naissance']) ?>"><br>

    Statut :
    <select name="statut">
        <?php
        $statuts = ['Actif','Blessé','Suspendu','Absent'];
        foreach ($statuts as $s) {
            $sel = ($joueur['statut'] === $s) ? "selected" : "";
            echo "<option value='$s' $sel>$s</option>";
        }
        ?>
    </select><br>

    Commentaire :<br>
    <textarea name="commentaire"><?= htmlspecialchars($joueur['commentaire']) ?></textarea><br>

    <button type="submit" class="btn">Enregistrer</button>
</form>

<?php include "../footer.php"; ?>
