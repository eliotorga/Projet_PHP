<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/match.php';

include "../header.php";
include "../menu.php";
require_once "../lib/joueur.php";

$matchs = get_all_matchs();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Matchs</title></head>
<body>

<h1>Liste des matchs</h1>
<a href="ajouter.php">Ajouter un match</a>

<table border="1">
    <tr>
        <th>Date</th>
        <th>Équipe adverse</th>
        <th>Lieu</th>
        <th>Résultat</th>
        <th>Actions</th>
    </tr>
    <?php foreach ($matchs as $m): ?>
    <tr>
        <td><?= htmlspecialchars($m['date_heure']) ?></td>
        <td><?= htmlspecialchars($m['equipe_adverse']) ?></td>
        <td><?= htmlspecialchars($m['lieu']) ?></td>
        <td><?= htmlspecialchars($m['resultat'] ?? '-') ?></td>
        <td>
            <a href="modifier.php?id=<?= $m['id_match'] ?>">Modifier</a> |
            <a href="supprimer.php?id=<?= $m['id_match'] ?>" onclick="return confirm('Supprimer ce match ?');">
                Supprimer
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
