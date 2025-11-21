<?php
// lib/auth.php
session_start();

function require_login() {
    if (!isset($_SESSION['id_utilisateur'])) {
        header('Location: /projet-php/index.php');
        exit;
    }
}
