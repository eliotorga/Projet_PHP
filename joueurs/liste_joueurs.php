<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* ==========================
   RÉCUP JOUEURS
========================== */
$stmt = $gestion_sportive->query("
    SELECT 
        j.id_joueur,
        j.nom,
        j.prenom,
        j.num_licence,
        s.libelle AS statut
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    ORDER BY j.nom, j.prenom
");

$joueurs = $stmt->fetchAll(PDO::FETCH_ASSOC);

include "../includes/header.php";
?>

<style>
/* =====================
   PAGE JOUEURS – DA
===================== */
.page-title {
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:25px;
}

.page-title h1 {
    font-size:2.2em;
}

.table-container {
    background:#fff;
    border-radius:18px;
    overflow:hidden;
    box-shadow:0 10px 30px rgba(0,0,0,0.08);
}

table {
    width:100%;
    border-collapse:collapse;
}

thead {
    background:#f4f6f8;
}

th, td {
    padding:16px;
    text-align:left;
}

th {
    font-size:0.85em;
    color:#555;
}

tbody tr {
    border-top:1px solid #eee;
}

tbody tr:hover {
    background:#f9fbfc;
}

/* =====================
   BADGES
===================== */
.badge {
    padding:6px 12px;
    border-radius:999px;
    font-size:0.75em;
    font-weight:bold;
}

.ACT { background:#e8f5e9; color:#2e7d32; }
.BLE { background:#fff3e0; color:#ef6c00; }
.SUS { background:#ffebee; color:#c62828; }
.ABS { background:#eceff1; color:#455a64; }

/* =====================
   ACTIONS
===================== */
.actions {
    display:flex;
    gap:10px;
}

.actions a {
    padding:8px 12px;
    border-radius:10px;
    font-size:0.85em;
    font-weight:bold;
    text-decoration:none;
    color:#fff;
}

.edit { background:#1976d2; }
.delete { background:#424242; }

/* =====================
   FOOTER INFO
===================== */
.info {
    margin-top:20px;
    padding:14px;
    background:#f4f6f8;
    border-radius:12px;
    font-style:italic;
}
</style>

<div class="page-title">
    <h1>👥 Gestion des joueurs</h1>
    <a href="ajouter_joueur.php" class="btn btn-green">➕ Ajouter un joueur</a>
</div>

<p>
Gérez l’effectif de l’équipe : informations personnelles, statut et actions de gestion.
</p>

<div class="table-container">
<table>
    <thead>
        <tr>
            <th>Joueur</th>
            <th>Licence</th>
            <th>Statut</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($joueurs as $j): ?>
        <tr>
            <td>
                <strong><?= htmlspecialchars($j["nom"]) ?></strong><br>
                <span style="opacity:.7"><?= htmlspecialchars($j["prenom"]) ?></span>
            </td>
            <td><?= htmlspecialchars($j["num_licence"]) ?></td>
            <td>
                <span class="badge <?= strtoupper(substr($j["statut"],0,3)) ?>">
                    <?= htmlspecialchars($j["statut"]) ?>
                </span>
            </td>
            <td>
                <div class="actions">
                    <a href="modifier_joueur.php?id_joueur=<?= $j["id_joueur"] ?>" class="edit">
                        ✏️ Modifier
                    </a>

                    <a href="supprimer_joueur.php?id_joueur=<?= $j["id_joueur"] ?>"
                       class="delete"
                       onclick="return confirm('⚠️ Supprimer définitivement ce joueur ?');">
                        🗑️ Supprimer
                    </a>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>

<div class="info">
💡 La suppression d’un joueur entraîne automatiquement la suppression de ses commentaires et participations.
</div>

<?php include "../includes/footer.php"; ?>
