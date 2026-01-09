<?php
// formulaire pour composer l'equipe avant un match
// selection des 11 titulaires par poste et des remplacants

require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* =============================
   VÉRIFICATION MATCH
============================= */
if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php?error=no_match");
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

$stmt = $gestion_sportive->prepare("SELECT 1 FROM participation WHERE id_match = ? AND evaluation IS NOT NULL LIMIT 1");
$stmt->execute([$id_match]);
$has_evaluations = (bool)$stmt->fetchColumn();

$nowDt = new DateTimeImmutable('now');
$dateMatchObj = new DateTimeImmutable($match['date_heure']);
if ($match['etat'] === 'JOUE' && $dateMatchObj <= $nowDt) {
    header("Location: voir_composition.php?id_match=$id_match");
    exit();
}

/* =============================
   VÉRIFIER SI LE MATCH EST DÉJÀ JOUÉ AVEC ÉVALUATIONS
============================= */
if ($match['etat'] === 'JOUE' && $dateMatchObj <= $nowDt && $has_evaluations) {
    // Si le match est joué, dans le passé ET a des évaluations, on ne peut plus modifier
    $is_played = true;
    $message = "Ce match a déjà été joué avec évaluations et ne peut plus être modifié.";
    
    // Charger le header avec le CSS des erreurs
    $pageTitle = "Match non modifiable";
    $extraCSS = "<link rel='stylesheet' href='../assets/css/error_pages.css'>
    <style>
        .match-details {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid var(--primary);
        }
        .match-details h3 {
            margin-top: 0;
            color: var(--dark);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .match-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray);
        }
        .info-item i {
            color: var(--primary);
            width: 20px;
            text-align: center;
        }
        .score-display {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            margin: 15px 0;
            color: var(--dark);
        }
        .team-names {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .team {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .team-logo {
            width: 30px;
            height: 30px;
            background: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
        }
    </style>";
    
    include "../includes/header.php";
    ?>
    
    <div class="match-details">
        <h3><i class="fas fa-exclamation-triangle"></i> Match non modifiable</h3>
        <div class="match-info-grid">
            <div class="info-item">
                <i class="fas fa-futbol"></i>
                <span><strong>Adversaire :</strong> <?= htmlspecialchars($match["adversaire"]) ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-calendar-alt"></i>
                <span><strong>Date :</strong> <?= date("d/m/Y", strtotime($match["date_heure"])) ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-clock"></i>
                <span><strong>Heure :</strong> <?= date("H:i", strtotime($match["date_heure"])) ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-map-marker-alt"></i>
                <span><strong>Lieu :</strong> <?= htmlspecialchars($match["lieu"] == "DOMICILE" ? "Domicile" : "Extérieur") ?></span>
            </div>
            <div class="info-item">
                <i class="fas fa-info-circle"></i>
                <span><strong>État :</strong> <?= htmlspecialchars($match["etat"]) ?></span>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #fff3cd; border-radius: 8px; border-left: 4px solid #ffc107;">
            <p style="margin: 0; color: #856404;"><strong><?= $message ?></strong></p>
            <p style="margin: 10px 0 0 0; color: #856404;">Vous pouvez consulter la composition en cliquant sur le bouton ci-dessous.</p>
            <a href="voir_composition.php?id_match=<?= $id_match ?>" class="btn-action" style="display: inline-block; padding: 10px 20px; background: var(--primary); color: white; text-decoration: none; border-radius: 5px; margin-top: 10px;">
                <i class="fas fa-eye"></i> Voir la composition
            </a>
        </div>
    </div>
    
    <?php include "../includes/footer.php"; ?>
    <?php exit();
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
    SELECT p.id_joueur, p.id_poste, p.role, po.libelle AS poste
    FROM participation p
    LEFT JOIN poste po ON po.id_poste = p.id_poste
    WHERE p.id_match = ?
");
$stmt->execute([$id_match]);
$participations_existantes = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$draft_error = null;
$draft_titulaires = null;
$draft_remplacants = null;
if (isset($_SESSION['composition_draft'][$id_match])) {
    $draft = $_SESSION['composition_draft'][$id_match];
    $draft_error = $draft['error'] ?? null;
    $draft_titulaires = $draft['titulaires'] ?? [];
    $draft_remplacants = $draft['remplacants'] ?? [];
    unset($_SESSION['composition_draft'][$id_match]);
}

$titulaires_existants = [];
$remplacants_existants = [];
foreach ($participations_existantes as $participation) {
    if ($participation['role'] === 'TITULAIRE') {
        $titulaires_existants[] = $participation['id_joueur'];
    } else {
        $remplacants_existants[] = $participation['id_joueur'];
    }
}

$titulaires_selected_ids = array_values(array_unique(array_filter($draft_titulaires !== null ? $draft_titulaires : $titulaires_existants, fn($v) => $v !== null && $v !== '')));
$remplacants_selected_ids = array_values(array_unique(array_filter($draft_remplacants !== null ? $draft_remplacants : $remplacants_existants, fn($v) => $v !== null && $v !== '')));

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Composition - <?= htmlspecialchars($match["adversaire"]) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/feuille_match.css">
    <link rel="stylesheet" href="../assets/css/resultats.css">
    <link rel="stylesheet" href="../assets/css/composition.css">
</head>
<body>
    <div class="page-container">
        <?php if (!empty($draft_error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($draft_error) ?>
            </div>
        <?php endif; ?>
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
                <div class="stat-number" id="titulaires-count"><?= count($titulaires_selected_ids) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Remplaçants</div>
                <div class="stat-number" id="remplacants-count"><?= count($remplacants_selected_ids) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Postes restants</div>
                <div class="stat-number" id="postes-restants"><?= count($postes) - count($titulaires_selected_ids) ?></div>
            </div>
        </div>

        <!-- GRID PRINCIPALE -->
        <form method="POST" action="sauvegarde_compo.php" id="compositionForm">
            <input type="hidden" name="id_match" value="<?= $id_match ?>">
            
            <div class="main-grid">
                <!-- COLONNE GAUCHE : SÉLECTION DES REMPLAÇANTS -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Remplaçants</h3>
                    <p class="panel-description">Cochez les joueurs remplaçants. (Les titulaires sont prioritaires)</p>
                    
                    <div class="players-list">
                        <?php foreach ($joueurs as $j):
                            $est_titulaire = in_array($j['id_joueur'], $titulaires_selected_ids);
                            $est_remplacant = in_array($j['id_joueur'], $remplacants_selected_ids) && !$est_titulaire;
                            $est_blesse = ($j['statut'] === 'BLE');
                            $est_suspendu = ($j['statut'] === 'SUS');
                        ?>
                            <label class="player-card player-select">
                                <input type="checkbox"
                                       class="player-checkbox"
                                       name="remplacants[]"
                                       value="<?= $j['id_joueur'] ?>"
                                       <?= $est_remplacant ? 'checked' : '' ?>
                                       <?= $est_titulaire ? 'disabled' : '' ?>>

                                <div class="player-avatar <?= $est_blesse ? 'injured' : ($est_suspendu ? 'suspended' : '') ?>">
                                <?= strtoupper(substr($j['prenom'], 0, 1) . substr($j['nom'], 0, 1)) ?>
                            </div>
                                <div class="player-info">
                                    <div class="player-name">
                                        <?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>
                                    </div>
                                    <div class="player-license">
                                        <?= htmlspecialchars($j['num_licence']) ?>
                                        <?php if ($est_titulaire): ?>
                                            <span style="color: var(--accent); font-size: 0.8em;">(Titulaire)</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLONNE CENTRE : TERRAIN (TITULAIRES) -->
                <div class="panel pitch-container">
                    <h3 class="panel-title"><i class="fas fa-futbol"></i> Titulaires</h3>
                    
                    <div class="pitch">
                        <?php 
                        // Formation football classique
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
                        
                        $lignes = [
                            [$formation[0]],
                            array_slice($formation, 1, 4),
                            array_slice($formation, 5, 4),
                            array_slice($formation, 9, 2)
                        ];
                        
                        $slot_index = 0;
                        $prefill_used_players = [];
                        $prefill_titulaires = array_values(array_filter($participations_existantes, fn($p) => ($p['role'] ?? null) === 'TITULAIRE'));
                        foreach ($lignes as $ligne): 
                        ?>
                            <div class="formation-line">
                                <?php foreach ($ligne as $poste): 
                                    $slot_index++;
                                    // Trouver si ce poste a déjà un joueur assigné
                                    $joueur_assigné_id = null;
                                    // Note: Cette logique est approximative car si on a 2 défenseurs, comment savoir lequel va où ?
                                    // On prend le premier dispo qui correspond au poste et qu'on n'a pas encore affiché ?
                                    // Simplification : On cherche dans $participations_existantes
                                    
                                    // Problème : $participations_existantes ne stocke pas la position exacte (gauche/droite) sauf si 'poste' le dit.
                                    // La table participation a 'id_poste'.
                                    // Ici on va essayer de mapper intelligemment ou juste laisser l'utilisateur choisir.
                                    // On pré-remplit si on trouve une correspondance.
                                    
                                    // Pour faire simple : On ne pré-remplit pas parfaitement si doublons de poste, 
                                    // ou on utilise un compteur.
                                ?>
                                    <div class="position-slot" style="cursor: default; padding: 10px;">
                                        <div class="position-title"><?= htmlspecialchars($poste['poste_libelle']) ?></div>
                                        
                                        <select name="titulaire[<?= $poste['poste_libelle'] ?>_<?= $slot_index ?>]" 
                                                class="search-input" 
                                                style="width: 100%; padding: 8px; font-size: 0.9rem;">
                                            <option value="">-- Sélectionner --</option>
                                            <?php foreach ($joueurs as $j): 
                                                $slot_key = $poste['poste_libelle'] . '_' . $slot_index;
                                                $current_value = '';
                                                if ($draft_titulaires !== null) {
                                                    $current_value = $draft_titulaires[$slot_key] ?? '';
                                                } else {
                                                    foreach ($prefill_titulaires as $p) {
                                                        if ((int)$p['id_poste'] === (int)$poste['poste_id'] && !in_array($p['id_joueur'], $prefill_used_players)) {
                                                            $current_value = (string)$p['id_joueur'];
                                                            $prefill_used_players[] = $p['id_joueur'];
                                                            break;
                                                        }
                                                    }
                                                }

                                                $selected = ((string)$j['id_joueur'] === (string)$current_value);
                                                $disabled = ((string)$j['id_joueur'] !== (string)$current_value) && in_array($j['id_joueur'], $titulaires_selected_ids);
                                            ?>
                                                <option value="<?= $j['id_joueur'] ?>" <?= $selected ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                    <?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- COLONNE DROITE : INFOS -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-info-circle"></i> Instructions</h3>
                    <div style="color: rgba(255,255,255,0.8); line-height: 1.6;">
                        <p>1. Sélectionnez les titulaires pour chaque poste sur le terrain.</p>
                        <br>
                        <p>2. Cochez les remplaçants dans la liste de gauche.</p>
                        <br>
                        <p>3. Cliquez sur "Enregistrer" pour valider la feuille de match.</p>
                        <br>
                        <div class="stat-card" style="margin-top: 20px;">
                            <div class="stat-label">Joueurs dispo</div>
                            <div class="stat-number"><?= count($joueurs) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ACTIONS -->
            <div class="actions-bar">
                <button type="reset" class="btn-action btn-reset">
                    <i class="fas fa-redo"></i> Réinitialiser
                </button>
                <button type="submit" class="btn-action btn-save">
                    <i class="fas fa-save"></i> Enregistrer la composition
                </button>
            </div>
        </form>
    </div>
</body>
</html>
<?php include "../includes/footer.php"; ?>