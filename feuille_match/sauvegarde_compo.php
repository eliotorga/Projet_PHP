<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_participation.php";

$id_match = intval($_POST["id_match"] ?? 0);
$titulaires = $_POST["titulaire"] ?? [];
$remplacants = $_POST["remplacants"] ?? [];

/* ================= VALIDATION SERVEUR ================= */
$nbJoueurs = 0;
$hasGK = false;
$assignedPlayers = []; // Pour éviter les doublons

// Validation des titulaires
foreach ($titulaires as $poste => $id_joueur) {
    if (!empty($id_joueur)) {
        if (in_array($id_joueur, $assignedPlayers)) {
            die("<div class='error-message'>Erreur : Le joueur (ID: $id_joueur) est assigné à plusieurs postes.<br><a href='composition.php?id_match=$id_match'>Retour</a></div>");
        }
        $assignedPlayers[] = $id_joueur;
        $nbJoueurs++;
        // ID 1 est le Gardien (supposition basée sur composition.php)
        if ($poste == 1) {
            $hasGK = true;
        }
    }
}

// Validation des remplaçants (filtrer ceux qui sont déjà titulaires)
$remplacantsClean = [];
foreach ($remplacants as $id_joueur) {
    if (!empty($id_joueur) && !in_array($id_joueur, $assignedPlayers)) {
        if (!in_array($id_joueur, $remplacantsClean)) {
            $remplacantsClean[] = $id_joueur;
        }
    }
}

// Règle : 11 joueurs titulaires (ou ajuster selon besoin)
// On peut être souple et accepter moins de 11 joueurs si c'est du foot à 7/8, 
// mais ici on garde la logique précédente si elle existait.
// Le code précédent vérifiait $nbJoueurs < 11.
if ($nbJoueurs < 11) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Composition enregistrée</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/feuille_match.css">
        <link rel="stylesheet" href="../assets/css/resultats.css">
    </head>
    <body>
        <div class="confirmation-container">
            <h1><i class="fas fa-exclamation-circle error-icon"></i> Composition invalide !</h1>
            
            <p class="confirmation-message">
                La composition du match nécessite au moins 11 joueurs titulaires (et un gardien).
            </p>
            
            <div class="confirmation-actions">
                <a href="composition.php?id_match=<?= $id_match ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour à la composition
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!$hasGK) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Composition enregistrée</title>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="../assets/css/feuille_match.css">
        <link rel="stylesheet" href="../assets/css/resultats.css">
    </head>
    <body>
        <div class="confirmation-container">
            <h1><i class="fas fa-exclamation-circle error-icon"></i> Composition invalide !</h1>
            
            <p class="confirmation-message">
                La composition du match nécessite un gardien.
            </p>
            
            <div class="confirmation-actions">
                <a href="composition.php?id_match=<?= $id_match ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Retour à la composition
                </a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

/* ================= SAUVEGARDE ================= */
// On utilise clearParticipation de db_participation.php
clearParticipation($gestion_sportive, $id_match);

// Enregistrement des titulaires
foreach ($titulaires as $poste => $id_joueur) {
    if (!empty($id_joueur)) {
        // Il faut trouver l'ID du poste à partir du libellé si possible, 
        // ou passer null si la fonction le permet. 
        // addParticipation attend un tableau.
        
        // Récupération de l'ID poste depuis la BDD si nécessaire
        // Mais db_participation.php demande 'id_poste'.
        // Dans le formulaire, on va envoyer l'ID du poste dans la clé du tableau : name="titulaire[id_poste]"
        // Donc $poste SERA l'ID du poste.
        
        addParticipation($gestion_sportive, [
            "id_match" => $id_match,
            "id_joueur" => $id_joueur,
            "id_poste" => intval($poste), // On suppose que la clé est l'ID
            "role" => "TITULAIRE",
            "evaluation" => null
        ]);
    }
}

// Enregistrement des remplaçants
foreach ($remplacantsClean as $id_joueur) {
    addParticipation($gestion_sportive, [
        "id_match" => $id_match,
        "id_joueur" => $id_joueur,
        "id_poste" => null, // Pas de poste spécifique pour un remplaçant
        "role" => "REMPLACANT",
        "evaluation" => null
    ]);
}

// Mise à jour de l'état du match
$stmt = $gestion_sportive->prepare("UPDATE matchs SET etat = 'PREPARE' WHERE id_match = ?");
$stmt->execute([$id_match]);

header("Location: ../matchs/liste_matchs.php");
exit;
