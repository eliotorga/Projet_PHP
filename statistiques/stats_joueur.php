<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<h2>Statistiques joueurs</h2>

<?php
$req = $pdo->query("SELECT * FROM joueurs");
foreach ($req as $j) {
    echo "<p>{$j['nom']} – Poste : {$j['poste']}</p>";
}
include("../includes/footer.php");
