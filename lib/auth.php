<?php
// NE PAS mettre session_start() ici

function is_logged(): bool {
    return !empty($_SESSION['logged']) && $_SESSION['logged'] === true;
}

function require_login(): void {
    if (!is_logged()) {
        header("Location: /PROJET_PHP/index.php");
        exit;
    }
}
