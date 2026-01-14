<?php
// Controller: permet de planifier un nouveau match dans le calendrier
// formulaire avec validation de date, adversaire, lieu et adresse

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../modele/match.php";

$error = "";
$success = "";

// Formulaire envoyé ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "date_heure" => $_POST["date_heure"] ?? null,
        "adversaire" => trim($_POST["adversaire"] ?? ""),
        "lieu"       => $_POST["lieu"] ?? "",
        "adresse"    => trim($_POST["adresse"] ?? "")
    ];

    // Validation avancée
    $errors = [];

    if (empty($data["date_heure"])) {
        $errors[] = "La date et l'heure sont obligatoires";
    } else {
        $matchDateTime = new DateTime($data["date_heure"]);
        $now = new DateTime();
        $minDate = new DateTime('-1 year');
        $maxDate = new DateTime('+2 years');

        if ($matchDateTime < $minDate) {
            $errors[] = "La date ne peut pas être antérieure à il y a un an";
        }
        if ($matchDateTime > $maxDate) {
            $errors[] = "La date ne peut pas dépasser deux ans dans le futur";
        }
        if ($matchDateTime < $now) {
            $errors[] = "La date du match doit être dans le futur";
        }
    }

    if (empty($data["adversaire"])) {
        $errors[] = "L'adversaire est obligatoire";
    } elseif (strlen($data["adversaire"]) < 2) {
        $errors[] = "Le nom de l'adversaire doit contenir au moins 2 caractères";
    } elseif (strlen($data["adversaire"]) > 100) {
        $errors[] = "Le nom de l'adversaire ne peut pas dépasser 100 caractères";
    }

    if (empty($data["lieu"]) || !in_array($data["lieu"], ['DOMICILE', 'EXTERIEUR'])) {
        $errors[] = "Veuillez sélectionner un lieu valide";
    }

    if ($data["lieu"] === 'EXTERIEUR' && empty($data["adresse"])) {
        $errors[] = "L'adresse est obligatoire pour les matchs à l'extérieur";
    } elseif (!empty($data["adresse"]) && strlen($data["adresse"]) > 255) {
        $errors[] = "L'adresse ne peut pas dépasser 255 caractères";
    }

    // Vérifier les conflits de dates
    if (empty($errors)) {
        $stmt = $gestion_sportive->prepare("
            SELECT COUNT(*) FROM matchs
            WHERE DATE(date_heure) = DATE(?) AND etat != 'JOUE'
        ");
        $stmt->execute([$data["date_heure"]]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Un match est déjà programmé à cette date";
        }
    }

    if (empty($errors)) {
        try {
            insertMatch($gestion_sportive, $data);
            $_SESSION['success_message'] = "Match ajouté avec succès !";
            header("Location: liste_matchs.php");
            exit;
        } catch (PDOException $e) {
            $error = "Erreur lors de l'ajout du match : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Récupérer les matchs à venir pour l'affichage
$stmt = $gestion_sportive->prepare("
    SELECT date_heure, adversaire, lieu
    FROM matchs WHERE date_heure > NOW()
    ORDER BY date_heure ASC LIMIT 10
");
$stmt->execute();
$upcoming_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . "/../includes/header.php";

// Inclure la vue
include __DIR__ . "/../vues/ajouter_match_view.php";

include __DIR__ . "/../includes/footer.php";
