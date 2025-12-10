<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<h2>Liste des joueurs</h2>
<a href="ajouter_joueur.php">Ajouter un joueur</a>

<?php
$req = $pdo->query("SELECT * FROM joueurs");
foreach ($req as $j) {
    echo "<p>{$j['nom']} ({$j['poste']}) 
          <a href='modification_joueur.php?id={$j['id']}'>Modifier</a> 
          <a href='supprimer_joueur.php?id={$j['id']}'>Supprimer</a>
          </p>";
}
include("../includes/footer.php");
