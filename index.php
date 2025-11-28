<?php
require_once __DIR__ . '/lib/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    // Requête préparée -> pas d'injection
    $stmt = $pdo->prepare("SELECT * FROM utilisateur WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['mot_de_passe_hash'])) {
        $_SESSION['id_utilisateur'] = $user['id_utilisateur'];
        $_SESSION['login'] = $user['login'];
        header('Location: menu.php');
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
</head>
<body>
<h1>Connexion entraîneur</h1>

<?php if ($error): ?>
<p style="color:red;"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<form method="post">
    Login : <input type="text" name="login" required><br>
    Mot de passe : <input type="password" name="password" required><br>
    <button type="submit">Se connecter</button>
</form>
</body>
</html>
