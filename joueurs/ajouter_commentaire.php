<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';

$id = $_GET['id'];

if ($_POST) {
    ajouterCommentaire($id, $_POST['texte']);
    header("Location: details_joueur.php?id=" . $id);
    exit;
}

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Ajouter un commentaire</h2>

<form method="POST">
    <label>Commentaire</label>
    <textarea name="texte" required></textarea>
    <button class="btn">Ajouter</button>
</form>

<?php include "../includes/footer.php"; ?>
