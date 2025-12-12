<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_joueur.php";

$id_match = intval($_GET["id_match"] ?? 0);
$match = getMatchById($gestion_sportive, $id_match);
if (!$match) die("Match introuvable");

$joueurs = array_filter(getAllPlayers($gestion_sportive), fn($j) => $j["id_statut"] == 1);

include __DIR__ . "/../includes/header.php";
?>

<style>
.layout { display:flex; gap:40px; margin-top:20px; }
.terrain {
    width:600px; height:900px;
    background:linear-gradient(#4caf50,#2e7d32);
    border-radius:18px;
    border:4px solid white;
    position:relative;
}
.poste {
    position:absolute;
    width:130px;
    min-height:70px;
    background:white;
    border:2px dashed #888;
    border-radius:8px;
    text-align:center;
    transform:translate(-50%,-50%);
    padding:6px;
}
.poste.over { background:#e3f2fd; border-color:#1976d2; }
.poste-joueur { margin-top:4px; }
.remove { color:red; cursor:pointer; font-size:12px; display:none; }
.liste {
    width:280px;
    background:#f5f5f5;
    padding:12px;
    border-radius:12px;
}
.joueur {
    background:white;
    border:1px solid #ccc;
    border-radius:6px;
    padding:6px;
    margin-bottom:6px;
    cursor:grab;
}
.joueur.dragging { opacity:0.5; }
button { padding:10px 20px; font-size:16px; }
</style>

<h2>⚽ Feuille de match — vs <?= htmlspecialchars($match["adversaire"]) ?></h2>

<form method="POST"
      action="sauvegarde_compo.php"
      onsubmit="return validationComposition();">

<input type="hidden" name="id_match" value="<?= $id_match ?>">

<div class="layout">

    <div class="terrain" id="terrain"></div>

    <div class="liste">
        <h3>Joueurs actifs</h3>
        <div id="listeJoueurs">
            <?php foreach ($joueurs as $j): ?>
                <div class="joueur" draggable="true" data-id="<?= $j["id_joueur"] ?>">
                    <?= htmlspecialchars($j["prenom"]." ".$j["nom"]) ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<br>
<button type="submit">💾 Enregistrer la composition</button>
</form>

<script>
/* ================= FORMATION FIXE (11 POSTES) ================= */
const postes = [
    {c:"GK",x:50,y:88},
    {c:"DG",x:20,y:65},{c:"DC1",x:40,y:65},{c:"DC2",x:60,y:65},{c:"DD",x:80,y:65},
    {c:"MG",x:20,y:43},{c:"MC1",x:40,y:43},{c:"MC2",x:60,y:43},{c:"MD",x:80,y:43},
    {c:"AC1",x:40,y:18},{c:"AC2",x:60,y:18}
];

const terrain = document.getElementById("terrain");
let dragged = null;

/* ================= RENDER TERRAIN ================= */
postes.forEach(p => {
    const d = document.createElement("div");
    d.className = "poste";
    d.style.top = p.y + "%";
    d.style.left = p.x + "%";
    d.innerHTML = `
        <strong>${p.c}</strong>
        <div class="poste-joueur">—</div>
        <div class="remove">Retirer</div>
        <input type="hidden" name="titulaire[${p.c}]" value="">
    `;
    terrain.appendChild(d);
    bindPoste(d);
});

/* ================= DRAG ================= */
function bindDrag(el) {
    el.addEventListener("dragstart", () => {
        dragged = el;
        el.classList.add("dragging");
    });
    el.addEventListener("dragend", () => {
        el.classList.remove("dragging");
        dragged = null;
    });
}
document.querySelectorAll(".joueur").forEach(bindDrag);

/* ================= POSTES ================= */
function bindPoste(poste) {

    poste.addEventListener("dragover", e => {
        e.preventDefault();
        poste.classList.add("over");
    });

    poste.addEventListener("dragleave", () => poste.classList.remove("over"));

    poste.addEventListener("drop", () => {
        poste.classList.remove("over");
        if (!dragged) return;

        const id = dragged.dataset.id;

        // Anti-doublon
        let deja = false;
        document.querySelectorAll(".poste input").forEach(i => {
            if (i.value === id) deja = true;
        });
        if (deja) {
            alert("❌ Joueur déjà placé");
            return;
        }

        poste.querySelector(".poste-joueur").innerText = dragged.innerText;
        poste.querySelector("input").value = id;
        poste.querySelector(".remove").style.display = "block";
        dragged.remove();
    });

    poste.querySelector(".remove").addEventListener("click", () => {
        const id = poste.querySelector("input").value;
        if (!id) return;

        const div = document.createElement("div");
        div.className = "joueur";
        div.draggable = true;
        div.dataset.id = id;
        div.innerText = poste.querySelector(".poste-joueur").innerText;
        document.getElementById("listeJoueurs").appendChild(div);
        bindDrag(div);

        poste.querySelector(".poste-joueur").innerText = "—";
        poste.querySelector("input").value = "";
        poste.querySelector(".remove").style.display = "none";
    });
}

/* ================= VALIDATION ================= */
function validationComposition() {
    const inputs = document.querySelectorAll(".poste input");
    let count = 0;
    let hasGK = false;

    inputs.forEach(i => {
        if (i.value !== "") {
            count++;
            if (i.name.includes("GK")) hasGK = true;
        }
    });

    if (count < 11) {
        alert("❌ La composition doit contenir 11 joueurs minimum.");
        return false;
    }
    if (!hasGK) {
        alert("❌ Un gardien est obligatoire.");
        return false;
    }
    return true;
}
</script>

<?php include __DIR__ . "/../includes/footer.php"; ?>
