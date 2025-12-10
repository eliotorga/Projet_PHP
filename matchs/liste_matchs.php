<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<h2>Liste des matchs</h2>
<a href="ajouter_match.php">Ajouter un match</a>

<?php
$req = $pdo->query("SELECT * FROM matchs");
foreach ($req as $m) {
    echo "<p>Match du {$m['date_match']} 
          <a href='modifier_match.php?id={$m['id']}'>Modifier</a>
          <a href='supprimer_match.php?id={$m['id']}'>Supprimer</a>
          </p>";
}
include("../includes/footer.php");
