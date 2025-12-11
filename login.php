<?php
// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Si déjà connecté, rediriger
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Inclure l'authentification
require_once 'includes/auth.php';

$erreur = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom_utilisateur = trim($_POST['nom_utilisateur'] ?? '');
    $mot_de_passe = $_POST['mot_de_passe'] ?? '';
    
    if (empty($nom_utilisateur) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        if (authentifier($nom_utilisateur, $mot_de_passe)) {
            $_SESSION['message'] = "Bienvenue, " . htmlspecialchars($nom_utilisateur) . "!";
            $_SESSION['message_type'] = "success";
            header('Location: index.php');
            exit();
        } else {
            $erreur = "Nom d'utilisateur ou mot de passe incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football Team Manager - Connexion</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        
        .login-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        
        .login-header {
            background: #2c3e50;
            color: white;
            padding: 30px 20px;
            text-align: center;
        }
        
        .login-header h1 {
            margin: 0;
            font-size: 1.8em;
            color: white;
        }
        
        .login-header i {
            font-size: 3em;
            margin-bottom: 15px;
            display: block;
        }
        
        .login-body {
            padding: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2c3e50;
            font-weight: 600;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn-login {
            display: block;
            width: 100%;
            padding: 14px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 20px;
        }
        
        .btn-login:hover {
            background: #2980b9;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            color: #7f8c8d;
            font-size: 0.9em;
        }
        
        .credentials-note {
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            padding: 10px 15px;
            margin-top: 20px;
            font-size: 0.85em;
            color: #555;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <i class="fas fa-futbol"></i>
                <h1>Football Team Manager</h1>
                <p>Connexion Entraîneur</p>
            </div>
            
            <div class="login-body">
                <?php if ($erreur): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo $erreur; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="nom_utilisateur">
                            <i class="fas fa-user"></i> Nom d'utilisateur
                        </label>
                        <input type="text" 
                               id="nom_utilisateur" 
                               name="nom_utilisateur" 
                               class="form-control" 
                               value="<?php echo htmlspecialchars($_POST['nom_utilisateur'] ?? ''); ?>"
                               required 
                               autofocus>
                    </div>
                    
                    <div class="form-group">
                        <label for="mot_de_passe">
                            <i class="fas fa-lock"></i> Mot de passe
                        </label>
                        <input type="password" 
                               id="mot_de_passe" 
                               name="mot_de_passe" 
                               class="form-control" 
                               required>
                    </div>
                    
                    <button type="submit" class="btn-login">
                        <i class="fas fa-sign-in-alt"></i> Se connecter
                    </button>
                </form>
                
                <div class="login-footer">
                    <p>Application réservée à l'entraîneur de l'équipe</p>
                    
                    <div class="credentials-note">
                        <p><strong>Identifiants par défaut :</strong></p>
                        <p>Utilisateur: <code>entraineur</code></p>
                        <p>Mot de passe: <code>football2024</code></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>