<?php
// Controller: formulaire pour ajouter un nouveau joueur dans la base de donnees
// validation des champs nom, prenom, licence, date de naissance et statut

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/joueur.php";

// Récupération des statuts pour le <select>
$statuts = getAllStatuts($gestion_sportive);

$erreur = "";
$succes = "";

// Soumission du formulaire
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Nettoyage des données reçues
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => trim($_POST["num_licence"] ?? ""),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ? (int)$_POST["taille_cm"] : null,
        "poids_kg"       => $_POST["poids_kg"] ? (float)$_POST["poids_kg"] : null,
        "id_statut"      => $_POST["id_statut"] ? (int)$_POST["id_statut"] : null
    ];

    // Validation
    $errors = [];

    if (empty($data["nom"])) {
        $errors[] = "Le nom est obligatoire";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["nom"])) {
        $errors[] = "Le nom ne doit contenir que des lettres, espaces ou tirets";
    }
    if (empty($data["prenom"])) {
        $errors[] = "Le prénom est obligatoire";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["prenom"])) {
        $errors[] = "Le prénom ne doit contenir que des lettres, espaces ou tirets";
    }
    if (empty($data["num_licence"])) $errors[] = "Le numéro de licence est obligatoire";
    if (empty($data["date_naissance"])) $errors[] = "La date de naissance est obligatoire";
    if ($data["taille_cm"] === null) $errors[] = "La taille est obligatoire";
    if ($data["poids_kg"] === null) $errors[] = "Le poids est obligatoire";
    if (empty($data["id_statut"])) $errors[] = "Le statut est obligatoire";

    if (!empty($data["num_licence"])) {
        // Vérifier l'unicité du numéro de licence
        $stmt = $gestion_sportive->prepare("SELECT COUNT(*) FROM joueur WHERE num_licence = ?");
        $stmt->execute([$data["num_licence"]]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Ce numéro de licence existe déjà";
        }
    }

    if ($data["date_naissance"]) {
        $naissance = new DateTime($data["date_naissance"]);
        $aujourdhui = new DateTime();
        $age = $aujourdhui->diff($naissance)->y;
        if ($age < 6) $errors[] = "Le joueur doit avoir au moins 6 ans";
        if ($age > 60) $errors[] = "Veuillez vérifier la date de naissance";
    }

    if ($data["taille_cm"] && ($data["taille_cm"] < 100 || $data["taille_cm"] > 250)) {
        $errors[] = "La taille doit être entre 100 et 250 cm";
    }

    if ($data["poids_kg"] && ($data["poids_kg"] < 30 || $data["poids_kg"] > 150)) {
        $errors[] = "Le poids doit être entre 30 et 150 kg";
    }

    if (empty($errors)) {
        try {
            insertPlayer($gestion_sportive, $data);
            $_SESSION['success_message'] = "Joueur ajouté avec succès !";
            header("Location: liste_joueurs.php");
            exit;
        } catch (PDOException $e) {
            $erreur = "Erreur lors de l'ajout du joueur : " . $e->getMessage();
        }
    } else {
        $erreur = implode("<br>", $errors);
    }
}

include __DIR__ . "/../includes/header.php";

// Inclure la vue
include __DIR__ . "/../vues/ajouter_joueur_view.php";

include __DIR__ . "/../includes/footer.php";
