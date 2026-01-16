<?php
// version avancee des statistiques joueurs avec filtres et tri
// affiche stats detaillees equipe et joueurs avec possibilite de filtrer et trier

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/stats.php";
include __DIR__ . "/../includes/header.php";


  // FONCTIONS UTILITAIRES

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


 // STATS ÉQUIPE

$totalMatchs = getPlayedMatchesCount($gestion_sportive);
$res = getResultsCountMap($gestion_sportive);

$victoires = $res["VICTOIRE"] ?? 0;
$defaites  = $res["DEFAITE"] ?? 0;
$nuls      = $res["NUL"] ?? 0;

// Matchs joues (ordre decroissant) pour les selections consecutives
$matchsJoues = getPlayedMatchIds($gestion_sportive);


  // LISTE DES JOUEURS & STATS

$joueurs = getPlayersBasicWithStatus($gestion_sportive);


 //  CALCUL DES STATS (PHP)

$statsData = [];

foreach ($joueurs as $j) {
    $id = $j["id_joueur"];
    $nomComplet = $j["prenom"] . " " . $j["nom"];
    $statut = $j["statut"];

    /* Titularisations / remplacements */
    $roles = getPlayerRoleCounts($gestion_sportive, $id);
    $titu = (int)($roles["titu"] ?? 0);
    $remp = (int)($roles["remp"] ?? 0);

    /* Moyenne des évaluations */
    $moy = getPlayerAvgEvaluation($gestion_sportive, $id);
    $moyVal = $moy ? (float)$moy : 0;
    $moyDisp = $moy ?? "—";

    /* Poste préféré (meilleure moyenne) */
    $poste = getPlayerBestPosteByEvaluation($gestion_sportive, $id) ?: "—";

    /* % matchs gagnés joués */
    $w = getPlayerWinRateData($gestion_sportive, $id);
    $pctWinVal = $w["total"] > 0 ? pct($w["wins"], $w["total"]) : 0;
    $pctWinDisp = $w["total"] > 0 ? $pctWinVal." %" : "—";

    /* Sélections consécutives */
    $consecutifs = 0;
    foreach ($matchsJoues as $mid) {
        if (getPlayerParticipationCountForMatch($gestion_sportive, (int)$mid, $id) > 0) {
            $consecutifs++;
        }
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
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">

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
