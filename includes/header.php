<?php
// Header avec gestion des messages de session
// NE PAS démarrer la session ici, elle est déjà démarrée dans index.php et login.php

function afficherMessage() {
    if (isset($_SESSION['message'])) {
        $type = $_SESSION['message_type'] ?? 'info';
        $message = $_SESSION['message'];
        
        $class = '';
        $icon = '';
        
        switch ($type) {
            case 'success':
                $class = 'alert-success';
                $icon = 'fa-check-circle';
                break;
            case 'error':
                $class = 'alert-danger';
                $icon = 'fa-exclamation-circle';
                break;
            case 'warning':
                $class = 'alert-warning';
                $icon = 'fa-exclamation-triangle';
                break;
            default:
                $class = 'alert-info';
                $icon = 'fa-info-circle';
        }
        
        echo '<div class="alert ' . $class . '">';
        echo '<i class="fas ' . $icon . '"></i> ' . htmlspecialchars($message);
        echo '</div>';
        
        // Supprimer le message après l'avoir affiché
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Football Team Manager</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        .alert-info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        
        .alert i {
            margin-right: 10px;
            font-size: 1.2em;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        // Vérifier si la fonction existe avant de l'appeler
        if (function_exists('afficherMessage')) {
            afficherMessage();
        }
        ?>