<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

include __DIR__ . "/../includes/header.php";

// Récupération de tous les joueurs
$joueurs = getAllPlayers($gestion_sportive);

// Fonction pour couleur des statuts
function colorStatut($statut) {
    return match ($statut) {
        "Actif"      => "green",
        "Blessé"     => "orange",
        "Suspendu"   => "red",
        "Absent"     => "gray",
        default      => "black"
    };
}
?>

<div class="container">

    <h1>👥 Liste des joueurs</h1>

    <p>
        <a href="ajouter_joueur.php" 
        style="
            padding: 8px 14px; 
            background: #007bff; 
            color:white; 
            border-radius: 6px; 
            text-decoration: none;
        ">➕ Ajouter un joueur</a>
    </p>

    <table border="1" cellpadding="8" cellspacing="0" style="width:100%; border-collapse:collapse;">
        <tr style="background:#ddd;">
            <th>ID</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Licence</th>
            <th>Taille (cm)</th>
            <th>Poids (kg)</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>

        <?php foreach ($joueurs as $j): ?>
            <tr>
                <td><?= $j["id_joueur"] ?></td>
                <td><?= htmlspecialchars($j["nom"]) ?></td>
                <td><?= htmlspecialchars($j["prenom"]) ?></td>
                <td><?= htmlspecialchars($j["num_licence"]) ?></td>
                <td><?= $j["taille_cm"] ?></td>
                <td><?= $j["poids_kg"] ?></td>

                <td style="color: <?= colorStatut($j["statut_libelle"]); ?>; font-weight:bold;">
                    <?= htmlspecialchars($j["statut_libelle"]) ?>
                </td>

                <td>
                    <a href="modifier_joueur.php?id=<?= $j["id_joueur"] ?>">✏️ Modifier</a> |
                    <a href="supprimer_joueur.php?id=<?= $j["id_joueur"] ?>" 
                       onclick="return confirm('Supprimer ce joueur ?');"
                       style="color:red;">🗑️ Supprimer</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (count($joueurs) === 0): ?>
            <tr>
                <td colspan="8" style="text-align:center; padding:15px;">Aucun joueur trouvé.</td>
            </tr>
        <?php endif; ?>

    </table>

</div>

<?php include __DIR__ . "/../includes/footer.php"; ?>
