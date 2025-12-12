<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* ===== MATCH ===== */
if (!isset($_GET["id_match"])) die("Match non spécifié");
$id_match = (int)$_GET["id_match"];

$stmt = $gestion_sportive->prepare("
    SELECT date_heure, adversaire, lieu
    FROM matchs WHERE id_match = ?
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$match) die("Match introuvable");

/* ===== JOUEURS ACTIFS ===== */
$joueurs = $gestion_sportive->query("
    SELECT j.id_joueur, j.nom, j.prenom
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE s.code = 'ACT'
    ORDER BY j.nom, j.prenom
")->fetchAll(PDO::FETCH_ASSOC);

/* ===== POSTES BDD ===== */
$postes = $gestion_sportive->query("
    SELECT id_poste, code FROM poste
")->fetchAll(PDO::FETCH_KEY_PAIR);

/* ===== ENREGISTREMENT ===== */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (!isset($_POST["titulaire"]) || count($_POST["titulaire"]) !== 11) {
        die("Il faut exactement 11 joueurs.");
    }

    $gestion_sportive->prepare(
        "DELETE FROM participation WHERE id_match = ?"
    )->execute([$id_match]);

    $stmt = $gestion_sportive->prepare("
        INSERT INTO participation (id_match, id_joueur, id_poste, role)
        VALUES (?, ?, ?, 'TITULAIRE')
    ");

    foreach ($_POST["titulaire"] as $id_poste => $id_joueur) {
        $stmt->execute([$id_match, $id_joueur, $id_poste]);
    }

    $gestion_sportive
        ->prepare("UPDATE matchs SET etat='PREPARE' WHERE id_match=?")
        ->execute([$id_match]);

    header("Location: ../matchs/liste_matchs.php");
    exit;
}

include "../includes/header.php";
?>

<style>
/* ===== LAYOUT ===== */
.wrapper {
    display: flex;
    gap: 20px;
}

/* ===== BANC ===== */
.bench {
    width: 260px;
    background: #f4f6f8;
    padding: 12px;
    border-radius: 12px;
}

.player {
    background: #fff;
    padding: 8px;
    margin-bottom: 6px;
    border-radius: 6px;
    cursor: grab;
    text-align: center;
    border: 1px solid #ccc;
}

/* ===== TERRAIN ===== */
.field {
    position: relative;
    width: 520px;
    height: 780px;
    background: linear-gradient(#2e7d32, #1b5e20);
    border-radius: 16px;
}

/* ===== POSTES RECTANGULAIRES ===== */
.slot {
    position: absolute;
    width: 110px;
    height: 60px;
    background: #fff;
    border-radius: 6px;
    font-size: 11px;
    font-weight: bold;
    transform: translate(-50%, -50%);
    border: 2px dashed #999;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}

.slot.filled {
    border-style: solid;
}

/* ===== BOUTON RETIRER ===== */
.remove-btn {
    margin-top: 4px;
    font-size: 10px;
    background: #c62828;
    color: #fff;
    border: none;
    border-radius: 4px;
    padding: 2px 6px;
    cursor: pointer;
}

/* ===== POSITIONS ===== */
.GAR { top: 92%; left: 50%; }

.DG  { top: 72%; left: 15%; }
.DCG { top: 72%; left: 35%; }
.DCD { top: 72%; left: 65%; }
.DD  { top: 72%; left: 85%; }

.MCG { top: 48%; left: 35%; }
.MC  { top: 48%; left: 50%; }
.MCD { top: 48%; left: 65%; }

.AG  { top: 18%; left: 15%; }
.AC  { top: 12%; left: 50%; }
.AD  { top: 18%; left: 85%; }

.btn {
    padding: 10px 16px;
    border-radius: 8px;
    font-weight: bold;
    border: none;
}
.btn-green { background: #2e7d32; color: #fff; }
.btn-grey { background: #607d8b; color: #fff; }
</style>

<h1>⚽ Composition du match</h1>

<p>
<strong><?= htmlspecialchars($match["adversaire"]) ?></strong> —
<?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
</p>

<div class="wrapper">

    <!-- ===== BANC ===== -->
    <div class="bench" id="bench">
        <h3>Remplaçants</h3>
        <?php foreach ($joueurs as $j): ?>
            <div class="player" draggable="true" data-id="<?= $j["id_joueur"] ?>">
                <?= htmlspecialchars($j["nom"]." ".$j["prenom"]) ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ===== TERRAIN ===== -->
    <div class="field">
        <?php
        $mapping = [
            "GAR"=>"GAR",
            "DG"=>"DEF","DCG"=>"DEF","DCD"=>"DEF","DD"=>"DEF",
            "MCG"=>"MIL","MC"=>"MIL","MCD"=>"MIL",
            "AG"=>"ATT","AC"=>"ATT","AD"=>"ATT"
        ];
        foreach ($mapping as $pos => $posteCode):
            $id_poste = array_search($posteCode, $postes);
        ?>
        <div class="slot <?= $pos ?>" data-poste="<?= $id_poste ?>" data-position="<?= $pos ?>">
            <span class="label"><?= $pos ?></span>
        </div>
        <?php endforeach; ?>
    </div>

</div>

<form method="post" id="formCompo">
    <button type="submit" class="btn btn-green">💾 Enregistrer</button>
    <a href="../matchs/liste_matchs.php" class="btn btn-grey">⬅ Retour</a>
</form>

<script>
let dragged = null;

/* ===== DRAG ===== */
document.addEventListener("dragstart", e => {
    if (e.target.classList.contains("player")) {
        dragged = e.target;
    }
});

/* ===== DROP SUR POSTE ===== */
document.querySelectorAll(".slot").forEach(slot => {

    slot.addEventListener("dragover", e => e.preventDefault());

    slot.addEventListener("drop", e => {
        e.preventDefault();
        if (!dragged || slot.classList.contains("filled")) return;

        slot.innerHTML = `
            <div>${dragged.textContent}</div>
            <button type="button" class="remove-btn">✖ Retirer</button>
        `;
        slot.dataset.joueur = dragged.dataset.id;
        slot.classList.add("filled");

        dragged.remove();
        dragged = null;
    });
});

/* ===== RETIRER ===== */
document.addEventListener("click", e => {
    if (!e.target.classList.contains("remove-btn")) return;

    const slot = e.target.closest(".slot");
    const joueurId = slot.dataset.joueur;
    const joueurNom = slot.querySelector("div").textContent;

    const div = document.createElement("div");
    div.className = "player";
    div.draggable = true;
    div.dataset.id = joueurId;
    div.textContent = joueurNom;

    document.getElementById("bench").appendChild(div);

    slot.innerHTML = `<span class="label">${slot.dataset.position}</span>`;
    slot.classList.remove("filled");
    delete slot.dataset.joueur;
});

/* ===== SUBMIT ===== */
document.getElementById("formCompo").addEventListener("submit", e => {
    e.preventDefault();

    const slots = document.querySelectorAll(".slot.filled");
    if (slots.length !== 11) {
        alert("Il faut placer exactement 11 joueurs.");
        return;
    }

    slots.forEach(s => {
        const input = document.createElement("input");
        input.type = "hidden";
        input.name = "titulaire[" + s.dataset.poste + "]";
        input.value = s.dataset.joueur;
        e.target.appendChild(input);
    });

    e.target.submit();
});
</script>

<?php include "../includes/footer.php"; ?>
