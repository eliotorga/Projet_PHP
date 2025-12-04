<?php
// matchs/feuille.php
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/auth.php';
require_login();

$id_match = (int)($_GET['id'] ?? 0);
if ($id_match <= 0) {
    header('Location: ../index.php');
    exit;
}

// ---- Récupération du match ----
$stmt = $pdo->prepare("SELECT * FROM match WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch();
if (!$match) die("Match introuvable.");

// ---- Récup joueurs actifs ----
$stmt = $pdo->query("
    SELECT j.*, p.libelle_poste
    FROM joueur j
    LEFT JOIN poste p ON p.id_poste = j.id_poste_prefere
    WHERE j.actif = 1
    ORDER BY j.nom, j.prenom
");
$joueurs = $stmt->fetchAll();

// ---- Récup postes ----
$req = $pdo->query("SELECT * FROM poste ORDER BY libelle_poste");
$postes = $req->fetchAll();

// ---- Ajouter titulaire / remplaçant ----
$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'ajouter') {
        $id_joueur = (int)$_POST['id_joueur'];
        $id_poste  = (int)$_POST['id_poste'];
        $role      = $_POST['role'];

        if (!$id_joueur || !$id_poste) {
            $erreur = "Sélection invalide.";
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO selection (id_match, id_joueur, id_poste, role)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$id_match, $id_joueur, $id_poste, $role]);
                $success = "Joueur ajouté.";
            } catch (PDOException $e) {
                $erreur = "Ce joueur est déjà sélectionné.";
            }
        }
    }

    // ---- Suppression ----
    if ($_POST['action'] === 'supprimer') {
        $id_selection = (int)$_POST['id_selection'];
        $stmt = $pdo->prepare("DELETE FROM selection WHERE id_selection = ? AND id_match = ?");
        $stmt->execute([$id_selection, $id_match]);
        $success = "Joueur retiré.";
    }
}

// ---- Récup sélections actuelles ----
$stmt = $pdo->prepare("
    SELECT s.*, j.nom, j.prenom, j.taille_cm, j.poids_kg, p.libelle_poste
    FROM selection s
    JOIN joueur j ON j.id_joueur = s.id_joueur
    JOIN poste p ON p.id_poste = s.id_poste
    WHERE s.id_match = ?
    ORDER BY s.role DESC, j.nom
");
$stmt->execute([$id_match]);
$selections = $stmt->fetchAll();

// Minimum de joueurs
$min_joueurs = 5;

// ---- Récupérer commentaires + évaluations pour affichage ----
function getCommentaires($pdo, $id_joueur)
{
    $stmt = $pdo->prepare("SELECT * FROM commentaire_joueur WHERE id_joueur = ? ORDER BY date_commentaire DESC LIMIT 3");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll();
}

function getEvaluations($pdo, $id_joueur)
{
    $stmt = $pdo->prepare("
        SELECT e.note, m.date_match
        FROM evaluation e
        JOIN match m ON m.id_match = e.id_match
        WHERE e.id_joueur = ?
        ORDER BY m.date_match DESC
        LIMIT 3
    ");
    $stmt->execute([$id_joueur]);
    return $stmt->fetchAll();
}

include __DIR__ . '/../header.php';
?>

<h2>Feuille du match</h2>
<p><strong><?= htmlspecialchars($match['date_match']) ?> — <?= htmlspecialchars($match['adversaire']) ?></strong></p>

<?php if ($erreur): ?>
    <p style="color:red"><?= $erreur ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green"><?= $success ?></p>
<?php endif; ?>

<h3>Joueurs sélectionnés</h3>

<?php if (count($selections) < $min_joueurs): ?>
    <p style="color:orange">⚠ Minimum requis : <?= $min_joueurs ?> joueurs.</p>
<?php endif; ?>

<table border="1" cellpadding="5">
    <tr>
        <th>Rôle</th>
        <th>Joueur</th>
        <th>Poste</th>
        <th>Taille</th>
        <th>Poids</th>
        <th>Commentaires récents</th>
        <th>Évaluations récentes</th>
        <th>Action</th>
    </tr>

    <?php foreach ($selections as $s): ?>
        <tr>
            <td><?= htmlspecialchars($s['role']) ?></td>
            <td><?= htmlspecialchars($s['prenom'] . " " . $s['nom']) ?></td>
            <td><?= htmlspecialchars($s['libelle_poste']) ?></td>
            <td><?= $s['taille_cm'] ? $s['taille_cm'] . " cm" : "-" ?></td>
            <td><?= $s['poids_kg'] ? $s['poids_kg'] . " kg" : "-" ?></td>

            <td>
                <?php
                $coms = getCommentaires($pdo, $s['id_joueur']);
                if (!$coms) echo "Aucun";
                else foreach ($coms as $c) echo "<li>" . htmlspecialchars($c['texte']) . "</li>";
                ?>
            </td>

            <td>
                <?php
                $evals = getEvaluations($pdo, $s['id_joueur']);
                if (!$evals) echo "Aucune";
                else foreach ($evals as $e) echo "<li>" . htmlspecialchars($e['note']) . "/10 (" . $e['date_match'] . ")</li>";
                ?>
            </td>

            <td>
                <form method="post">
                    <input type="hidden" name="id_selection" value="<?= $s['id_selection'] ?>">
                    <input type="hidden" name="action" value="supprimer">
                    <button onclick="return confirm('Retirer ce joueur ?')">Retirer</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</table>


<hr>

<h3>Ajouter un joueur</h3>

<form method="post">
    <input type="hidden" name="action" value="ajouter">

    <label>Joueur :</label>
    <select name="id_joueur" required>
        <option value="">-- sélection --</option>
        <?php foreach ($joueurs as $j): ?>
            <option value="<?= $j['id_joueur'] ?>">
                <?= htmlspecialchars($j['prenom'] . " " . $j['nom']) ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Poste :</label>
    <select name="id_poste" required>
        <option value="">-- poste --</option>
        <?php foreach ($postes as $p): ?>
            <option value="<?= $p['id_poste'] ?>"><?= htmlspecialchars($p['libelle_poste']) ?></option>
        <?php endforeach; ?>
    </select><br><br>

    <label>Rôle :</label>
    <select name="role">
        <option value="titulaire">Titulaire</option>
        <option value="remplaçant">Remplaçant</option>
    </select><br><br>

    <button type="submit">Ajouter</button>
</form>

<?php include __DIR__ . '/../footer.php'; ?>
