<?php
session_start();

require_once __DIR__ . '/lib/auth.php';

// Identifiants définis par ton prof (en dur)
$LOGIN = "admin";
$PASSWORD_HASH = '$2y$10$2DaFhKz3YpG8DZb0cPVH7uF0zoVZ2J0f6Qr6vF00Y8SkIjr/xBrOm'; 
// = le hash du mot de passe : 1234

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($login === $LOGIN && password_verify($password, $PASSWORD_HASH)) {
        $_SESSION['logged'] = true;
        header("Location: menu.php");
        exit;
    } else {
        $error = "Identifiants incorrects.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">

<h1>Connexion</h1>

<?php if ($error): ?>
    <p style="color:red;"><?= $error ?></p>
<?php endif; ?>

<form method="post">
    Login : <input type="text" name="login"><br>
    Mot de passe : <input type="password" name="password"><br>
    <button type="submit">Se connecter</button>
</form>

</div>
</body>
</html>
