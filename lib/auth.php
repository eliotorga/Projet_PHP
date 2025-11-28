<?php

session_start();

function require_login() {
    if (!isset($_SESSION['logged']) || $_SESSION['logged'] !== true) {
        header("Location: /PROJET_PHP/index.php");
        exit;
    }
}
