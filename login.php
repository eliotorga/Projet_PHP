<?php
session_start();

require_once __DIR__ . "/includes/config.php";

// Si déjà connecté -> redirection
if (isset($_SESSION["user_id"])) {
    header("Location: /Projet_PHP/index.php");
    exit;
}

$error = "";

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login !== $AUTH_LOGIN) {
        $error = "Identifiants incorrects.";
    }
    elseif (!password_verify($password, $AUTH_HASH)) {
        $error = "Identifiants incorrects.";
    }
    else {
        session_regenerate_id(true);
        $_SESSION["user_id"] = $login;

        header("Location: /Projet_PHP/index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="/Projet_PHP/assets/css/style.css">
</head>
<body>

<div class="container" style="padding:20px;">
    <h2>Connexion</h2>

    <form method="POST" action="">
        <label>Nom d'utilisateur</label><br>
        <input type="text" name="login" required><br><br>

        <label>Mot de passe</label><br>
        <input type="password" name="password" required><br><br>

        <button type="submit">Se connecter</button>
    </form>

    <?php if (!empty($error)) : ?>
        <p style="color: red; font-weight:bold;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
</div>

</body>
</html>
