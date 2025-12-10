<?php 
require_once "../includes/header.php"; 
require_once "../BDD/config.php"; 

// =======================================================
// Filtres / Recherche
// =======================================================

$search = $_GET["search"] ?? "";
$resultat = $_GET["resultat"] ?? "";
$ordre = $_GET["ordre"] ?? "date_desc";

$sql = "SELECT * FROM match_sportif WHERE 1=1";
$params = [];

if ($search !== "") {
    $sql .= " AND (equipe_adverse LIKE ? OR lieu LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($resultat !== "") {
    $sql .= " AND resultat = ?";
    $params[] = $resultat;
}

switch ($ordre) {
    case "date_asc":
        $sql .= " ORDER BY date_heure ASC";
        break;
    case "date_desc":
    default:
        $sql .= " ORDER BY date_heure DESC";
        break;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$matchs = $stmt->fetchAll();
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
.btn-add {
    display: inline-block;
    padding: 10px 15px;
    background: #28a745;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    margin-top: 15px;
}
.actions a {
    padding: 6px 10px;
    margin: 3px;
    border-radius: 6px;
    text-decoration: none;
    color: white;
    display: inline-block;
}

.btn-edit { background: #ffc107; color: black; }
.btn-edit:hover { background: #e0a800; }

.btn-delete { background: #dc3545; }
.btn-delete:hover { background: #c82333; }

.btn-compose { background: #007bff; }
.btn-compose:hover { background: #0062cc; }

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

<h2>Liste des matchs</h2>

<a class="btn-add" href="ajouter_match.php">➕ Ajouter un match</a>

<!-- =======================================================
     Filtres / Recherche
     ======================================================= -->
<div class="filter-box">
    <form method="GET">
        <input type="text" name="search" placeholder="🔍 Adversaire ou lieu"
               value="<?= htmlspecialchars($search) ?>">

        <select name="resultat">
            <option value="">Résultat (tous)</option>
            <option value="gagne" <?= $resultat=="gagne"?"selected":"" ?>>Gagné</option>
            <option value="perdu" <?= $resultat=="perdu"?"selected":"" ?>>Perdu</option>
            <option value="nul"   <?= $resultat=="nul"  ?"selected":"" ?>>Nul</option>
        </select>

        <select name="ordre">
            <option value="date_desc" <?= $ordre=="date_desc"?"selected":"" ?>>Date ↓</option>
            <option value="date_asc"  <?= $ordre=="date_asc" ?"selected":"" ?>>Date ↑</option>
        </select>

        <button>Filtrer</button>
    </form>
</div>

<!-- =======================================================
     Tableau des matchs
     ======================================================= -->
<table class="table">
    <tr>
        <th>Date</th>
        <th>Heure</th>
        <th>Adversaire</th>
        <th>Lieu</th>
        <th>Résultat</th>
        <th>Actions</th>
    </tr>

    <?php if (empty($matchs)) : ?>
        <tr><td colspan="6">Aucun match trouvé.</td></tr>
    <?php else: ?>
        <?php foreach ($matchs as $m) : ?>
            <tr>
                <?php 
                    $d = strtotime($m['date_heure']);
                ?>
                <td><?= date("d/m/Y", $d) ?></td>
                <td><?= date("H:i", $d) ?></td>

                <td><?= htmlspecialchars($m['equipe_adverse']) ?></td>
                <td><?= htmlspecialchars($m['lieu']) ?></td>

                <td>
                    <?php 
                        if ($m['resultat']=="gagne")   echo "<span style='color:green;font-weight:bold;'>Gagné</span>";
                        elseif ($m['resultat']=="perdu") echo "<span style='color:red;font-weight:bold;'>Perdu</span>";
                        elseif ($m['resultat']=="nul")   echo "<span style='color:blue;font-weight:bold;'>Nul</span>";
                        else echo "<i>Non joué</i>";
                    ?>
                </td>

                <td class="actions">
                    <a class="btn-compose" href="composition.php?id=<?= $m['id_match'] ?>">Feuille de match</a>

                    <a class="btn-edit" href="modifier_match.php?id=<?= $m['id_match'] ?>">Modifier</a>

                    <a class="btn-delete"
                       onclick="return confirm('Supprimer ce match ?')"
                       href="supprimer_match.php?id=<?= $m['id_match'] ?>">
                       Supprimer
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</table>

<?php require_once "../includes/footer.php"; ?>
