<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/match.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    add_match($_POST);
    header('Location: liste.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ajouter match</title></head>
<body>

<h1>Ajouter un match</h1>

<form method="post">
    Date et heure : <input type="datetime-local" name="date_heure" required><br>
    Équipe adverse : <input type="text" name="equipe_adverse" required><br>
    Lieu :
    <select name="lieu">
        <option value="Domicile">Domicile</option>
        <option value="Extérieur">Extérieur</option>
    </select><br>
    Résultat :
    <select name="resultat">
        <option value="">Non joué</option>
        <option value="Victoire">Victoire</option>
        <option value="Nul">Nul</option>
        <option value="Défaite">Défaite</option>
    </select><br>

    <button type="submit">Ajouter</button>
</form>

</body>
</html>
