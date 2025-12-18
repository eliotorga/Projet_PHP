<?php
session_start();
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
include __DIR__ . "/../includes/header.php";

/* =======================
   FONCTIONS UTILITAIRES
======================= */
function pct($v, $t) {
    return $t > 0 ? round(($v / $t) * 100, 1) : 0;
}

function statutClass($statut) {
    return match ($statut) {
        'Actif' => 'actif',
        'Blessé' => 'blesse',
        'Suspendu' => 'suspendu',
        default => ''
    };
}

/* =======================
   STATS ÉQUIPE
======================= */
$totalMatchs = $gestion_sportive->query("
    SELECT COUNT(*) FROM matchs WHERE etat='JOUE'
")->fetchColumn();

$res = $gestion_sportive->query("
    SELECT resultat, COUNT(*) nb
    FROM matchs
    WHERE etat='JOUE'
    GROUP BY resultat
")->fetchAll(PDO::FETCH_KEY_PAIR);

$victoires = $res["VICTOIRE"] ?? 0;
$defaites  = $res["DEFAITE"] ?? 0;
$nuls      = $res["NUL"] ?? 0;

/* =======================
   LISTE DES JOUEURS & STATS
======================= */
$sql = "
    SELECT j.id_joueur, j.nom, j.prenom, s.libelle AS statut
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
";

// Filtres SQL de base (pour le statut si possible, mais on peut tout faire en PHP si on veut filtrer sur des champs calculés)
$params = [];
$where = [];

// On récupère tous les joueurs pour le calcul des stats
$joueurs = $gestion_sportive->query($sql)->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   CALCUL DES STATS (PHP)
======================= */
$statsData = [];

foreach ($joueurs as $j) {
    $id = $j["id_joueur"];
    $nomComplet = $j["prenom"] . " " . $j["nom"];
    $statut = $j["statut"];

    /* Titularisations / remplacements */
    $stmt = $gestion_sportive->prepare("
        SELECT
            SUM(role='TITULAIRE') AS titu,
            SUM(role='REMPLACANT') AS remp
        FROM participation
        WHERE id_joueur=?
    ");
    $stmt->execute([$id]);
    $roles = $stmt->fetch(PDO::FETCH_ASSOC);
    $titu = (int)($roles["titu"] ?? 0);
    $remp = (int)($roles["remp"] ?? 0);

    /* Moyenne des évaluations */
    $stmt = $gestion_sportive->prepare("
        SELECT ROUND(AVG(evaluation),2)
        FROM participation
        WHERE id_joueur=? AND evaluation IS NOT NULL
    ");
    $stmt->execute([$id]);
    $moy = $stmt->fetchColumn();
    $moyVal = $moy ? (float)$moy : 0;
    $moyDisp = $moy ?? "—";

    /* Poste préféré (meilleure moyenne) */
    $stmt = $gestion_sportive->prepare("
        SELECT po.libelle
        FROM participation pa
        JOIN poste po ON po.id_poste = pa.id_poste
        WHERE pa.id_joueur=? AND pa.evaluation IS NOT NULL
        GROUP BY pa.id_poste
        ORDER BY AVG(pa.evaluation) DESC
        LIMIT 1
    ");
    $stmt->execute([$id]);
    $poste = $stmt->fetchColumn() ?: "—";

    /* % matchs gagnés joués */
    $stmt = $gestion_sportive->prepare("
        SELECT COUNT(*) total,
           SUM(m.resultat='VICTOIRE') wins
        FROM participation pa
        JOIN matchs m ON m.id_match = pa.id_match
        WHERE pa.id_joueur=? AND m.etat='JOUE'
    ");
    $stmt->execute([$id]);
    $w = $stmt->fetch(PDO::FETCH_ASSOC);
    $pctWinVal = $w["total"] > 0 ? pct($w["wins"], $w["total"]) : 0;
    $pctWinDisp = $w["total"] > 0 ? $pctWinVal." %" : "—";

    /* Sélections consécutives */
    $consecutifs = 0;
    foreach ($matchsJoues as $mid) {
        $stmt = $gestion_sportive->prepare("
            SELECT COUNT(*) FROM participation
            WHERE id_match=? AND id_joueur=?
        ");
        $stmt->execute([$mid, $id]);
        if ($stmt->fetchColumn() > 0) $consecutifs++;
        else break;
    }

    $statsData[] = [
        'id' => $id,
        'nom' => $nomComplet,
        'statut' => $statut,
        'poste' => $poste,
        'titu' => $titu,
        'remp' => $remp,
        'moy' => $moyVal,
        'moy_disp' => $moyDisp,
        'pct_win' => $pctWinVal,
        'pct_win_disp' => $pctWinDisp,
        'consecutifs' => $consecutifs
    ];
}

/* =======================
   FILTRAGE (PHP)
======================= */
$filtreStatut = $_GET['statut'] ?? '';
$recherche = trim($_GET['search'] ?? '');

if ($filtreStatut || $recherche) {
    $statsData = array_filter($statsData, function($row) use ($filtreStatut, $recherche) {
        // Filtre Statut
        if ($filtreStatut && $row['statut'] !== $filtreStatut) {
            return false;
        }
        // Filtre Recherche
        if ($recherche && stripos($row['nom'], $recherche) === false) {
            return false;
        }
        return true;
    });
}

/* =======================
   TRI (PHP)
======================= */
$sort = $_GET['sort'] ?? 'nom';
$order = $_GET['order'] ?? 'asc';
$nextOrder = $order === 'asc' ? 'desc' : 'asc';

usort($statsData, function($a, $b) use ($sort, $order) {
    $valA = $a[$sort] ?? $a['nom'];
    $valB = $b[$sort] ?? $b['nom'];

    if ($valA == $valB) return 0;
    
    // Tri numérique pour certaines colonnes
    if (is_numeric($valA) && is_numeric($valB)) {
        return ($order === 'asc') ? ($valA - $valB) : ($valB - $valA);
    }
    
    // Tri alphabétique
    return ($order === 'asc') ? strcasecmp($valA, $valB) : strcasecmp($valB, $valA);
});

// Helper pour les liens de tri
function sortLink($col, $label, $currentSort, $currentOrder) {
    $newOrder = ($currentSort === $col && $currentOrder === 'asc') ? 'desc' : 'asc';
    $icon = '';
    if ($currentSort === $col) {
        $icon = $currentOrder === 'asc' ? ' ▲' : ' ▼';
    }
    
    // Garder les filtres actuels dans l'URL
    $params = $_GET;
    $params['sort'] = $col;
    $params['order'] = $newOrder;
    $url = '?' . http_build_query($params);
    
    return "<a href='$url' style='color: white; text-decoration: none;'>$label$icon</a>";
}

?>

<link rel="stylesheet" href="../assets/css/stats_joueurs_pro.css">

<h2>📊 Statistiques de l’équipe</h2>

<ul class="stats-equipe">
    <li>🏆 Victoires : <?= $victoires ?> (<?= pct($victoires, $totalMatchs) ?> %)</li>
    <li>❌ Défaites : <?= $defaites ?> (<?= pct($defaites, $totalMatchs) ?> %)</li>
    <li>🤝 Nuls : <?= $nuls ?> (<?= pct($nuls, $totalMatchs) ?> %)</li>
</ul>

<hr>

<h2>📈 Statistiques détaillées des joueurs</h2>

<div class="filtres">
    <form method="GET" action="">
        <select name="statut">
            <option value="">Tous les statuts</option>
            <option value="Actif" <?= $filtreStatut === 'Actif' ? 'selected' : '' ?>>Actif</option>
            <option value="Blessé" <?= $filtreStatut === 'Blessé' ? 'selected' : '' ?>>Blessé</option>
            <option value="Suspendu" <?= $filtreStatut === 'Suspendu' ? 'selected' : '' ?>>Suspendu</option>
        </select>

        <input type="text" name="search" placeholder="Rechercher un joueur…" value="<?= htmlspecialchars($recherche) ?>">
        
        <button type="submit">Filtrer</button>
        <?php if($filtreStatut || $recherche): ?>
            <a href="?" style="margin-left: 10px; color: #64748b; text-decoration: none;">Réinitialiser</a>
        <?php endif; ?>
    </form>
</div>

<table id="tableStats">
<thead>
<tr>
    <th><?= sortLink('nom', 'Joueur', $sort, $order) ?></th>
    <th><?= sortLink('statut', 'Statut', $sort, $order) ?></th>
    <th>Poste préféré</th>
    <th><?= sortLink('titu', 'Titularisations', $sort, $order) ?></th>
    <th><?= sortLink('remp', 'Remplacements', $sort, $order) ?></th>
    <th><?= sortLink('moy', 'Moy. notes', $sort, $order) ?></th>
    <th><?= sortLink('pct_win', '% victoires', $sort, $order) ?></th>
    <th><?= sortLink('consecutifs', 'Sélections consécutives', $sort, $order) ?></th>
</tr>
</thead>
<tbody>

<?php if (empty($statsData)): ?>
    <tr>
        <td colspan="8">Aucun joueur trouvé.</td>
    </tr>
<?php else: ?>
    <?php foreach ($statsData as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['nom']) ?></td>
        <td>
            <span class="badge <?= statutClass($row['statut']) ?>">
                <?= htmlspecialchars($row['statut']) ?>
            </span>
        </td>
        <td><?= htmlspecialchars($row['poste']) ?></td>
        <td><?= $row['titu'] ?></td>
        <td><?= $row['remp'] ?></td>
        <td><?= $row['moy_disp'] ?></td>
        <td><?= $row['pct_win_disp'] ?></td>
        <td><?= $row['consecutifs'] ?></td>
    </tr>
    <?php endforeach; ?>
<?php endif; ?>

</tbody>
</table>

<?php include __DIR__ . "/../includes/footer.php"; ?>
