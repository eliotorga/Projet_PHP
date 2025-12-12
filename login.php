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
    <title>Connexion – Coach Manager Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&family=Open+Sans:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
    /* =============================
       VARIABLES & RESET
    ============================= */
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --secondary: #2ecc71;
        --accent: #f39c12;
        --danger: #e74c3c;
        --warning: #e67e22;
        --info: #3498db;
        --light: #ecf0f1;
        --dark: #0c2918;
        --darker: #071a10;
        --grass: #2E8B57;
        --grass-dark: #1E6B47;
        --field-lines: #FFFFFF;
        --jersey-home: #DC143C;
        --jersey-away: #4169E1;
        --shadow: 0 15px 35px rgba(0,0,0,0.3);
        --radius: 16px;
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        position: relative;
        overflow-x: hidden;
    }

    /* =============================
       BACKGROUND FOOTBALL
    ============================= */
    .pitch-pattern {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: 
            linear-gradient(45deg, transparent 48%, rgba(255,255,255,0.03) 50%, transparent 52%),
            linear-gradient(-45deg, transparent 48%, rgba(255,255,255,0.03) 50%, transparent 52%);
        background-size: 80px 80px;
        z-index: -2;
        opacity: 0.2;
    }

    .stadium-effect {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 120%;
        height: 120%;
        background: radial-gradient(circle at center, 
            transparent 30%, 
            rgba(46, 139, 87, 0.1) 70%, 
            transparent 100%
        );
        z-index: -1;
    }

    .floating-ball {
        position: fixed;
        width: 60px;
        height: 60px;
        background: radial-gradient(circle at 30% 30%, #fff, #aaa);
        border-radius: 50%;
        animation: floatBall 8s ease-in-out infinite;
        box-shadow: 
            inset -5px -5px 10px rgba(0,0,0,0.3),
            inset 2px 2px 5px rgba(255,255,255,0.5),
            0 5px 15px rgba(0,0,0,0.3);
        z-index: -1;
    }

    .ball-1 {
        top: 20%;
        left: 10%;
        animation-delay: 0s;
    }

    .ball-2 {
        top: 60%;
        right: 15%;
        animation-delay: 2s;
        width: 40px;
        height: 40px;
    }

    .ball-3 {
        bottom: 20%;
        left: 20%;
        animation-delay: 4s;
        width: 30px;
        height: 30px;
    }

    @keyframes floatBall {
        0%, 100% { 
            transform: translate(0, 0) rotate(0deg); 
        }
        33% { 
            transform: translate(30px, -40px) rotate(120deg); 
        }
        66% { 
            transform: translate(-20px, 20px) rotate(240deg); 
        }
    }

    /* =============================
       MAIN CONTAINER
    ============================= */
    .login-container {
        display: grid;
        grid-template-columns: 1.1fr 0.9fr;
        max-width: 1100px;
        width: 100%;
        gap: 0;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(15px);
        border-radius: var(--radius);
        overflow: hidden;
        border: 2px solid rgba(255, 255, 255, 0.2);
        box-shadow: var(--shadow);
        animation: slideIn 0.8s ease-out;
    }

    @keyframes slideIn {
        from { 
            opacity: 0; 
            transform: translateY(50px) scale(0.95); 
        }
        to { 
            opacity: 1; 
            transform: translateY(0) scale(1); 
        }
    }

    /* =============================
       LEFT PANEL - FOOTBALL THEME
    ============================= */
    .football-panel {
        background: linear-gradient(145deg, rgba(12, 41, 24, 0.95), rgba(26, 58, 38, 0.95));
        padding: 50px 40px;
        position: relative;
        overflow: hidden;
    }

    .football-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--jersey-home), var(--jersey-away));
    }

    .logo-section {
        margin-bottom: 40px;
        position: relative;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 20px;
        margin-bottom: 15px;
    }

    .logo-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, var(--jersey-home), var(--jersey-away));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 32px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        border: 3px solid rgba(255, 255, 255, 0.2);
    }

    .logo-text h1 {
        font-size: 28px;
        font-weight: 800;
        background: linear-gradient(to right, #ffffff, var(--secondary));
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
        line-height: 1.2;
    }

    .logo-text .tagline {
        font-size: 14px;
        color: rgba(255, 255, 255, 0.8);
        margin-top: 5px;
    }

    .welcome-message {
        font-size: 16px;
        line-height: 1.6;
        margin-bottom: 40px;
        color: rgba(255, 255, 255, 0.9);
        max-width: 500px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }

    .feature-card {
        background: rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        padding: 18px;
        border-left: 3px solid var(--secondary);
        transition: var(--transition);
        backdrop-filter: blur(5px);
    }

    .feature-card:hover {
        background: rgba(255, 255, 255, 0.12);
        transform: translateY(-5px);
    }

    .feature-icon {
        color: var(--secondary);
        font-size: 20px;
        margin-bottom: 10px;
    }

    .feature-title {
        font-size: 14px;
        font-weight: 600;
        color: white;
        margin-bottom: 5px;
    }

    .feature-desc {
        font-size: 12px;
        color: rgba(255, 255, 255, 0.7);
        line-height: 1.4;
    }

    /* =============================
       RIGHT PANEL - LOGIN FORM
    ============================= */
    .login-panel {
        background: rgba(255, 255, 255, 0.98);
        padding: 50px 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        position: relative;
    }

    .login-panel::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .login-header {
        text-align: center;
        margin-bottom: 40px;
    }

    .login-header h2 {
        font-size: 28px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 10px;
        position: relative;
        display: inline-block;
    }

    .login-header h2::after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 3px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
        border-radius: 2px;
    }

    .login-header p {
        color: #666;
        font-size: 15px;
        margin-top: 20px;
        line-height: 1.5;
    }

    /* =============================
       FORM STYLES
    ============================= */
    .login-form {
        width: 100%;
    }

    .form-group {
        margin-bottom: 25px;
        position: relative;
    }

    .form-group label {
        display: block;
        font-size: 13px;
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
        padding-left: 5px;
    }

    .input-container {
        position: relative;
        border-radius: 10px;
        overflow: hidden;
        border: 2px solid #e0e0e0;
        transition: var(--transition);
        background: white;
    }

    .input-container:focus-within {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(30, 122, 60, 0.15);
        transform: translateY(-2px);
    }

    .input-icon {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #777;
        font-size: 18px;
        z-index: 2;
        transition: var(--transition);
    }

    .input-field {
        width: 100%;
        padding: 16px 20px 16px 50px;
        border: none;
        font-size: 15px;
        color: var(--dark);
        background: transparent;
        font-family: 'Open Sans', sans-serif;
        font-weight: 500;
        outline: none;
    }

    .input-field:focus + .input-icon {
        color: var(--primary);
    }

    .input-field::placeholder {
        color: #aaa;
    }

    /* =============================
       BUTTON
    ============================= */
    .login-btn {
        width: 100%;
        padding: 18px;
        background: linear-gradient(135deg, var(--jersey-home), #c1121f);
        color: white;
        border: none;
        border-radius: 10px;
        font-size: 16px;
        font-weight: 700;
        cursor: pointer;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        margin-top: 10px;
        box-shadow: 0 8px 20px rgba(220, 20, 60, 0.3);
        position: relative;
        overflow: hidden;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .login-btn::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, 
            transparent, 
            rgba(255, 255, 255, 0.2), 
            transparent
        );
        transition: 0.5s;
    }

    .login-btn:hover {
        background: linear-gradient(135deg, #c1121f, var(--jersey-home));
        transform: translateY(-3px);
        box-shadow: 0 12px 25px rgba(220, 20, 60, 0.4);
    }

    .login-btn:hover::before {
        left: 100%;
    }

    .login-btn:active {
        transform: translateY(-1px);
    }

    /* =============================
       MESSAGES
    ============================= */
    .message-container {
        margin-bottom: 25px;
        animation: fadeIn 0.5s ease;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .message {
        padding: 16px 20px;
        border-radius: 10px;
        text-align: center;
        font-size: 14px;
        font-weight: 500;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 12px;
        border: 2px solid;
        background: white;
    }

    .error-message {
        border-color: var(--danger);
        color: var(--danger);
        background: linear-gradient(135deg, 
            rgba(231, 76, 60, 0.08), 
            rgba(231, 76, 60, 0.04)
        );
    }

    .warning-message {
        border-color: var(--warning);
        color: var(--warning);
        background: linear-gradient(135deg, 
            rgba(230, 126, 34, 0.08), 
            rgba(230, 126, 34, 0.04)
        );
    }

    /* =============================
       FOOTER
    ============================= */
    .login-footer {
        text-align: center;
        margin-top: 40px;
        padding-top: 25px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 13px;
    }

    .login-footer p {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .credentials-hint {
        background: linear-gradient(135deg, 
            rgba(46, 139, 87, 0.08), 
            rgba(65, 105, 225, 0.08)
        );
        padding: 12px;
        border-radius: 8px;
        margin-top: 15px;
        border: 1px solid rgba(46, 139, 87, 0.2);
    }

    .credentials-hint h4 {
        color: var(--dark);
        margin-bottom: 8px;
        font-size: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
    }

    .credentials-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-top: 10px;
    }

    .cred-item {
        text-align: center;
        padding: 8px;
        background: white;
        border-radius: 6px;
        border: 1px solid #eee;
    }

    .cred-label {
        font-size: 11px;
        color: #777;
        margin-bottom: 3px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .cred-value {
        font-weight: 700;
        color: var(--primary);
        font-size: 13px;
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 992px) {
        .login-container {
            grid-template-columns: 1fr;
            max-width: 500px;
        }
        
        .football-panel, .login-panel {
            padding: 40px 30px;
        }
        
        .features-grid {
            grid-template-columns: 1fr;
        }
        
        .floating-ball {
            opacity: 0.3;
        }
    }

    @media (max-width: 480px) {
        body {
            padding: 15px;
        }
        
        .football-panel, .login-panel {
            padding: 30px 20px;
        }
        
        .logo {
            flex-direction: column;
            text-align: center;
            gap: 15px;
        }
        
        .logo-text h1 {
            font-size: 24px;
        }
        
        .login-header h2 {
            font-size: 24px;
        }
        
        .input-field {
            padding: 14px 20px 14px 45px;
            font-size: 14px;
        }
        
        .login-btn {
            padding: 16px;
            font-size: 15px;
        }
        
        .credentials-grid {
            grid-template-columns: 1fr;
        }
    }
    </style>
</head>

<body>
    <!-- BACKGROUND ELEMENTS -->
    <div class="pitch-pattern"></div>
    <div class="stadium-effect"></div>
    <div class="floating-ball ball-1"></div>
    <div class="floating-ball ball-2"></div>
    <div class="floating-ball ball-3"></div>

    <div class="login-container">
        <!-- LEFT PANEL: FOOTBALL THEME -->
        <div class="football-panel">
            <div class="logo-section">
                <div class="logo">
                    <div class="logo-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="logo-text">
                        <h1>COACH MANAGER PRO</h1>
                        <div class="tagline">Système de Gestion d'Équipe</div>
                    </div>
                </div>
                
                <p class="welcome-message">
                    Application professionnelle pour la gestion complète de votre équipe de football. 
                    Optimisez vos performances avec des outils dédiés aux entraîneurs.
                </p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="feature-title">Effectif</div>
                    <div class="feature-desc">Gérez vos joueurs et leur statut</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="feature-title">Matchs</div>
                    <div class="feature-desc">Planifiez et analysez vos rencontres</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="feature-title">Statistiques</div>
                    <div class="feature-desc">Suivez les performances en détail</div>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <div class="feature-title">Tactiques</div>
                    <div class="feature-desc">Créez vos compositions d'équipe</div>
                </div>
            </div>
            
            <div style="margin-top: 40px; text-align: center;">
                <div style="color: var(--secondary); font-size: 13px; margin-bottom: 8px;">
                    <i class="fas fa-star"></i> Outil Professionnel pour Entraîneurs
                </div>
                <div style="color: rgba(255,255,255,0.7); font-size: 12px;">
                    Développé pour optimiser la gestion de votre équipe
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: LOGIN FORM -->
        <div class="login-panel">
            <div class="login-header">
                <h2>Accès Staff</h2>
                <p>Réservé à l'encadrement technique de l'équipe</p>
            </div>

            <div class="message-container">
                <?php if (isset($_GET["expired"])): ?>
                    <div class="message warning-message">
                        <i class="fas fa-clock"></i>
                        <span>Session expirée. Veuillez vous reconnecter.</span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="message error-message">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <form class="login-form" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="login">Identifiant</label>
                    <div class="input-container">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               id="login" 
                               name="login" 
                               class="input-field" 
                               placeholder="admin" 
                               required
                               autocomplete="username">
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-container">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="input-field" 
                               placeholder="••••••••" 
                               required
                               autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="login-btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>SE CONNECTER</span>
                </button>
            </form>

            <div class="login-footer">
                <p><i class="fas fa-shield-alt"></i> Connexion sécurisée</p>
                <p>© 2024 Coach Manager Pro - Tous droits réservés</p>
                <div class="credentials-hint">
                    <h4><i class="fas fa-key"></i> Identifiants de test</h4>
                    <div class="credentials-grid">
                        <div class="cred-item">
                            <div class="cred-label">Utilisateur</div>
                            <div class="cred-value">admin</div>
                        </div>
                        <div class="cred-item">
                            <div class="cred-label">Mot de passe</div>
                            <div class="cred-value">admin</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loginForm = document.getElementById('loginForm');
        const submitBtn = document.getElementById('submitBtn');
        
        // Animation d'entrée des champs
        const formGroups = document.querySelectorAll('.form-group');
        formGroups.forEach((group, index) => {
            group.style.opacity = '0';
            group.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                group.style.transition = 'all 0.5s ease';
                group.style.opacity = '1';
                group.style.transform = 'translateY(0)';
            }, index * 200);
        });
        
        // Effets de survol
        const inputContainers = document.querySelectorAll('.input-container');
        inputContainers.forEach(container => {
            const input = container.querySelector('input');
            
            input.addEventListener('focus', function() {
                container.style.borderColor = 'var(--primary)';
                container.style.boxShadow = '0 0 0 3px rgba(30, 122, 60, 0.15)';
                container.style.transform = 'translateY(-2px)';
            });
            
            input.addEventListener('blur', function() {
                container.style.borderColor = '#e0e0e0';
                container.style.boxShadow = 'none';
                container.style.transform = 'translateY(0)';
            });
        });
        
        // Animation du bouton
        submitBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
        });
        
        submitBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
        
        // Validation simple sans bug
        loginForm.addEventListener('submit', function(e) {
            const login = document.getElementById('login').value.trim();
            const password = document.getElementById('password').value;
            
            if (!login || !password) {
                e.preventDefault();
                showMessage('Veuillez remplir tous les champs', 'error');
                return false;
            }
            
            // Petit effet visuel avant soumission
            submitBtn.style.background = 'linear-gradient(135deg, var(--primary), var(--primary-dark))';
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>CONNEXION...</span>';
            
            // La soumission se fait normalement via PHP
            return true;
        });
        
        function showMessage(text, type) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            messageDiv.innerHTML = `
                <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                <span>${text}</span>
            `;
            
            const container = document.querySelector('.message-container');
            container.innerHTML = '';
            container.appendChild(messageDiv);
        }
        
        // Effet d'animation au chargement
        setTimeout(() => {
            document.querySelector('.login-container').style.animation = 'slideIn 0.8s ease-out';
        }, 100);
    });
    </script>
</body>
</html>