<?php
// Controller: permet de modifier les informations d'un joueur existant
// affiche un formulaire avec les donnees actuelles et les statistiques du joueur

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/joueur.php";
require_once __DIR__ . "/../modele/participation.php";

// Vérifier qu'un ID a été envoyé
if (!isset($_GET["id"])) {
    $_SESSION['error_message'] = "ID joueur manquant.";
    header("Location: liste_joueurs.php");
    exit;
}

$id_joueur = intval($_GET["id"]);

// Récupération du joueur à modifier
$joueur = getPlayerById($gestion_sportive, $id_joueur);

if (!$joueur) {
    $_SESSION['error_message'] = "Joueur introuvable.";
    header("Location: liste_joueurs.php");
    exit;
}

// Récupérer la liste des statuts
$statuts = getAllStatuts($gestion_sportive);

// Récupérer les commentaires du joueur
$commentaires = getRecentComments($gestion_sportive, $id_joueur, 5);

// Ajout d'un commentaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_comment") {
    $comment_text = trim($_POST["comment_texte"] ?? "");
    if ($comment_text === "") {
        $_SESSION["comment_error"] = "Le commentaire est obligatoire.";
    } else {
        addComment($gestion_sportive, $id_joueur, $comment_text);
        $_SESSION["comment_success"] = "Commentaire ajouté.";
    }
    header("Location: modifier_joueur.php?id=" . $id_joueur);
    exit;
}

// Récupérer les statistiques du joueur
$stats = getPlayerMatchStats($gestion_sportive, $id_joueur);

$error = "";
$success = "";

// Formulaire soumis ?
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST["action"])) {
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => strtoupper(trim($_POST["num_licence"] ?? "")),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ? intval($_POST["taille_cm"]) : null,
        "poids_kg"       => $_POST["poids_kg"] ? floatval($_POST["poids_kg"]) : null,
        "id_statut"      => $_POST["id_statut"] ? intval($_POST["id_statut"]) : null
    ];

    // Validation
    $errors = [];

    if (empty($data["nom"])) {
        $errors[] = "Le nom est requis.";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["nom"])) {
        $errors[] = "Le nom ne doit contenir que des lettres, espaces ou tirets.";
    }
    if (empty($data["prenom"])) {
        $errors[] = "Le prénom est requis.";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["prenom"])) {
        $errors[] = "Le prénom ne doit contenir que des lettres, espaces ou tirets.";
    }
    if (empty($data["num_licence"])) $errors[] = "Le numéro de licence est requis.";
    if (empty($data["date_naissance"])) $errors[] = "La date de naissance est requise.";
    if ($data["taille_cm"] === null) $errors[] = "La taille est requise.";
    if ($data["poids_kg"] === null) $errors[] = "Le poids est requis.";
    if (empty($data["id_statut"])) $errors[] = "Le statut est requis.";

    if (!empty($data["num_licence"]) && !preg_match('/^LIC[0-9]{3}$/', $data["num_licence"])) {
        $errors[] = "Le numéro de licence doit respecter le format LIC001 (LIC suivi de 3 chiffres).";
    }

    // Validation spécifique
    if (!empty($data["num_licence"]) && $data["num_licence"] !== strtoupper($joueur["num_licence"])) {
        if (isLicenseUsedByOtherPlayer($gestion_sportive, $data["num_licence"], $id_joueur)) {
            $errors[] = "Ce numéro de licence est déjà utilisé par un autre joueur.";
        }
    }

    if ($data["taille_cm"] !== null && ($data["taille_cm"] < 140 || $data["taille_cm"] > 220)) {
        $errors[] = "La taille doit être comprise entre 140 et 220 cm.";
    }

    if ($data["poids_kg"] !== null && ($data["poids_kg"] < 40 || $data["poids_kg"] > 120)) {
        $errors[] = "Le poids doit être compris entre 40 et 120 kg.";
    }

    if ($data["date_naissance"]) {
        $date_naissance = DateTime::createFromFormat('Y-m-d', $data["date_naissance"]);
        $min_date = (new DateTime())->modify('-50 years');
        $max_date = (new DateTime())->modify('+5 years');

        if ($date_naissance === false || $date_naissance < $min_date || $date_naissance > $max_date) {
            $errors[] = "La date de naissance doit être comprise entre " .
                       $min_date->format('d/m/Y') . " et " . $max_date->format('d/m/Y');
        }
    }

    if (empty($errors)) {
        try {
            updatePlayer($gestion_sportive, $id_joueur, $data);
            $_SESSION['success_message'] = "Joueur modifié avec succès !";
            header("Location: liste_joueurs.php");
            exit;
        } catch (Exception $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include __DIR__ . "/../includes/header.php";

// Inclure la vue
include __DIR__ . "/../vues/modifier_joueur_view.php";

include __DIR__ . "/../includes/footer.php";
