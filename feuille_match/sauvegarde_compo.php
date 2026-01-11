<?php
// traite et enregistre la composition d'equipe selectionnee
// valide les titulaires et remplacants puis met a jour la base de donnees

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_participation.php";

$id_match = intval($_POST["id_match"] ?? 0);
$titulaires = $_POST["titulaire"] ?? [];
$remplacants = $_POST["remplacant_poste"] ?? [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$stmt = $gestion_sportive->prepare("SELECT etat, date_heure FROM matchs WHERE id_match = ?");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);
$etat_match = $match['etat'] ?? null;

if (!$etat_match) {
    die("<div class='error-message'>Erreur : Match introuvable.<br><a href='../matchs/liste_matchs.php'>Retour</a></div>");
}

$nowDt = new DateTimeImmutable('now');
$dateMatchObj = new DateTimeImmutable($match['date_heure']);
if ($dateMatchObj <= $nowDt) {
    die("<div class='error-message'>Erreur : ce match est déjà passé, la composition ne peut plus être modifiée.<br><a href='../matchs/liste_matchs.php'>Retour</a></div>");
}

$stmt = $gestion_sportive->prepare("SELECT COUNT(*) FROM participation WHERE id_match = ? AND evaluation IS NOT NULL");
$stmt->execute([$id_match]);
$nb_eval = (int)$stmt->fetchColumn();

if ($nb_eval > 0) {
    die("<div class='error-message'>Erreur : Des évaluations existent déjà pour ce match, la composition ne peut plus être modifiée.<br><a href='../matchs/liste_matchs.php'>Retour</a></div>");
}

/* ================= VALIDATION SERVEUR ================= */
$nbJoueurs = 0;
$hasGK = false;
$assignedPlayers = []; // Pour éviter les doublons
$titulaires_to_save = []; // Pour stocker les titulaires traités avec les bons postes

// Validation des titulaires
foreach ($titulaires as $poste_key => $id_joueur) {
    if (!empty($id_joueur)) {
        if (in_array($id_joueur, $assignedPlayers)) {
            $_SESSION['composition_draft'][$id_match] = [
                'error' => "Erreur : un joueur est assigné à plusieurs postes.",
                'titulaires' => $titulaires,
                'remplacants' => $remplacants
            ];
            header("Location: composition.php?id_match=$id_match");
            exit;
        }
        $assignedPlayers[] = $id_joueur;
        $nbJoueurs++;
        
        // Extraire le nom du poste depuis la clé (format: "Gardien_0", "Défenseur central gauche_1", etc.)
        $poste_libelle = $poste_key;
        if (is_string($poste_key) && strpos($poste_key, '_') !== false) {
            $poste_libelle = explode('_', $poste_key, 2)[0];
        }
        
        // Vérifier si c'est un gardien
        if (stripos($poste_libelle, 'Gardien') !== false) {
            $hasGK = true;
        }
        
        // Récupérer l'ID du poste depuis la base de données
        $stmt = $gestion_sportive->prepare("SELECT id_poste FROM poste WHERE libelle LIKE ? LIMIT 1");
        $stmt->execute(["%" . $poste_libelle . "%"]);
        $poste_id = $stmt->fetchColumn();
        
        if (!$poste_id) {
            // Si le poste n'existe pas, utiliser une valeur par défaut selon le type
            if (stripos($poste_libelle, 'Gardien') !== false) {
                $poste_id = 1;
            } elseif (stripos($poste_libelle, 'Défenseur') !== false) {
                $poste_id = 2;
            } elseif (stripos($poste_libelle, 'Milieu') !== false) {
                $poste_id = 3;
            } elseif (stripos($poste_libelle, 'Attaquant') !== false) {
                $poste_id = 4;
            } else {
                $poste_id = 2; // Défaut : défenseur
            }
        }
        
        // Stocker pour la sauvegarde
        $titulaires_to_save[] = [
            'id_joueur' => $id_joueur,
            'id_poste' => $poste_id,
            'poste_libelle' => $poste_libelle
        ];
    }
}

// Validation des remplaçants par poste
$remplacants_to_save = [];
foreach ($remplacants as $slot_key => $id_joueur) {
    if (!empty($id_joueur)) {
        if (in_array($id_joueur, $assignedPlayers, true)) {
            $_SESSION['composition_draft'][$id_match] = [
                'error' => "Erreur : un joueur est assigné à plusieurs postes.",
                'titulaires' => $titulaires,
                'remplacants' => $remplacants
            ];
            header("Location: composition.php?id_match=$id_match");
            exit;
        }
        $assignedPlayers[] = $id_joueur;

        $poste_id = null;
        if (is_string($slot_key) && preg_match('/^poste_(\d+)_/', $slot_key, $matches)) {
            $poste_id = (int)$matches[1];
        }

        if (!$poste_id) {
            $_SESSION['composition_draft'][$id_match] = [
                'error' => "Erreur : poste de remplaçant invalide.",
                'titulaires' => $titulaires,
                'remplacants' => $remplacants
            ];
            header("Location: composition.php?id_match=$id_match");
            exit;
        }

        $remplacants_to_save[] = [
            'id_joueur' => (int)$id_joueur,
            'id_poste' => $poste_id
        ];
    }
}

// Règle : 11 joueurs titulaires (ou ajuster selon besoin)
// On peut être souple et accepter moins de 11 joueurs si c'est du foot à 7/8, 
// mais ici on garde la logique précédente si elle existait.
// Le code précédent vérifiait $nbJoueurs < 11.
if ($nbJoueurs < 11) {
    $_SESSION['composition_draft'][$id_match] = [
        'error' => "La composition du match nécessite au moins 11 joueurs titulaires.",
        'titulaires' => $titulaires,
        'remplacants' => $remplacants
    ];
    header("Location: composition.php?id_match=$id_match");
    exit;
}

if (!$hasGK) {
    $_SESSION['composition_draft'][$id_match] = [
        'error' => "La composition du match nécessite un gardien.",
        'titulaires' => $titulaires,
        'remplacants' => $remplacants
    ];
    header("Location: composition.php?id_match=$id_match");
    exit;
}

/* ================= SAUVEGARDE ================= */
// On utilise clearParticipation de db_participation.php
clearParticipation($gestion_sportive, $id_match);

// Enregistrement des titulaires
foreach ($titulaires_to_save as $titulaire) {
    addParticipation($gestion_sportive, [
        "id_match" => $id_match,
        "id_joueur" => $titulaire['id_joueur'],
        "id_poste" => $titulaire['id_poste'],
        "role" => "TITULAIRE",
        "evaluation" => null
    ]);
}

// Enregistrement des remplaçants
foreach ($remplacants_to_save as $remplacant) {
    addParticipation($gestion_sportive, [
        "id_match" => $id_match,
        "id_joueur" => $remplacant['id_joueur'],
        "id_poste" => $remplacant['id_poste'],
        "role" => "REMPLACANT",
        "evaluation" => null
    ]);
}

// Mise à jour de l'état du match
if ($etat_match !== 'JOUE') {
    $stmt = $gestion_sportive->prepare("UPDATE matchs SET etat = 'PREPARE' WHERE id_match = ?");
    $stmt->execute([$id_match]);
}

header("Location: ../matchs/liste_matchs.php");
exit;
