<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =============================
   Vérification ID match
============================= */
if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}

$id_match = intval($_GET["id_match"]);

/* =============================
   Infos match
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT *
    FROM matchs
    WHERE id_match = ?
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match introuvable.");
}

/* =============================
   Récup titulaires
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT 
        j.nom,
        j.prenom,
        p.evaluation,
        po.code AS poste,
        po.libelle AS poste_libelle
    FROM participation p
    JOIN joueur j ON j.id_joueur = p.id_joueur
    JOIN poste po ON po.id_poste = p.id_poste
    WHERE p.id_match = ?
      AND p.role = 'TITULAIRE'
    ORDER BY po.code
");
$stmt->execute([$id_match]);
$titulaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   Classement par poste
============================= */
$GAR = $DEF = $MIL = $ATT = [];

foreach ($titulaires as $j) {
    switch ($j["poste"]) {
        case "GAR": $GAR[] = $j; break;
        case "DEF": $DEF[] = $j; break;
        case "MIL": $MIL[] = $j; break;
        case "ATT": $ATT[] = $j; break;
    }
}

include "../includes/header.php";
?>

<style>
/* =============================
   GLOBAL
============================= */
.page {
    max-width: 1200px;
    margin: 40px auto;
    padding: 0 20px;
}

.card {
    background: linear-gradient(180deg, #1f3d2b, #12281c);
    border-radius: 18px;
    padding: 24px;
    margin-bottom: 30px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.35);
}

h1, h2 {
    margin: 0 0 15px;
}

/* =============================
   TERRAIN
============================= */
.pitch {
    background: linear-gradient(180deg, #1f7a3f, #145c2f);
    border-radius: 20px;
    padding: 35px 20px;
    border: 4px solid rgba(255,255,255,0.15);
}

/* Ligne de joueurs */
.line {
    display: flex;
    justify-content: center;
    gap: 18px;
    margin-bottom: 35px;
    flex-wrap: wrap;
}

/* Carte joueur */
.player {
    width: 170px;
    background: rgba(0,0,0,0.35);
    border-radius: 14px;
    padding: 14px;
    text-align: center;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,0.08);
}

.player strong {
    display: block;
    font-size: 15px;
}

.player small {
    opacity: 0.8;
}

.stars {
    margin-top: 6px;
    color: gold;
    font-size: 14px;
}

/* =============================
   BADGES MATCH
============================= */
.badges {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.badge {
    background: rgba(255,255,255,0.15);
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 14px;
}

/* =============================
   RETOUR
============================= */
.back {
    margin-top: 20px;
}
.back a {
    text-decoration: none;
    padding: 12px 20px;
    background: #34495e;
    border-radius: 10px;
    color: #fff;
}
</style>

<div class="page">

    <!-- EN-TÊTE MATCH -->
    <div class="card">
        <h1>👁️ Feuille de match</h1>
        <div class="badges">
            <div class="badge">⚔️ <?= htmlspecialchars($match["adversaire"]) ?></div>
            <div class="badge">📅 <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?></div>
            <div class="badge">📍 <?= $match["lieu"] ?></div>
            <div class="badge">🏁 <?= $match["resultat"] ?></div>
        </div>
    </div>

    <!-- TERRAIN -->
    <div class="card">
        <h2>🧩 Composition titulaire</h2>

        <div class="pitch">

            <?php if ($ATT): ?>
                <div class="line">
                    <?php foreach ($ATT as $j): ?>
                        <div class="player">
                            <strong><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></strong>
                            <small>Attaquant</small>
                            <div class="stars">
                                <?= str_repeat("★", $j["evaluation"]) ?>
                                <?= str_repeat("☆", 5 - $j["evaluation"]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($MIL): ?>
                <div class="line">
                    <?php foreach ($MIL as $j): ?>
                        <div class="player">
                            <strong><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></strong>
                            <small>Milieu</small>
                            <div class="stars">
                                <?= str_repeat("★", $j["evaluation"]) ?>
                                <?= str_repeat("☆", 5 - $j["evaluation"]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($DEF): ?>
                <div class="line">
                    <?php foreach ($DEF as $j): ?>
                        <div class="player">
                            <strong><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></strong>
                            <small>Défenseur</small>
                            <div class="stars">
                                <?= str_repeat("★", $j["evaluation"]) ?>
                                <?= str_repeat("☆", 5 - $j["evaluation"]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($GAR): ?>
                <div class="line">
                    <?php foreach ($GAR as $j): ?>
                        <div class="player">
                            <strong><?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?></strong>
                            <small>Gardien</small>
                            <div class="stars">
                                <?= str_repeat("★", $j["evaluation"]) ?>
                                <?= str_repeat("☆", 5 - $j["evaluation"]) ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <div class="back">
        <a href="../matchs/liste_matchs.php">← Retour aux matchs</a>
    </div>

</div>

<?php include "../includes/footer.php"; ?>
