<?php
// Footer simple
?>
    </div> <!-- Fermeture du container si ouvert dans header -->
    
    <footer style="margin-top: 50px; padding: 20px; text-align: center; background: #2c3e50; color: white;">
        <p>Football Team Manager &copy; <?php echo date('Y'); ?> - Application de gestion d'équipe de football</p>
        <p style="font-size: 0.9em; color: #bdc3c7;">
            Connecté en tant que: <strong><?php echo htmlspecialchars($_SESSION['user_nom'] ?? 'Invité'); ?></strong>
        </p>
    </footer>
</body>
</html>