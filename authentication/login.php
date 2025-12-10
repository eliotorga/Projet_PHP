<?php
session_start();

// Redirection si déjà connecté
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Inclure la configuration
require_once 'BDD/config.php';

// Variables pour les messages
$error = '';
$success = '';
$username = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validation
    if (empty($username) || empty($password)) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        try {
            // Connexion à la base de données
            $pdo = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8",
                DB_USER,
                DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            
            // Préparation de la requête
            $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE username = :username OR email = :email LIMIT 1");
            $stmt->execute([':username' => $username, ':email' => $username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Vérification du mot de passe
                if (password_verify($password, $user['password'])) {
                    
                    // Vérifier si le compte est actif
                    if ($user['actif'] == 0) {
                        $error = "Votre compte est désactivé. Contactez l'administrateur.";
                    } else {
                        // Mettre à jour la dernière connexion
                        $updateStmt = $pdo->prepare("UPDATE utilisateurs SET derniere_connexion = NOW() WHERE id = :id");
                        $updateStmt->execute([':id' => $user['id']]);
                        
                        // Créer la session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['nom_complet'] = $user['nom'] . ' ' . $user['prenom'];
                        
                        // Cookie "Se souvenir de moi"
                        if ($remember) {
                            $token = bin2hex(random_bytes(32));
                            $expiry = time() + (30 * 24 * 60 * 60); // 30 jours
                            
                            $stmt = $pdo->prepare("INSERT INTO tokens_remember (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)");
                            $stmt->execute([
                                ':user_id' => $user['id'],
                                ':token' => hash('sha256', $token),
                                ':expires_at' => date('Y-m-d H:i:s', $expiry)
                            ]);
                            
                            setcookie('remember_token', $token, $expiry, '/', '', true, true);
                        }
                        
                        // Journalisation de la connexion
                        $logStmt = $pdo->prepare("INSERT INTO logs_connexion (user_id, ip_address, user_agent) VALUES (:user_id, :ip, :ua)");
                        $logStmt->execute([
                            ':user_id' => $user['id'],
                            ':ip' => $_SERVER['REMOTE_ADDR'],
                            ':ua' => $_SERVER['HTTP_USER_AGENT']
                        ]);
                        
                        // Redirection selon le rôle
                        $redirect = match($user['role']) {
                            'admin' => 'admin/dashboard.php',
                            'entraineur' => 'matchs/liste_matches.php',
                            'joueur' => 'statistiques/stats_joueur.php',
                            default => 'index.php'
                        };
                        
                        header('Location: ' . $redirect);
                        exit();
                    }
                } else {
                    $error = "Identifiants incorrects.";
                    
                    // Enregistrer la tentative échouée
                    $stmt = $pdo->prepare("UPDATE utilisateurs SET tentatives_echec = tentatives_echec + 1 WHERE id = :id");
                    $stmt->execute([':id' => $user['id']]);
                }
            } else {
                $error = "Identifiants incorrects.";
            }
        } catch (PDOException $e) {
            error_log("Erreur connexion: " . $e->getMessage());
            $error = "Une erreur est survenue. Veuillez réessayer.";
        }
    }
}

// Inclure le header si disponible
if (file_exists('includes/header.php')) {
    require_once 'includes/header.php';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Gestion Équipe Sportive</title>
    
    <!-- Inclure le CSS -->
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 450px;
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(to right, #4a6fa5, #3a5a8c);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }
        
        .login-header p {
            margin: 10px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
        
        .login-body {
            padding: 40px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 25px;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .alert-error {
            background-color: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background-color: #efffef;
            color: #2a7;
            border: 1px solid #cfc;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #333;
            font-size: 14px;
        }
        
        .input-with-icon {
            position: relative;
        }
        
        .input-with-icon i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
            font-size: 18px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            border-color: #4a6fa5;
            box-shadow: 0 0 0 3px rgba(74, 111, 165, 0.1);
            outline: none;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 25px;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: #4a6fa5;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(to right, #4a6fa5, #3a5a8c);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 25px;
        }
        
        .btn-login:hover {
            background: linear-gradient(to right, #3a5a8c, #2a4a7c);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(58, 90, 140, 0.3);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .login-footer {
            text-align: center;
            padding-top: 25px;
            border-top: 1px solid #eee;
            color: #666;
            font-size: 14px;
        }
        
        .login-footer a {
            color: #4a6fa5;
            text-decoration: none;
            font-weight: 500;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            font-size: 18px;
        }
        
        .forgot-password {
            text-align: right;
            margin-bottom: 25px;
        }
        
        .forgot-password a {
            color: #4a6fa5;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password a:hover {
            text-decoration: underline;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .logo i {
            font-size: 50px;
            color: #4a6fa5;
            background: #f0f5ff;
            width: 100px;
            height: 100px;
            line-height: 100px;
            border-radius: 50%;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        @media (max-width: 480px) {
            .login-body {
                padding: 30px 20px;
            }
            
            .login-header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-users"></i>
            </div>
            <h1>Gestion Équipe Sportive</h1>
            <p>Connectez-vous pour accéder à votre espace</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Nom d'utilisateur ou Email</label>
                    <div class="input-with-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" 
                               id="username" 
                               name="username" 
                               class="form-control" 
                               placeholder="ex: john.doe ou john@exemple.com" 
                               value="<?php echo htmlspecialchars($username); ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Mot de passe</label>
                    <div class="input-with-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Votre mot de passe" 
                               required>
                        <button type="button" class="password-toggle" id="togglePassword">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="forgot-password">
                    <a href="forgot_password.php">Mot de passe oublié ?</a>
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Se souvenir de moi</label>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Se connecter
                </button>
                
                <div class="login-footer">
                    <p>Vous n'avez pas de compte ? <a href="register.php">Demander un accès</a></p>
                    <p><a href="index.php"><i class="fas fa-home"></i> Retour à l'accueil</a></p>
                </div>
            </form>
        </div>
    </div>

    <!-- Inclure le JavaScript -->
    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const icon = this.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
        
        // Validation du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Veuillez remplir tous les champs');
                return false;
            }
            
            // Afficher un indicateur de chargement
            const submitBtn = this.querySelector('.btn-login');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Connexion en cours...';
            submitBtn.disabled = true;
        });
        
        // Auto-focus sur le premier champ
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
    </script>
</body>
</html>

<?php
// Inclure le footer si disponible
if (file_exists('includes/footer.php')) {
    require_once 'includes/footer.php';
}
?>