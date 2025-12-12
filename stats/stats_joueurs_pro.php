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
   LISTE DES JOUEURS
======================= */
$joueurs = $gestion_sportive->query("
    SELECT j.id_joueur, j.nom, j.prenom, s.libelle AS statut
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    ORDER BY j.nom, j.prenom
")->fetchAll(PDO::FETCH_ASSOC);

/* =======================
   MATCHS JOUÉS (ordre décroissant)
======================= */
$matchsJoues = $gestion_sportive->query("
    SELECT id_match
    FROM matchs
    WHERE etat='JOUE'
    ORDER BY date_heure DESC
")->fetchAll(PDO::FETCH_COLUMN);
?>

<style>
h2 { color:#1e293b; margin-top:25px; }

.stats-equipe {
    background:#f8fafc;
    border-left:6px solid #2563eb;
    padding:15px;
    margin-bottom:20px;
    border-radius:6px;
}
.stats-equipe li { margin:6px 0; font-size:16px; }

.filtres {
    display:flex;
    gap:15px;
    margin:15px 0;
}

table {
    width:100%;
    border-collapse:collapse;
    background:white;
}
thead { background:#0f172a; color:white; }
th, td {
    padding:10px;
    text-align:center;
}
th {
    cursor:pointer;
    user-select:none;
}
tbody tr:nth-child(even) { background:#f1f5f9; }
tbody tr:hover { background:#e0f2fe; }

.badge {
    padding:4px 8px;
    border-radius:6px;
    font-weight:bold;
    font-size:13px;
}
.actif { background:#dcfce7; color:#166534; }
.blesse { background:#fee2e2; color:#991b1b; }
.suspendu { background:#fef9c3; color:#854d0e; }
</style>

<h2>📊 Statistiques de l’équipe</h2>

<ul class="stats-equipe">
    <li>🏆 Victoires : <?= $victoires ?> (<?= pct($victoires, $totalMatchs) ?> %)</li>
    <li>❌ Défaites : <?= $defaites ?> (<?= pct($defaites, $totalMatchs) ?> %)</li>
    <li>🤝 Nuls : <?= $nuls ?> (<?= pct($nuls, $totalMatchs) ?> %)</li>
</ul>

<hr>

<h2>📈 Statistiques détaillées des joueurs</h2>

<div class="filtres">
    <select id="filtreStatut">
        <option value="">Tous les statuts</option>
        <option value="Actif">Actif</option>
        <option value="Blessé">Blessé</option>
        <option value="Suspendu">Suspendu</option>
    </select>

    <input type="text" id="recherche" placeholder="Rechercher un joueur…">
</div>

<table id="tableStats">
<thead>
<tr>
    <th>Joueur</th>
    <th>Statut</th>
    <th>Poste préféré</th>
    <th>Titularisations</th>
    <th>Remplacements</th>
    <th>Moy. notes</th>
    <th>% victoires</th>
    <th>Sélections consécutives</th>
</tr>
</thead>
<tbody>

<?php foreach ($joueurs as $j): ?>
<?php
$id = $j["id_joueur"];

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

/* Moyenne des évaluations */
$stmt = $gestion_sportive->prepare("
    SELECT ROUND(AVG(evaluation),2)
    FROM participation
    WHERE id_joueur=? AND evaluation IS NOT NULL
");
$stmt->execute([$id]);
$moy = $stmt->fetchColumn();

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
$pctWin = $w["total"] > 0 ? pct($w["wins"], $w["total"])." %" : "—";

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
?>

<tr>
    <td><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></td>
    <td data-statut="<?= htmlspecialchars($j["statut"]) ?>">
        <span class="badge <?= statutClass($j["statut"]) ?>">
            <?= htmlspecialchars($j["statut"]) ?>
        </span>
    </td>
    <td><?= htmlspecialchars($poste) ?></td>
    <td><?= $roles["titu"] ?? 0 ?></td>
    <td><?= $roles["remp"] ?? 0 ?></td>
    <td><?= $moy ?? "—" ?></td>
    <td><?= $pctWin ?></td>
    <td><?= $consecutifs ?></td>
</tr>

<?php endforeach; ?>

</tbody>
</table>

<script>
/* ===== TRI TABLE ===== */
document.querySelectorAll("th").forEach((th, i) => {
    let asc = true;
    th.addEventListener("click", () => {
        const tbody = document.querySelector("tbody");
        const rows = Array.from(tbody.querySelectorAll("tr"));
        rows.sort((a, b) => {
            let A = a.children[i].innerText.replace("%","").trim();
            let B = b.children[i].innerText.replace("%","").trim();
            let nA = parseFloat(A), nB = parseFloat(B);
            if (!isNaN(nA) && !isNaN(nB)) return asc ? nA-nB : nB-nA;
            return asc ? A.localeCompare(B) : B.localeCompare(A);
        });
        asc = !asc;
        rows.forEach(r => tbody.appendChild(r));
    });
});

/* ===== FILTRES ===== */
const filtreStatut = document.getElementById("filtreStatut");
const recherche = document.getElementById("recherche");

function appliquerFiltres() {
    document.querySelectorAll("tbody tr").forEach(tr => {
        const nom = tr.children[0].innerText.toLowerCase();
        const statut = tr.querySelector("[data-statut]").dataset.statut.toLowerCase();
        const okStatut = !filtreStatut.value || statut === filtreStatut.value.toLowerCase();
        const okNom = nom.includes(recherche.value.toLowerCase());
        tr.style.display = (okStatut && okNom) ? "" : "none";
    });
}
filtreStatut.addEventListener("change", appliquerFiltres);
recherche.addEventListener("keyup", appliquerFiltres);
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
