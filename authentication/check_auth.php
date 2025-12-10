<?php
session_start();

$USER = "admin";
$PASS = "admin123";

if ($_POST["username"] === $USER && $_POST["password"] === $PASS) {
    $_SESSION["auth"] = true;
    header("Location: ../index.php");
    exit;
}

echo "Identifiants incorrects.";
