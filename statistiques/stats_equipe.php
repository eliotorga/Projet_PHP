<?php include("../includes/header.php"); include("../BDD/config.php"); ?>

<h2>Statistiques équipe</h2>

<?php
$stats = $pdo->query("SELECT
    SUM(resultat='gagne') AS g,
    SUM(resultat='perdu') AS p,
    SUM(resultat='nul') AS n,
    COUNT(*) AS total
FROM matchs")->fetch();

echo "<p>Gagnés : {$stats['g']}</p>";
echo "<p>Perdus : {$stats['p']}</p>";
echo "<p>Nuls : {$stats['n']}</p>";
?>

<?php include("../includes/footer.php"); ?>
