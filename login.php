<?php
session_start();

/*
 |------------------------------------------
 | IDENTIFIANTS (en dur, conforme au sujet)
 |------------------------------------------
 */
$AUTH_LOGIN = "admin";
$AUTH_PASSWORD = "admin";

$error = "";

/*
 |------------------------------------------
 | Déjà connecté → redirection
 |------------------------------------------
 */
if (isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

/*
 |------------------------------------------
 | Traitement formulaire
 |------------------------------------------
 */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["login"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($login !== $AUTH_LOGIN || $password !== $AUTH_PASSWORD) {
        $error = "Identifiant ou mot de passe incorrect.";
    } else {
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
<title>Connexion – Coach Manager</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<style>
/* =========================
   RESET & GLOBAL
========================= */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
    font-family: "Segoe UI", Roboto, Arial, sans-serif;
}

body {
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    background:
        radial-gradient(900px 700px at 15% 20%, rgba(46,204,113,.25), transparent 60%),
        radial-gradient(900px 700px at 85% 80%, rgba(52,152,219,.25), transparent 60%),
        linear-gradient(180deg, #0f2027, #203a43, #2c5364);
    color: #fff;
    padding: 20px;
}

/* =========================
   CONTAINER
========================= */
.login-container {
    display: grid;
    grid-template-columns: 1.1fr 0.9fr;
    max-width: 900px;
    width: 100%;
    gap: 24px;
}

/* =========================
   LEFT – BRAND / DA FOOT
========================= */
.brand {
    background: linear-gradient(135deg, #1e7c3a, #0b3d1e);
    border-radius: 22px;
    padding: 36px;
    box-shadow: 0 30px 60px rgba(0,0,0,0.45);
}

.brand h1 {
    font-size: 2.4em;
}

.brand p {
    margin-top: 14px;
    opacity: 0.9;
    line-height: 1.6;
}

.features {
    margin-top: 28px;
    display: grid;
    gap: 14px;
}

.feature {
    background: rgba(255,255,255,0.12);
    padding: 14px 16px;
    border-radius: 14px;
}

.feature strong {
    display: block;
    font-size: 1em;
}

.feature span {
    font-size: 0.9em;
    opacity: 0.85;
}

/* =========================
   RIGHT – LOGIN CARD
========================= */
.card {
    background: rgba(255,255,255,0.14);
    backdrop-filter: blur(12px);
    border-radius: 22px;
    padding: 32px;
    box-shadow: 0 25px 55px rgba(0,0,0,0.45);
}

.card h2 {
    font-size: 1.8em;
    margin-bottom: 6px;
}

.card p {
    opacity: 0.85;
    margin-bottom: 22px;
}

/* =========================
   FORM
========================= */
.form-group {
    margin-bottom: 16px;
}

label {
    font-size: 0.85em;
    opacity: 0.9;
}

input {
    width: 100%;
    padding: 12px 14px;
    border-radius: 12px;
    border: none;
    margin-top: 6px;
    background: rgba(0,0,0,0.35);
    color: #fff;
    outline: none;
    font-size: 0.95em;
}

input:focus {
    box-shadow: 0 0 0 3px rgba(46,204,113,0.45);
}

/* =========================
   BUTTON
========================= */
button {
    width: 100%;
    margin-top: 12px;
    padding: 14px;
    border-radius: 14px;
    border: none;
    background: linear-gradient(135deg, #2ecc71, #27ae60);
    color: #fff;
    font-weight: bold;
    font-size: 1em;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
}

button:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.45);
}

/* =========================
   ERROR / INFO
========================= */
.error {
    background: rgba(231,76,60,0.25);
    border: 1px solid rgba(231,76,60,0.6);
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 14px;
    font-size: 0.9em;
    text-align: center;
}

.expired {
    background: rgba(255,193,7,0.25);
    border: 1px solid rgba(255,193,7,0.6);
    padding: 12px;
    border-radius: 12px;
    margin-bottom: 14px;
    font-size: 0.9em;
    text-align: center;
}

/* =========================
   RESPONSIVE
========================= */
@media (max-width: 900px) {
    .login-container {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="login-container">

    <!-- GAUCHE : BRAND -->
    <div class="brand">
        <h1>⚽ Coach Manager</h1>
        <p>
            Application de gestion d’équipe de football destinée aux entraîneurs.
            Prépare tes matchs, choisis tes joueurs et analyse les performances.
        </p>

        <div class="features">
            <div class="feature">
                <strong>🧩 Composition</strong>
                <span>Créer la feuille de match avant la rencontre</span>
            </div>
            <div class="feature">
                <strong>⭐ Évaluation</strong>
                <span>Noter les joueurs après le match</span>
            </div>
            <div class="feature">
                <strong>📊 Statistiques</strong>
                <span>Aider l’entraîneur à prendre des décisions</span>
            </div>
        </div>
    </div>

    <!-- DROITE : LOGIN -->
    <div class="card">
        <h2>Connexion</h2>
        <p>Accès réservé à l’entraîneur</p>

        <?php if (isset($_GET["expired"])): ?>
            <div class="expired">
                ⏰ Votre session a expiré après une période d’inactivité.
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>Identifiant</label>
                <input type="text" name="login" placeholder="admin" required>
            </div>

            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" placeholder="admin" required>
            </div>

            <button type="submit">Se connecter</button>
        </form>
    </div>

</div>

</body>
</html>
