<?php 
require_once "../includes/header.php"; 
require_once "../BDD/config.php"; 

// =======================================================
// Gestion des filtres / recherche
// =======================================================

$search = $_GET["search"] ?? "";
$statut = $_GET["statut"] ?? "";
$poste = $_GET["poste"] ?? "";

// Construction dynamique de la requête SQL
$sql = "SELECT * FROM joueur WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (nom LIKE ? OR prenom LIKE ? OR num_licence LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($statut !== "") {
    $sql .= " AND statut = ?";
    $params[] = $statut;
}

if ($poste !== "") {
    $sql .= " AND poste_favori LIKE ?";
    $params[] = "%$poste%";
}

$sql .= " ORDER BY nom ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$joueurs = $stmt->fetchAll();
?>

<style>
.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}
.table th, .table td {
    padding: 12px;
    border: 1px solid #ddd;
    text-align: center;
}
.table th {
    background: #0073ff;
    color: white;
}
.actions a {
    margin: 0 5px;
    padding: 6px 10px;
    border-radius: 6px;
    text-decoration: none;
    transition: 0.2s;
}
.btn-add {
    display: inline-block;
    padding: 10px 15px;
    background: #28a745;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 15px;
}
.btn-edit { background: #ffc107; color: black; }
.btn-edit:hover { background: #e0a800; }

.btn-delete { background: #dc3545; color: white; }
.btn-delete:hover { background: #c82333; }

.btn-comment { background: #17a2b8; color: white; }
.btn-comment:hover { background: #117a8b; }

.filter-box {
    background: #f7f7f7;
    padding: 15px;
    margin-top: 15px;
    border-radius: 10px;
}
.filter-box input, .filter-box select {
    padding: 7px;
    margin-right: 10px;
}
</style>

<h2>Liste des joueurs</h2>

<a class="btn-add" href="ajouter_joueur.php">➕ Ajouter un joueur</a>

<!-- =======================================================
     Filtres / Recherche
     ======================================================= -->
<div class="filter-box">
    <form method="GET">
        <input type="text" name="search" placeholder="🔍 Nom, prénom, licence..."
               value="<?= htmlspecialchars($search) ?>">

        <select name="statut">
            <option value="">Statut (tous)</option>
            <option value="actif" <?= $statut == "actif" ? "selected" : "" ?>>Actif</option>
            <option value="inactif" <?= $statut == "inactif" ? "selected" : "" ?>>Inactif</option>
        </select>

        <input type="text" name="poste" placeholder="Poste favori"
               value="<?= htmlspecialchars($poste) ?>">

        <button>Filtrer</button>
    </form>
</div>

<!-- =======================================================
     Tableau des joueurs
     ======================================================= -->
<table class="table">
    <tr>
        <th>Nom</th>
        <th>Licence</th>
        <th>Poste favori</th>
        <th>Poids</th>
        <th>Taille</th>
        <th>Statut</th>
        <th>Actions</th>
    </tr>

    <?php if (empty($joueurs)) : ?>
        <tr><td colspan="7">Aucun joueur trouvé.</td></tr>
    <?php else: ?>
        <?php foreach ($joueurs as $j) : ?>
            <tr>
                <td><?= htmlspecialchars($j['nom']) . " " . htmlspecialchars($j['prenom']) ?></td>

                <td><?= htmlspecialchars($j['num_licence']) ?></td>

                <td><?= htmlspecialchars($j['poste_favori']) ?></td>

                <td><?= $j['poids_kg'] ? htmlspecialchars($j['poids_kg'])." kg" : "-" ?></td>

                <td><?= $j['taille_cm'] ? htmlspecialchars($j['taille_cm'])." cm" : "-" ?></td>

                <td>
                    <?= $j['statut'] == "actif" 
                        ? "<span style='color:green;font-weight:bold;'>Actif</span>"
                        : "<span style='color:red;font-weight:bold;'>Inactif</span>"
                    ?>
                </td>

                <td class="actions">
                    <a class="btn-comment" href="../commentaires/liste_commentaires.php?id=<?= $j['id_joueur'] ?>">Commentaires</a>

                    <a class="btn-edit" href="modification_joueur.php?id=<?= $j['id_joueur'] ?>">Modifier</a>

                    <a class="btn-delete" 
                       onclick="return confirm('Supprimer ce joueur ?')"
                       href="supprimer_joueur.php?id=<?= $j['id_joueur'] ?>">
                       Supprimer
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php require_once "../includes/footer.php"; ?>
