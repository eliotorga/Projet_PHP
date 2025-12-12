<?php
session_start();

/*
 |------------------------------------------
 | IDENTIFIANTS (en dur, comme demandé)
 |------------------------------------------
 */
$AUTH_LOGIN = "admin";
$AUTH_PASSWORD = "admin";

$error = "";

/*
 |------------------------------------------
 | Si déjà connecté → redirection
 |------------------------------------------
 */
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

/*
 |------------------------------------------
 | Traitement du formulaire
 |------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = $_POST["login"] ?? "";
    $password = $_POST["password"] ?? "";

    if ($login !== $AUTH_LOGIN || $password !== $AUTH_PASSWORD) {
        $error = "Identifiants incorrects.";
    } else {
        // Connexion OK
        session_regenerate_id(true);
        $_SESSION["user_id"] = $login;
        $_SESSION["last_activity"] = time();

        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f2f2;
        }
        .login-box {
            width: 350px;
            margin: 120px auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
        }
        input {
            width: 100%;
            padding: 8px;
            margin-top: 10px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            margin-top: 15px;
            background: #1976d2;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 15px;
        }
        button:hover {
            background: #125aa3;
        }
        .error {
            color: red;
            margin-top: 10px;
            text-align: center;
        }
        .expired {
            color: darkred;
            margin-bottom: 10px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="login-box">
    <h2>Connexion</h2>

    <?php if (isset($_GET["expired"])): ?>
        <div class="expired">
            ⏰ Votre session a expiré après 20 secondes d'inactivité.
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
        <input type="text" name="login" placeholder="Nom d'utilisateur" required>
        <input type="password" name="password" placeholder="Mot de passe" required>
        <button type="submit">Se connecter</button>
    </form>
</div>

</body>
</html>
