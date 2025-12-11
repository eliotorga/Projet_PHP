<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';

$id = $_GET['id'];
$joueur = getJoueurById($id);
$coms = getCommentaires($id);

include "../includes/header.php";
include "../includes/menu.php";
?>

<h2>Détails du joueur</h2>

<p><strong>Nom :</strong> <?= htmlspecialchars($joueur['nom']) ?></p>
<p><strong>Prénom :</strong> <?= htmlspecialchars($joueur['prenom']) ?></p>
<p><strong>Licence :</strong> <?= htmlspecialchars($joueur['num_licence']) ?></p>
<p><strong>Statut :</strong> <?= htmlspecialchars($joueur['statut']) ?></p>

<br>

<h3>Historique des commentaires</h3>

<a href="ajouter_commentaire.php?id=<?= $id ?>" class="btn">➕ Ajouter un commentaire</a>

<?php foreach ($coms as $c): ?>
    <div class="comment-box">
        <p><strong><?= $c['date_commentaire'] ?></strong></p>
        <p><?= nl2br(htmlspecialchars($c['texte'])) ?></p>
    </div>
<?php endforeach; ?>

<?php include "../includes/footer.php"; ?>
