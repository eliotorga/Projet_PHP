<?php
session_start();
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

require_once "../bdd/db_participation.php";
require_once "../bdd/db_poste.php";
require_once "../bdd/db_match.php";


// ---------------------------
// Vérification ID match
// ---------------------------
if (!isset($_POST["id_match"])) {
    header("Location: ../matchs/liste_matchs.php");
    exit;
}

$id_match = intval($_POST["id_match"]);

$titulaires = $_POST["titulaire"] ?? [];
$remplacants = $_POST["remplacant"] ?? [];

// Minimum requis pour FOOTBALL
define("NB_TITULAIRES_MIN", 11);


// Récupérer la liste des postes
$postes = getAllPostes($gestion_sportive);


// ---------------------------
// VALIDATION DES DONNÉES
// ---------------------------
$erreurs = [];


// 1️⃣ Vérifier qu'il y a un titulaire pour chaque poste
foreach ($postes as $p) {
    $id_poste = $p["id_poste"];

    if (!isset($titulaires[$id_poste]) || $titulaires[$id_poste] == "") {
        $erreurs[] = "Le poste <strong>{$p["libelle"]}</strong> doit obligatoirement avoir un titulaire.";
    }
}


// 2️⃣ Vérifier nombre de titulaires
$nb_titulaires_valides = count(array_filter($titulaires));

if ($nb_titulaires_valides < NB_TITULAIRES_MIN) {
    $erreurs[] = "Il faut au minimum <strong>" . NB_TITULAIRES_MIN . " titulaires</strong> pour un match de football.";
}


// 3️⃣ Vérification des doublons (un joueur ne peut être à deux endroits)
$selection = [];

foreach ($titulaires as $id_poste => $id_joueur) {
    if ($id_joueur != "") {
        if (in_array($id_joueur, $selection)) {
            $erreurs[] = "Le joueur ID <strong>$id_joueur</strong> est sélectionné plusieurs fois (doublon).";
        } else {
            $selection[] = $id_joueur;
        }
    }
}

foreach ($remplacants as $id_joueur) {
    if ($id_joueur != "") {
        if (in_array($id_joueur, $selection)) {
            $erreurs[] = "Le joueur ID <strong>$id_joueur</strong> est sélectionné plusieurs fois (doublon ou remplaçant déjà titulaire).";
        } else {
            $selection[] = $id_joueur;
        }
    }
}


// ---------------------------
// SI ERREUR → RETOUR À LA PAGE
// ---------------------------
if (!empty($erreurs)) {
    $_SESSION["compo_error"] = implode("<br>", $erreurs);
    header("Location: composition.php?id_match=" . $id_match);
    exit;
}


// ---------------------------
// SAUVEGARDE EN BASE
// ---------------------------

// 1️⃣ Effacer l’ancienne composition
clearParticipation($gestion_sportive, $id_match);


// 2️⃣ Ajouter les titulaires
foreach ($titulaires as $id_poste => $id_joueur) {
    if ($id_joueur != "") {

        addParticipation($gestion_sportive, [
            "id_match"   => $id_match,
            "id_joueur"  => $id_joueur,
            "id_poste"   => $id_poste,
            "role"       => "TITULAIRE",
            "evaluation" => null
        ]);

    }
}


// 3️⃣ Ajouter les remplaçants
foreach ($remplacants as $id_joueur) {
    if ($id_joueur != "") {

        addParticipation($gestion_sportive, [
            "id_match"   => $id_match,
            "id_joueur"  => $id_joueur,
            "id_poste"   => null,         // remplaçant → pas de poste attribué
            "role"       => "REMPLACANT",
            "evaluation" => null
        ]);

    }
}


// 4️⃣ Marquer le match comme "PREPARE"
setMatchPrepared($gestion_sportive, $id_match);


// 5️⃣ Redirection
header("Location: ../matchs/modifier_match.php?id_match=$id_match&saved=1");
exit;

?>
