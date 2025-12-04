<?php
session_start();
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/joueur.php';

if (isset($_GET['id'])) {
    delete_joueur((int) $_GET['id']);
}

header("Location: liste.php");
exit;
