<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion – Coach Manager Pro</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Open+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <link rel="stylesheet" href="assets/css/login.css"></head>

<body>
    <!-- BACKGROUND ELEMENTS -->
    <div class="bg-pattern"></div>
    <div class="grid-overlay"></div>
    <div class="floating-shapes">
        <div class="shape shape-1"></div>
        <div class="shape shape-2"></div>
        <div class="shape shape-3"></div>
    </div>
    <div class="football-icon ball-1"></div>
    <div class="football-icon ball-2"></div>

    <div class="login-container animate__animated animate__fadeIn">
        <!-- LEFT PANEL: BRAND & FEATURES -->
        <div class="brand-panel">
            <div class="brand-header">
                <div class="brand-logo">
                    <div class="logo-circle">
                        <i class="fas fa-futbol"></i>
                    </div>
                    <div class="logo-text">
                        <h1>COACH MANAGER PRO</h1>
                        <div class="tagline">Système de Gestion d'Équipe Élite</div>
                    </div>
                </div>
                
                <p class="brand-description">
                    Application professionnelle pour la gestion complète de votre équipe de football. 
                    Optimisez vos performances avec des outils dédiés aux entraîneurs modernes.
                </p>
            </div>
            
            <div class="features-section">
                <h3>Fonctionnalités Principales</h3>
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="feature-title">Gestion d'Effectif</div>
                        <div class="feature-desc">Suivez vos joueurs, leurs statistiques et leur état de forme en temps réel.</div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <div class="feature-title">Calendrier des Matchs</div>
                        <div class="feature-desc">Planifiez, analysez et évaluez chaque rencontre avec précision.</div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="feature-title">Analyses Avancées</div>
                        <div class="feature-desc">Statistiques détaillées et indicateurs de performance personnalisés.</div>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="feature-title">Tactique & Composition</div>
                        <div class="feature-desc">Créez et ajustez vos schémas tactiques et compositions d'équipe.</div>
                    </div>
                </div>
            </div>
            
            <div style="margin-top: 50px; text-align: center;">
                <div style="color: var(--primary-light); font-size: 14px; margin-bottom: 10px; font-weight: 500;">
                    <i class="fas fa-shield-alt"></i> Système Sécurisé & Professionnel
                </div>
                <div style="color: rgba(255,255,255,0.6); font-size: 13px; font-weight: 300;">
                    Conçu exclusivement pour les entraîneurs et staff techniques
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL: LOGIN FORM -->
        <div class="form-panel">
            <div class="form-header">
                <h2>Accès Staff Technique</h2>
                <p>Accédez au panneau d'administration de votre équipe</p>
            </div>

            <div class="message-container">
                <?php if (isset($_GET["expired"])): ?>
                    <div class="message warning-message animate__animated animate__shakeX">
                        <i class="fas fa-clock"></i>
                        <span>Session expirée. Veuillez vous reconnecter.</span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="message error-message animate__animated animate__shakeX">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <form class="login-form" method="POST" id="loginForm">
                <div class="form-group">
                    <label for="login" class="form-label">Identifiant Staff</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user input-icon"></i>
                        <input type="text" 
                               id="login" 
                               name="login" 
                               class="form-input" 
                               placeholder="Entrez votre identifiant" 
                               required
                               autocomplete="username"
                               autofocus>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock input-icon"></i>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-input" 
                               placeholder="Entrez votre mot de passe" 
                               required
                               autocomplete="current-password">
                    </div>
                </div>

                <button type="submit" class="login-btn" id="submitBtn">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>CONNEXION AU PANEL</span>
                </button>
            </form>

            <div class="form-footer">
                <div class="security-info">
                    <i class="fas fa-shield-alt"></i>
                    <span>Connexion chiffrée & sécurisée</span>
                </div>
                
                <div class="copyright">
                    © 2024 Coach Manager Pro - Version 2.0
                </div>
                
                <div class="credentials-hint">
                    <h4><i class="fas fa-key"></i> Identifiants de Démonstration</h4>
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
</body>
</html>