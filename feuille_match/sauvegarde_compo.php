<?php
require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_participation.php";

$id_match = intval($_POST["id_match"] ?? 0);
$titulaires = $_POST["titulaire"] ?? [];
$remplacants = $_POST["remplacants"] ?? [];

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
if ($etat_match === 'JOUE' && $dateMatchObj <= $nowDt) {
    die("<div class='error-message'>Erreur : Ce match est déjà joué, la composition ne peut plus être modifiée.<br><a href='voir_composition.php?id_match=$id_match'>Voir la composition</a></div>");
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

// Validation des titulaires
foreach ($titulaires as $poste => $id_joueur) {
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
        $poste_id = $poste;
        if (is_string($poste) && strpos($poste, '_') !== false) {
            $poste_id = explode('_', $poste, 2)[0];
        }
        if ((int)$poste_id === 1) {
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
$stmt = $gestion_sportive->prepare("SELECT id_poste FROM poste WHERE code = 'REM' LIMIT 1");
$stmt->execute();
$remplacant_poste_id = (int)$stmt->fetchColumn();
if ($remplacant_poste_id <= 0) {
    $stmt = $gestion_sportive->prepare("INSERT INTO poste (code, libelle) VALUES ('REM', 'Remplaçant')");
    $stmt->execute();
    $stmt = $gestion_sportive->prepare("SELECT id_poste FROM poste WHERE code = 'REM' LIMIT 1");
    $stmt->execute();
    $remplacant_poste_id = (int)$stmt->fetchColumn();
}
foreach ($remplacantsClean as $id_joueur) {
    addParticipation($gestion_sportive, [
        "id_match" => $id_match,
        "id_joueur" => $id_joueur,
        "id_poste" => $remplacant_poste_id,
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
