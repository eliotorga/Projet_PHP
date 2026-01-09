<!-- vue pour la page de connexion avec formulaire d'authentification -->
<!-- affiche un formulaire moderne avec champs login et mot de passe -->

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Gestion d'Équipe de Foot</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/login-modern.css">
</head>
<body>
    <div class="bg-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>

    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-futbol"></i>
            </div>
            <h1>Gestion d'Équipe</h1>
            <p>Accès sécurisé</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="login">Identifiant</label>
                <div class="input-wrapper">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" 
                           id="login" 
                           name="login" 
                           class="form-control" 
                           placeholder="Votre identifiant" 
                           required 
                           autofocus>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Mot de passe</label>
                <div class="input-wrapper">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           class="form-control" 
                           placeholder="Votre mot de passe" 
                           required>
                </div>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i>
                <span>Se connecter</span>
            </button>
        </form>
        
        <div class="footer">
            Made by Elio Torga & William Fincan
        </div>
    </div>

    <script>
        document.addEventListener('mousemove', (e) => {
            const container = document.querySelector('.login-container');
            const x = e.clientX / window.innerWidth;
            const y = e.clientY / window.innerHeight;
            
            container.style.setProperty('--x', x);
            container.style.setProperty('--y', y);
            
            // Effet de profondeur au survol
            container.addEventListener('mousemove', (e) => {
                const rect = container.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                container.style.setProperty('--mouse-x', `${x}px`);
                container.style.setProperty('--mouse-y', `${y}px`);
            });
        });
    </script>
</body>
</html>