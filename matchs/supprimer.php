<?php
require_once __DIR__ . '/../lib/auth.php';
require_login();
require_once __DIR__ . '/../lib/match.php';

if (isset($_GET['id'])) {
    delete_match((int)$_GET['id']);
}

header('Location: liste.php');
exit;
?>