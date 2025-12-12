<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =============================
   VÉRIFICATION MATCH
============================= */
if (!isset($_GET["id_match"])) {
    header("Location: matchs.php?error=no_match");
    exit();
}

$id_match = intval($_GET["id_match"]);

/* =============================
   INFOS MATCH
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT *
    FROM matchs
    WHERE id_match = ?
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("<div class='error-container'><h2>⚽ Match introuvable</h2><p>Le match sélectionné n'existe pas.</p></div>");
}

/* =============================
   VÉRIFIER SI LE MATCH EST DÉJÀ PRÉPARÉ
============================= */
if ($match['etat'] !== 'A_PREPARER') {
    $message = $match['etat'] === 'PREPARE' ? 
        "Ce match a déjà une composition définie." : 
        "Ce match a déjà été joué.";
    die("<div class='error-container'><h2>⚽ Match non modifiable</h2><p>$message</p></div>");
}

/* =============================
   JOUEURS ACTIFS AVEC LEUR NUMÉRO DE LICENCE
============================= */
$joueurs = $gestion_sportive->query("
    SELECT j.id_joueur, j.nom, j.prenom, j.num_licence, s.code AS statut
    FROM joueur j
    JOIN statut s ON s.id_statut = j.id_statut
    WHERE s.code = 'ACT'
    ORDER BY j.nom, j.prenom
")->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   POSTES AVEC ORDRE D'AFFICHAGE
============================= */
$postes = $gestion_sportive->query("
    SELECT * FROM poste ORDER BY id_poste ASC
")->fetchAll(PDO::FETCH_ASSOC);

/* =============================
   VÉRIFIER LES PARTICIPATIONS EXISTANTES
============================= */
$stmt = $gestion_sportive->prepare("
    SELECT p.id_joueur, p.role, po.libelle AS poste
    FROM participation p
    JOIN poste po ON po.id_poste = p.id_poste
    WHERE p.id_match = ?
");
$stmt->execute([$id_match]);
$participations_existantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour JavaScript
$titulaires_existants = [];
$remplacants_existants = [];
foreach ($participations_existantes as $participation) {
    if ($participation['role'] === 'TITULAIRE') {
        $titulaires_existants[] = $participation['id_joueur'];
    } else {
        $remplacants_existants[] = $participation['id_joueur'];
    }
}

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composition - <?= htmlspecialchars($match["adversaire"]) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
    /* =============================
       VARIABLES & RESET
    ============================= */
    :root {
        --primary: #1e7a3c;
        --primary-dark: #145c2f;
        --secondary: #2ecc71;
        --accent: #f39c12;
        --danger: #e74c3c;
        --light: #ecf0f1;
        --dark: #2c3e50;
        --gray: #7f8c8d;
        --shadow: 0 10px 30px rgba(0,0,0,0.3);
        --radius: 16px;
        --transition: all 0.3s ease;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #0c2918 0%, #1a3a26 100%);
        color: white;
        min-height: 100vh;
        padding-bottom: 50px;
    }

    .error-container {
        max-width: 600px;
        margin: 100px auto;
        text-align: center;
        background: rgba(231, 76, 60, 0.1);
        border: 2px solid var(--danger);
        border-radius: var(--radius);
        padding: 40px;
    }

    /* =============================
       LAYOUT PRINCIPAL
    ============================= */
    .page-container {
        max-width: 1600px;
        margin: 0 auto;
        padding: 20px;
    }

    .header-match {
        background: linear-gradient(90deg, var(--primary), var(--primary-dark));
        border-radius: var(--radius);
        padding: 25px 30px;
        margin-bottom: 30px;
        box-shadow: var(--shadow);
        position: relative;
        overflow: hidden;
    }

    .header-match::before {
        content: "";
        position: absolute;
        top: -50%;
        right: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
        background-size: 30px 30px;
        opacity: 0.2;
        z-index: 0;
    }

    .header-match h1 {
        font-size: 2.2rem;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 15px;
        position: relative;
        z-index: 1;
    }

    .match-info {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .match-info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.1rem;
    }

    .match-info-item i {
        color: var(--secondary);
    }

    /* =============================
       GRID PRINCIPALE
    ============================= */
    .main-grid {
        display: grid;
        grid-template-columns: 350px 1fr 350px;
        gap: 25px;
        margin-bottom: 30px;
    }

    @media (max-width: 1400px) {
        .main-grid {
            grid-template-columns: 1fr;
        }
    }

    /* =============================
       PANNEAUX
    ============================= */
    .panel {
        background: rgba(30, 40, 35, 0.85);
        backdrop-filter: blur(10px);
        border-radius: var(--radius);
        padding: 25px;
        box-shadow: var(--shadow);
        border: 1px solid rgba(255,255,255,0.1);
        transition: var(--transition);
        height: fit-content;
    }

    .panel:hover {
        transform: translateY(-5px);
        border-color: var(--secondary);
    }

    .panel-title {
        font-size: 1.4rem;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid var(--secondary);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    /* =============================
       LISTE JOUEURS
    ============================= */
    .players-search {
        margin-bottom: 20px;
    }

    .search-input {
        width: 100%;
        padding: 12px 20px;
        border-radius: 50px;
        border: 2px solid rgba(255,255,255,0.2);
        background: rgba(0,0,0,0.3);
        color: white;
        font-size: 1rem;
        transition: var(--transition);
    }

    .search-input:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 3px rgba(46, 204, 113, 0.3);
    }

    .players-list {
        max-height: 650px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .players-list::-webkit-scrollbar {
        width: 8px;
    }

    .players-list::-webkit-scrollbar-track {
        background: rgba(0,0,0,0.2);
        border-radius: 10px;
    }

    .players-list::-webkit-scrollbar-thumb {
        background: var(--secondary);
        border-radius: 10px;
    }

    .player-card {
        background: linear-gradient(90deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 12px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
        border: 1px solid transparent;
    }

    .player-card:hover {
        background: rgba(46, 204, 113, 0.15);
        border-color: var(--secondary);
        transform: translateX(5px);
    }

    .player-info {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }

    .player-name {
        font-weight: 600;
        font-size: 1rem;
    }

    .player-license {
        font-size: 0.85rem;
        color: rgba(255,255,255,0.6);
    }

    .player-actions {
        display: flex;
        gap: 8px;
    }

    .btn-add {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        border: none;
        width: 36px;
        height: 36px;
        border-radius: 50%;
        color: white;
        cursor: pointer;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: var(--transition);
    }

    .btn-add:hover {
        transform: scale(1.1);
        box-shadow: 0 0 15px rgba(46, 204, 113, 0.5);
    }

    .btn-add:disabled {
        background: var(--gray);
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    /* =============================
       TERRAIN DE FOOT
    ============================= */
    .pitch-container {
        position: relative;
    }

    .pitch {
        background: linear-gradient(135deg, #1a7a3f 0%, #0f5a2f 100%);
        border-radius: var(--radius);
        min-height: 700px;
        padding: 40px 30px;
        border: 4px solid rgba(255,255,255,0.2);
        box-shadow: inset 0 0 50px rgba(0,0,0,0.5);
        position: relative;
        overflow: hidden;
    }

    /* Lignes du terrain */
    .pitch::before {
        content: "";
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 4px;
        background: rgba(255,255,255,0.3);
        transform: translateY(-50%);
    }

    .pitch::after {
        content: "";
        position: absolute;
        top: 50%;
        left: 50%;
        width: 150px;
        height: 150px;
        border: 4px solid rgba(255,255,255,0.3);
        border-radius: 50%;
        transform: translate(-50%, -50%);
    }

    /* Lignes de postes */
    .formation-line {
        display: flex;
        justify-content: center;
        gap: 25px;
        margin-bottom: 40px;
        position: relative;
        z-index: 1;
    }

    .position-slot {
        width: 180px;
        min-height: 100px;
        background: rgba(0,0,0,0.4);
        border-radius: 15px;
        padding: 15px;
        text-align: center;
        border: 2px dashed rgba(255,255,255,0.2);
        transition: var(--transition);
        cursor: pointer;
        position: relative;
    }

    .position-slot:hover {
        border-color: var(--accent);
        background: rgba(0,0,0,0.5);
    }

    .position-slot.filled {
        border: 2px solid var(--secondary);
        background: rgba(46, 204, 113, 0.1);
    }

    .position-title {
        font-size: 0.9rem;
        color: rgba(255,255,255,0.7);
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .player-in-slot {
        font-weight: 600;
        font-size: 1rem;
        margin: 5px 0;
    }

    .player-license-slot {
        background: var(--primary-dark);
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.8);
        display: block;
        margin-top: 5px;
    }

    .btn-remove {
        background: var(--danger);
        border: none;
        color: white;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 0.9rem;
        margin-top: 8px;
        transition: var(--transition);
    }

    .btn-remove:hover {
        transform: scale(1.1);
    }

    .empty-slot {
        color: rgba(255,255,255,0.5);
        font-style: italic;
        font-size: 0.9rem;
        padding: 10px 0;
    }

    /* =============================
       BANC DE REMPLAÇANTS
    ============================= */
    .bench-container {
        position: relative;
    }

    .bench-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .bench-count {
        background: var(--accent);
        padding: 5px 12px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: bold;
    }

    .bench-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        max-height: 650px;
        overflow-y: auto;
        padding-right: 10px;
    }

    .bench-item {
        background: linear-gradient(90deg, rgba(255,255,255,0.05), rgba(255,255,255,0.02));
        border-radius: 12px;
        padding: 15px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: var(--transition);
        border-left: 4px solid var(--accent);
    }

    .bench-item:hover {
        background: rgba(243, 156, 18, 0.1);
        transform: translateX(5px);
    }

    .bench-player-info {
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .btn-move-to-pitch {
        background: var(--secondary);
        border: none;
        color: white;
        padding: 6px 12px;
        border-radius: 8px;
        cursor: pointer;
        font-size: 0.9rem;
        transition: var(--transition);
    }

    .btn-move-to-pitch:hover {
        background: #27ae60;
    }

    /* =============================
       STATISTIQUES & ACTIONS
    ============================= */
    .stats-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: rgba(30, 40, 35, 0.85);
        border-radius: var(--radius);
        padding: 20px;
        text-align: center;
        border-top: 4px solid var(--secondary);
    }

    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--secondary);
        margin: 10px 0;
    }

    .stat-label {
        color: rgba(255,255,255,0.7);
        font-size: 0.9rem;
    }

    .actions-bar {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 40px;
        flex-wrap: wrap;
    }

    .btn-action {
        padding: 16px 32px;
        font-size: 1.1rem;
        border-radius: 50px;
        border: none;
        cursor: pointer;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: var(--transition);
        min-width: 220px;
        justify-content: center;
    }

    .btn-save {
        background: linear-gradient(135deg, var(--secondary), #27ae60);
        color: white;
    }

    .btn-save:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 25px rgba(46, 204, 113, 0.4);
    }

    .btn-reset {
        background: rgba(255,255,255,0.1);
        color: white;
        border: 2px solid rgba(255,255,255,0.3);
    }

    .btn-reset:hover {
        background: rgba(255,255,255,0.2);
        transform: translateY(-5px);
    }

    /* =============================
       ANIMATIONS & NOTIFICATIONS
    ============================= */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .player-card, .position-slot, .bench-item, .stat-card {
        animation: fadeIn 0.5s ease forwards;
    }

    .dragging {
        opacity: 0.5;
        transform: scale(0.95);
    }

    .drop-zone {
        border-color: var(--accent) !important;
        background: rgba(243, 156, 18, 0.2) !important;
    }

    /* =============================
       RESPONSIVE
    ============================= */
    @media (max-width: 1200px) {
        .formation-line {
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .position-slot {
            width: 150px;
        }
    }

    @media (max-width: 768px) {
        .header-match h1 {
            font-size: 1.8rem;
        }
        
        .match-info {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn-action {
            min-width: 100%;
        }
        
        .main-grid {
            gap: 20px;
        }
    }
    </style>
</head>
<body>
    <div class="page-container">
        <!-- EN-TÊTE DU MATCH -->
        <div class="header-match">
            <h1><i class="fas fa-futbol"></i> Composition d'équipe</h1>
            <div class="match-info">
                <div class="match-info-item">
                    <i class="fas fa-flag"></i>
                    <strong>Adversaire :</strong> <?= htmlspecialchars($match["adversaire"]) ?>
                </div>
                <div class="match-info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Date :</strong> <?= date("d/m/Y", strtotime($match["date_heure"])) ?>
                </div>
                <div class="match-info-item">
                    <i class="fas fa-clock"></i>
                    <strong>Heure :</strong> <?= date("H:i", strtotime($match["date_heure"])) ?>
                </div>
                <div class="match-info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"] == "DOMICILE" ? "Domicile" : "Extérieur") ?>
                </div>
                <div class="match-info-item">
                    <i class="fas fa-info-circle"></i>
                    <strong>État :</strong> <?= htmlspecialchars($match["etat"]) ?>
                </div>
            </div>
        </div>

        <!-- STATISTIQUES -->
        <div class="stats-bar">
            <div class="stat-card">
                <div class="stat-label">Joueurs disponibles</div>
                <div class="stat-number" id="available-count"><?= count($joueurs) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Titulaires</div>
                <div class="stat-number" id="titulaires-count"><?= count($titulaires_existants) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Remplaçants</div>
                <div class="stat-number" id="remplacants-count"><?= count($remplacants_existants) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Postes restants</div>
                <div class="stat-number" id="postes-restants"><?= count($postes) - count($titulaires_existants) ?></div>
            </div>
        </div>

        <!-- GRID PRINCIPALE -->
        <form method="POST" action="enregistrer_composition.php" id="compositionForm">
            <input type="hidden" name="id_match" value="<?= $id_match ?>">
            
            <div class="main-grid">
                <!-- COLONNE GAUCHE : JOUEURS DISPONIBLES -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Effectif</h3>
                    
                    <div class="players-search">
                        <input type="text" 
                               class="search-input" 
                               placeholder="Rechercher un joueur..." 
                               id="searchPlayers">
                    </div>
                    
                    <div class="players-list" id="playersList">
                        <?php foreach ($joueurs as $j): 
                            $est_selectionne = in_array($j['id_joueur'], $titulaires_existants) || in_array($j['id_joueur'], $remplacants_existants);
                        ?>
                            <div class="player-card" 
                                 data-id="<?= $j['id_joueur'] ?>"
                                 data-name="<?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>"
                                 data-license="<?= htmlspecialchars($j['num_licence']) ?>"
                                 data-selected="<?= $est_selectionne ? 'true' : 'false' ?>">
                                <div class="player-info">
                                    <div class="player-name">
                                        <?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>
                                    </div>
                                    <div class="player-license">
                                        <?= htmlspecialchars($j['num_licence']) ?>
                                    </div>
                                </div>
                                <div class="player-actions">
                                    <button type="button" 
                                            class="btn-add"
                                            onclick="addToPitch(<?= $j['id_joueur'] ?>)"
                                            <?= $est_selectionne ? 'disabled' : '' ?>
                                            title="<?= $est_selectionne ? 'Déjà sélectionné' : 'Ajouter au terrain' ?>">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn-add"
                                            onclick="addToBench(<?= $j['id_joueur'] ?>)"
                                            <?= $est_selectionne ? 'disabled' : '' ?>
                                            title="<?= $est_selectionne ? 'Déjà sélectionné' : 'Ajouter au banc' ?>">
                                        <i class="fas fa-chair"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLONNE CENTRE : TERRAIN -->
                <div class="panel pitch-container">
                    <h3 class="panel-title"><i class="fas fa-futbol"></i> Composition titulaire</h3>
                    
                    <div class="pitch" id="pitch">
                        <?php 
                        // Formation football classique (1 Gardien, 4 Défenseurs, 4 Milieux, 2 Attaquants)
                        $formation = [
                            ['poste_id' => 1, 'poste_libelle' => 'Gardien'],
                            ['poste_id' => 2, 'poste_libelle' => 'Défenseur central gauche'],
                            ['poste_id' => 2, 'poste_libelle' => 'Défenseur central droit'],
                            ['poste_id' => 2, 'poste_libelle' => 'Latéral gauche'],
                            ['poste_id' => 2, 'poste_libelle' => 'Latéral droit'],
                            ['poste_id' => 3, 'poste_libelle' => 'Milieu défensif'],
                            ['poste_id' => 3, 'poste_libelle' => 'Milieu central gauche'],
                            ['poste_id' => 3, 'poste_libelle' => 'Milieu central droit'],
                            ['poste_id' => 3, 'poste_libelle' => 'Milieu offensif'],
                            ['poste_id' => 4, 'poste_libelle' => 'Attaquant gauche'],
                            ['poste_id' => 4, 'poste_libelle' => 'Attaquant droit']
                        ];
                        
                        // Lignes pour l'affichage
                        $lignes = [
                            [$formation[0]],
                            array_slice($formation, 1, 4),
                            array_slice($formation, 5, 4),
                            array_slice($formation, 9, 2)
                        ];
                        
                        foreach ($lignes as $ligne): 
                        ?>
                            <div class="formation-line" data-ligne="<?= $ligne[0]['poste_libelle'] ?>">
                                <?php foreach ($ligne as $poste): 
                                    // Trouver si ce poste a déjà un joueur assigné
                                    $joueur_assigné = null;
                                    foreach ($participations_existantes as $participation) {
                                        if ($participation['role'] === 'TITULAIRE' && $participation['poste'] === $poste['poste_libelle']) {
                                            $joueur_assigné = $participation;
                                            break;
                                        }
                                    }
                                ?>
                                    <div class="position-slot <?= $joueur_assigné ? 'filled' : '' ?>" 
                                         data-poste-id="<?= $poste['poste_id'] ?>"
                                         data-poste-libelle="<?= htmlspecialchars($poste['poste_libelle']) ?>"
                                         ondrop="dropOnPitch(event)"
                                         ondragover="allowDrop(event)"
                                         ondragenter="highlightDropZone(event)"
                                         ondragleave="unhighlightDropZone(event)">
                                        <div class="position-title"><?= htmlspecialchars($poste['poste_libelle']) ?></div>
                                        <div class="slot-content">
                                            <?php if ($joueur_assigné): 
                                                // Récupérer les infos du joueur
                                                $stmt = $gestion_sportive->prepare("
                                                    SELECT j.id_joueur, j.nom, j.prenom, j.num_licence
                                                    FROM joueur j
                                                    WHERE j.id_joueur = ?
                                                ");
                                                $stmt->execute([$joueur_assigné['id_joueur']]);
                                                $joueur = $stmt->fetch(PDO::FETCH_ASSOC);
                                            ?>
                                                <div class="player-in-slot">
                                                    <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?>
                                                </div>
                                                <div class="player-license-slot">
                                                    <?= htmlspecialchars($joueur['num_licence']) ?>
                                                </div>
                                                <button type="button" class="btn-remove" onclick="removeFromPitch(<?= $joueur['id_joueur'] ?>, '<?= htmlspecialchars($poste['poste_libelle']) ?>')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php else: ?>
                                                <div class="empty-slot">Cliquez ou glissez un joueur</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLONNE DROITE : BANC -->
                <div class="panel bench-container">
                    <div class="bench-title">
                        <h3 class="panel-title"><i class="fas fa-chair"></i> Remplaçants</h3>
                        <span class="bench-count" id="benchCount"><?= count($remplacants_existants) ?></span>
                    </div>
                    
                    <div class="bench-list" id="benchList"
                         ondrop="dropOnBench(event)"
                         ondragover="allowDrop(event)"
                         ondragenter="highlightBench(event)"
                         ondragleave="unhighlightBench(event)">
                        <?php foreach ($remplacants_existants as $id_joueur): 
                            $stmt = $gestion_sportive->prepare("
                                SELECT j.id_joueur, j.nom, j.prenom, j.num_licence
                                FROM joueur j
                                WHERE j.id_joueur = ?
                            ");
                            $stmt->execute([$id_joueur]);
                            $joueur = $stmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                            <div class="bench-item" data-id="<?= $joueur['id_joueur'] ?>">
                                <div class="bench-player-info">
                                    <div><?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></div>
                                    <div style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                                        <?= htmlspecialchars($joueur['num_licence']) ?>
                                    </div>
                                </div>
                                <div class="player-actions">
                                    <button type="button" class="btn-move-to-pitch" onclick="moveToPitch(<?= $joueur['id_joueur'] ?>)">
                                        <i class="fas fa-arrow-up"></i> Terrain
                                    </button>
                                    <button type="button" class="btn-remove" onclick="removeFromBench(<?= $joueur['id_joueur'] ?>)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <input type="hidden" name="remplacants[]" value="<?= $joueur['id_joueur'] ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="margin-top: 20px; padding: 15px; background: rgba(0,0,0,0.2); border-radius: 10px; font-size: 0.9rem; color: rgba(255,255,255,0.7);">
                        <i class="fas fa-info-circle"></i> Glissez les joueurs ici ou utilisez les boutons d'ajout.
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="actions-bar">
                <button type="button" class="btn-action btn-reset" onclick="resetComposition()">
                    <i class="fas fa-redo"></i> Réinitialiser
                </button>
                <button type="submit" class="btn-action btn-save">
                    <i class="fas fa-save"></i> Enregistrer la composition
                </button>
            </div>
        </form>
    </div>

    <script>
    // =============================
    // VARIABLES GLOBALES
    // =============================
    let selectedPlayers = {
        titulaires: {},
        remplacants: <?= json_encode($remplacants_existants) ?>
    };
    
    // Initialiser les titulaires existants
    <?php foreach ($participations_existantes as $participation): ?>
        <?php if ($participation['role'] === 'TITULAIRE'): ?>
            selectedPlayers.titulaires['<?= addslashes($participation['poste']) ?>'] = <?= $participation['id_joueur'] ?>;
        <?php endif; ?>
    <?php endforeach; ?>
    
    // =============================
    // INITIALISATION
    // =============================
    document.addEventListener('DOMContentLoaded', function() {
        initDragAndDrop();
        updateCounters();
        
        // Recherche de joueurs
        document.getElementById('searchPlayers').addEventListener('input', function(e) {
            searchPlayers(e.target.value);
        });
        
        // Configurer le banc comme zone de drop
        const benchList = document.getElementById('benchList');
        benchList.addEventListener('dragover', allowDrop);
        benchList.addEventListener('drop', dropOnBench);
    });
    
    // =============================
    // FONCTIONS DRAG & DROP
    // =============================
    function initDragAndDrop() {
        const playerCards = document.querySelectorAll('.player-card:not([data-selected="true"])');
        playerCards.forEach(card => {
            card.setAttribute('draggable', 'true');
            card.addEventListener('dragstart', handleDragStart);
            card.addEventListener('dragend', handleDragEnd);
        });
    }
    
    function handleDragStart(e) {
        const playerCard = e.target.closest('.player-card');
        if (!playerCard) return;
        
        const playerId = playerCard.dataset.id;
        const playerName = playerCard.dataset.name;
        const playerLicense = playerCard.dataset.license;
        
        e.dataTransfer.setData('text/plain', JSON.stringify({
            id: playerId,
            name: playerName,
            license: playerLicense
        }));
        
        playerCard.classList.add('dragging');
        
        // Créer un effet visuel de drag
        e.dataTransfer.effectAllowed = 'move';
    }
    
    function handleDragEnd(e) {
        const playerCard = e.target.closest('.player-card');
        if (playerCard) {
            playerCard.classList.remove('dragging');
        }
    }
    
    function allowDrop(e) {
        e.preventDefault();
    }
    
    function highlightDropZone(e) {
        e.preventDefault();
        const slot = e.target.closest('.position-slot');
        if (slot) {
            slot.classList.add('drop-zone');
        }
    }
    
    function unhighlightDropZone(e) {
        const slot = e.target.closest('.position-slot');
        if (slot) {
            slot.classList.remove('drop-zone');
        }
    }
    
    function highlightBench(e) {
        e.preventDefault();
        const benchList = e.target.closest('#benchList');
        if (benchList) {
            benchList.style.backgroundColor = 'rgba(243, 156, 18, 0.1)';
        }
    }
    
    function unhighlightBench(e) {
        const benchList = e.target.closest('#benchList');
        if (benchList) {
            benchList.style.backgroundColor = '';
        }
    }
    
    function dropOnPitch(e) {
        e.preventDefault();
        const slot = e.target.closest('.position-slot');
        if (!slot) return;
        
        slot.classList.remove('drop-zone');
        
        try {
            const playerData = JSON.parse(e.dataTransfer.getData('text/plain'));
            assignPlayerToSlot(playerData, slot);
        } catch (error) {
            console.error('Erreur lors du drop:', error);
        }
    }
    
    function dropOnBench(e) {
        e.preventDefault();
        const benchList = e.target.closest('#benchList');
        if (!benchList) return;
        
        benchList.style.backgroundColor = '';
        
        try {
            const playerData = JSON.parse(e.dataTransfer.getData('text/plain'));
            addToBench(playerData.id);
        } catch (error) {
            console.error('Erreur lors du drop sur le banc:', error);
        }
    }
    
    // =============================
    // FONCTIONS DE GESTION
    // =============================
    function assignPlayerToSlot(playerData, slot) {
        const posteLibelle = slot.dataset.posteLibelle;
        const playerId = parseInt(playerData.id);
        
        // Vérifier si le joueur est déjà titulaire à un autre poste
        for (const existingPoste in selectedPlayers.titulaires) {
            if (selectedPlayers.titulaires[existingPoste] == playerId) {
                if (!confirm(`Ce joueur est déjà titulaire (${existingPoste}). Remplacer par ${posteLibelle} ?`)) {
                    return;
                }
                // Retirer l'ancien poste
                removeFromPitch(playerId, existingPoste);
                break;
            }
        }
        
        // Vérifier si le poste est déjà occupé
        if (selectedPlayers.titulaires[posteLibelle]) {
            if (!confirm(`Ce poste est déjà occupé. Remplacer ?`)) {
                return;
            }
            // Retirer l'ancien joueur
            removePlayerFromSelection(selectedPlayers.titulaires[posteLibelle]);
        }
        
        // Vérifier si le joueur est déjà dans les remplaçants
        const benchIndex = selectedPlayers.remplacants.indexOf(playerId);
        if (benchIndex !== -1) {
            selectedPlayers.remplacants.splice(benchIndex, 1);
            updateBenchDisplay();
        }
        
        // Ajouter le joueur au poste
        selectedPlayers.titulaires[posteLibelle] = playerId;
        
        // Mettre à jour l'affichage du slot
        updateSlotDisplay(slot, playerData);
        
        // Mettre à jour les compteurs
        updateCounters();
        updateFormInputs();
        
        // Désactiver le bouton d'ajout du joueur
        disablePlayerButton(playerId);
    }
    
    function updateSlotDisplay(slot, playerData) {
        slot.classList.add('filled');
        slot.innerHTML = `
            <div class="position-title">${slot.dataset.posteLibelle}</div>
            <div class="player-in-slot">
                ${playerData.name}
            </div>
            <div class="player-license-slot">
                ${playerData.license}
            </div>
            <button type="button" class="btn-remove" onclick="removeFromPitch(${playerData.id}, '${slot.dataset.posteLibelle}')">
                <i class="fas fa-times"></i>
            </button>
        `;
    }
    
    function addToPitch(playerId) {
        const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
        if (!playerCard) return;
        
        const playerData = {
            id: playerId,
            name: playerCard.dataset.name,
            license: playerCard.dataset.license
        };
        
        // Trouver le premier slot vide
        const emptySlot = document.querySelector('.position-slot:not(.filled)');
        if (emptySlot) {
            assignPlayerToSlot(playerData, emptySlot);
        } else {
            alert('Tous les postes sont déjà occupés !');
        }
    }
    
    function addToBench(playerId) {
        const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
        if (!playerCard) return;
        
        // Vérifier si le joueur est déjà titulaire
        for (const posteLibelle in selectedPlayers.titulaires) {
            if (selectedPlayers.titulaires[posteLibelle] == playerId) {
                alert('Ce joueur est déjà titulaire ! Retirez-le d\'abord du terrain.');
                return;
            }
        }
        
        // Vérifier si le joueur est déjà sur le banc
        if (selectedPlayers.remplacants.includes(parseInt(playerId))) {
            alert('Ce joueur est déjà sur le banc !');
            return;
        }
        
        // Ajouter au banc
        selectedPlayers.remplacants.push(parseInt(playerId));
        
        updateBenchDisplay();
        updateCounters();
        updateFormInputs();
        disablePlayerButton(playerId);
    }
    
    function updateBenchDisplay() {
        const benchList = document.getElementById('benchList');
        benchList.innerHTML = '';
        
        // Mettre à jour l'affichage avec les données des joueurs
        selectedPlayers.remplacants.forEach(playerId => {
            const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
            if (playerCard) {
                const benchItem = document.createElement('div');
                benchItem.className = 'bench-item';
                benchItem.draggable = true;
                benchItem.dataset.id = playerId;
                benchItem.addEventListener('dragstart', handleDragStart);
                benchItem.addEventListener('dragend', handleDragEnd);
                benchItem.innerHTML = `
                    <div class="bench-player-info">
                        <div>${playerCard.dataset.name}</div>
                        <div style="font-size: 0.85rem; color: rgba(255,255,255,0.6);">
                            ${playerCard.dataset.license}
                        </div>
                    </div>
                    <div class="player-actions">
                        <button type="button" class="btn-move-to-pitch" onclick="moveToPitch(${playerId})">
                            <i class="fas fa-arrow-up"></i> Terrain
                        </button>
                        <button type="button" class="btn-remove" onclick="removeFromBench(${playerId})">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <input type="hidden" name="remplacants[]" value="${playerId}">
                `;
                benchList.appendChild(benchItem);
            }
        });
        
        document.getElementById('benchCount').textContent = selectedPlayers.remplacants.length;
    }
    
    function moveToPitch(playerId) {
        const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
        if (!playerCard) return;
        
        const emptySlot = document.querySelector('.position-slot:not(.filled)');
        
        if (emptySlot) {
            const playerData = {
                id: playerId,
                name: playerCard.dataset.name,
                license: playerCard.dataset.license
            };
            
            // Retirer du banc
            const benchIndex = selectedPlayers.remplacants.indexOf(parseInt(playerId));
            if (benchIndex !== -1) {
                selectedPlayers.remplacants.splice(benchIndex, 1);
                assignPlayerToSlot(playerData, emptySlot);
                updateBenchDisplay();
            }
        } else {
            alert('Tous les postes sont occupés !');
        }
    }
    
    function removeFromPitch(playerId, posteLibelle) {
        if (selectedPlayers.titulaires[posteLibelle] == playerId) {
            delete selectedPlayers.titulaires[posteLibelle];
        }
        
        // Réinitialiser le slot
        const slot = document.querySelector(`.position-slot[data-poste-libelle="${posteLibelle}"]`);
        if (slot) {
            slot.classList.remove('filled');
            slot.innerHTML = `
                <div class="position-title">${slot.dataset.posteLibelle}</div>
                <div class="slot-content">
                    <div class="empty-slot">Cliquez ou glissez un joueur</div>
                </div>
            `;
        }
        
        enablePlayerButton(playerId);
        updateCounters();
        updateFormInputs();
    }
    
    function removeFromBench(playerId) {
        const playerIndex = selectedPlayers.remplacants.indexOf(parseInt(playerId));
        if (playerIndex !== -1) {
            selectedPlayers.remplacants.splice(playerIndex, 1);
            updateBenchDisplay();
            enablePlayerButton(playerId);
            updateCounters();
            updateFormInputs();
        }
    }
    
    function removePlayerFromSelection(playerId) {
        // Retirer des titulaires
        for (const posteLibelle in selectedPlayers.titulaires) {
            if (selectedPlayers.titulaires[posteLibelle] == playerId) {
                delete selectedPlayers.titulaires[posteLibelle];
                const slot = document.querySelector(`.position-slot[data-poste-libelle="${posteLibelle}"]`);
                if (slot) {
                    slot.classList.remove('filled');
                    slot.innerHTML = `
                        <div class="position-title">${slot.dataset.posteLibelle}</div>
                        <div class="slot-content">
                            <div class="empty-slot">Cliquez ou glissez un joueur</div>
                        </div>
                    `;
                }
                break;
            }
        }
        
        // Retirer des remplaçants
        const benchIndex = selectedPlayers.remplacants.indexOf(parseInt(playerId));
        if (benchIndex !== -1) {
            selectedPlayers.remplacants.splice(benchIndex, 1);
            updateBenchDisplay();
        }
        
        enablePlayerButton(playerId);
    }
    
    // =============================
    // FONCTIONS UTILITAIRES
    // =============================
    function disablePlayerButton(playerId) {
        const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
        if (playerCard) {
            const buttons = playerCard.querySelectorAll('.btn-add');
            buttons.forEach(btn => {
                btn.disabled = true;
                btn.style.cursor = 'not-allowed';
            });
            playerCard.dataset.selected = 'true';
        }
    }
    
    function enablePlayerButton(playerId) {
        const playerCard = document.querySelector(`.player-card[data-id="${playerId}"]`);
        if (playerCard) {
            // Vérifier si le joueur est encore sélectionné quelque part
            let isStillSelected = false;
            
            // Vérifier dans les titulaires
            for (const posteLibelle in selectedPlayers.titulaires) {
                if (selectedPlayers.titulaires[posteLibelle] == playerId) {
                    isStillSelected = true;
                    break;
                }
            }
            
            // Vérifier dans les remplaçants
            if (!isStillSelected) {
                isStillSelected = selectedPlayers.remplacants.includes(parseInt(playerId));
            }
            
            // Activer les boutons seulement si le joueur n'est plus sélectionné
            if (!isStillSelected) {
                const buttons = playerCard.querySelectorAll('.btn-add');
                buttons.forEach(btn => {
                    btn.disabled = false;
                    btn.style.cursor = 'pointer';
                });
                playerCard.dataset.selected = 'false';
                playerCard.setAttribute('draggable', 'true');
            }
        }
    }
    
    function searchPlayers(query) {
        const players = document.querySelectorAll('.player-card');
        const searchTerm = query.toLowerCase();
        
        players.forEach(player => {
            const playerName = player.dataset.name.toLowerCase();
            const playerLicense = player.dataset.license.toLowerCase();
            if (playerName.includes(searchTerm) || playerLicense.includes(searchTerm)) {
                player.style.display = 'flex';
            } else {
                player.style.display = 'none';
            }
        });
    }
    
    function updateCounters() {
        const titulairesCount = Object.keys(selectedPlayers.titulaires).length;
        const remplacantsCount = selectedPlayers.remplacants.length;
        const postesTotal = document.querySelectorAll('.position-slot').length;
        const postesRestants = postesTotal - titulairesCount;
        
        document.getElementById('titulaires-count').textContent = titulairesCount;
        document.getElementById('remplacants-count').textContent = remplacantsCount;
        document.getElementById('postes-restants').textContent = postesRestants;
    }
    
    function updateFormInputs() {
        const form = document.getElementById('compositionForm');
        
        // Supprimer les anciens inputs pour les titulaires
        const oldInputs = form.querySelectorAll('input[name^="titulaires"]');
        oldInputs.forEach(input => input.remove());
        
        // Ajouter les nouveaux inputs pour les titulaires
        for (const posteLibelle in selectedPlayers.titulaires) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `titulaires[${posteLibelle}]`;
            input.value = selectedPlayers.titulaires[posteLibelle];
            form.appendChild(input);
        }
    }
    
    function resetComposition() {
        if (!confirm('Êtes-vous sûr de vouloir réinitialiser toute la composition ?')) {
            return;
        }
        
        selectedPlayers = {
            titulaires: {},
            remplacants: []
        };
        
        // Réinitialiser les slots
        document.querySelectorAll('.position-slot').forEach(slot => {
            slot.classList.remove('filled');
            slot.innerHTML = `
                <div class="position-title">${slot.dataset.posteLibelle}</div>
                <div class="slot-content">
                    <div class="empty-slot">Cliquez ou glissez un joueur</div>
                </div>
            `;
        });
        
        // Réinitialiser le banc
        updateBenchDisplay();
        
        // Réactiver tous les boutons
        document.querySelectorAll('.player-card').forEach(card => {
            const buttons = card.querySelectorAll('.btn-add');
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.style.cursor = 'pointer';
            });
            card.dataset.selected = 'false';
            card.setAttribute('draggable', 'true');
        });
        
        updateCounters();
        updateFormInputs();
    }
    
    // =============================
    // GESTION DU FORMULAIRE
    // =============================
    document.getElementById('compositionForm').addEventListener('submit', function(e) {
        const titulairesCount = Object.keys(selectedPlayers.titulaires).length;
        const postesTotal = document.querySelectorAll('.position-slot').length;
        
        if (titulairesCount < postesTotal) {
            e.preventDefault();
            if (confirm(`Il reste ${postesTotal - titulairesCount} poste(s) non attribué(s). Voulez-vous quand même enregistrer ?`)) {
                this.submit();
            }
        }
    });
    </script>
</body>
</html>
<?php include "../includes/footer.php"; ?>