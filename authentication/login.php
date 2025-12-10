<?php session_start(); ?>
<form method="POST" action="check_auth.php">
    <input type="text" name="username" placeholder="Utilisateur" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <button type="submit">Connexion</button>
</form>
