<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

require_once "../bdd/db_match.php";

if (!isset($_POST["id_match"])) {
    header("Location: liste_matchs.php");
    exit;
}

$id_match = intval($_POST["id_match"]);

$data = [
    "date_heure"    => $_POST["date_heure"],
    "adversaire"    => $_POST["adversaire"],
    "lieu"          => $_POST["lieu"],
    "score_equipe"  => $_POST["score_equipe"] !== "" ? intval($_POST["score_equipe"]) : null,
    "score_adverse" => $_POST["score_adverse"] !== "" ? intval($_POST["score_adverse"]) : null,
    "resultat"      => null,
    "etat"          => $_POST["etat"]
];

if ($data["score_equipe"] !== null && $data["score_adverse"] !== null) {
    $data["etat"] = "JOUE";
    if ($data["score_equipe"] > $data["score_adverse"]) {
        $data["resultat"] = "VICTOIRE";
    } elseif ($data["score_equipe"] < $data["score_adverse"]) {
        $data["resultat"] = "DEFAITE";
    } else {
        $data["resultat"] = "NUL";
    }
}

// Mise à jour
updateMatch($gestion_sportive, $id_match, $data);

// Redirection
header("Location: liste_matchs.php?updated=1");
exit;
