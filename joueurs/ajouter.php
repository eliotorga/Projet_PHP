<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/joueur.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    add_joueur($_POST);
    header('Location: liste.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ajouter joueur</title></head>
<body>
<h1>Ajouter un joueur</h1>

<form method="post">
    Nom : <input type="text" name="nom" required><br>
    Prénom : <input type="text" name="prenom" required><br>
    Licence : <input type="text" name="num_licence" required><br>
    Poids (kg) : <input type="number" step="0.1" name="poids_kg"><br>
    Taille (cm) : <input type="number" name="taille_cm"><br>
    Date de naissance : <input type="date" name="date_naissance"><br>
    Statut :
    <select name="statut">
        <option value="Actif">Actif</option>
        <option value="Blessé">Blessé</option>
        <option value="Suspendu">Suspendu</option>
        <option value="Absent">Absent</option>
    </select><br>
    Commentaire : <br>
    <textarea name="commentaire"></textarea><br>
    <button type="submit">Enregistrer</button>
</form>
</body>

</html>