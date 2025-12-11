<?php
require_once __DIR__ . '/../includes/auth.php';
require_auth();
require_once __DIR__ . '/../bdd/joueurs.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/menu.php';

$joueurs = getTousLesJoueurs();
?>

<h2>Liste des joueurs</h2>

<a href="ajouter_joueur.php" class="btn">➕ Ajouter un joueur</a>

<table class="table">
    <tr>
        <th>Nom</th><th>Prénom</th><th>Licence</th><th>Statut</th><th>Actions</th>
    </tr>

    <?php foreach ($joueurs as $j): ?>
        <tr>
            <td><?= htmlspecialchars($j['nom']) ?></td>
            <td><?= htmlspecialchars($j['prenom']) ?></td>
            <td><?= htmlspecialchars($j['num_licence']) ?></td>
            <td><?= htmlspecialchars($j['statut']) ?></td>
            <td>
                <a href="details_joueur.php?id=<?= $j['id_joueur'] ?>" class="btn-mini">👁</a>
                <a href="modifier_joueur.php?id=<?= $j['id_joueur'] ?>" class="btn-mini">✏️</a>
                <a href="supprimer_joueur.php?id=<?= $j['id_joueur'] ?>" class="btn-mini delete">🗑</a>
            </td>
        </tr>
    <?php endforeach; ?>
</table>

<?php include "../includes/footer.php"; ?>
