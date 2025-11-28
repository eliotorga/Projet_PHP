<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/joueur.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    delete_joueur($id);
}
header('Location: liste.php');
exit;