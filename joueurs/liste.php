<?php
session_start();
require_once __DIR__ . '/../lib/auth.php';
require_login();

require_once __DIR__ . '/../lib/joueur.php';

// INCLUSIONS HTML
include "../header.php";
include "../menu.php";

$joueurs = get_all_joueurs();
?>

<h1>Liste des joueurs</h1>
<a class="btn" href="ajouter.php">Ajouter un joueur</a>

<table>
    <tr>
        <th>ID</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>

    <?php foreach ($joueurs as $j): ?>
    <tr>
        <td><?= htmlspecialchars($j['id_joueur']) ?></td>
        <td><?= htmlspecialchars($j['nom']) ?></td>
        <td><?= htmlspecialchars($j['prenom']) ?></td>
        <td><?= htmlspecialchars($j['statut']) ?></td>
        <td>
            <a href="modifier.php?id=<?= $j['id_joueur'] ?>">Modifier</a> |
            <a href="supprimer.php?id=<?= $j['id_joueur'] ?>" onclick="return confirm('Supprimer ?');">Supprimer</a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<?php include "../footer.php"; ?>
