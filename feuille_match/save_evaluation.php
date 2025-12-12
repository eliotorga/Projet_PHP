<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_participation.php";

$id_match = intval($_POST["id_match"]);
$notes = $_POST["note"] ?? [];

foreach ($notes as $id_joueur => $note) {
    if (!empty($note)) {
        updateEvaluation($gestion_sportive, $id_match, $id_joueur, intval($note));
    }
}

header("Location: ../matchs/modifier_match.php?id_match=" . $id_match);
exit;
