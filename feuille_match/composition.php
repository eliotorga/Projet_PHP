<?php
// formulaire pour composer l'equipe avant un match
// selection des 11 titulaires par poste et des remplacants

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once __DIR__ . "/../bdd/db_commentaire.php";
require_once __DIR__ . "/../bdd/db_match.php";
require_once __DIR__ . "/../bdd/db_joueur.php";
require_once __DIR__ . "/../bdd/db_poste.php";
require_once __DIR__ . "/../bdd/db_participation.php";

if (!isset($_GET["id_match"])) {
    header("Location: ../matchs/liste_matchs.php?error=no_match");
    exit();
}

$id_match = intval($_GET["id_match"]);

$match = getMatchById($gestion_sportive, $id_match);

if (!$match) {
    die("<div class='error-container'><h2>⚽ Match introuvable</h2><p>Le match sélectionné n'existe pas.</p></div>");
}

$nowDt = new DateTimeImmutable('now');
$dateMatchObj = new DateTimeImmutable($match['date_heure']);
if ($dateMatchObj <= $nowDt || $match['etat'] === 'JOUE') {
    header("Location: voir_composition.php?id_match=$id_match");
    exit();
}


$joueurs = getActivePlayersDetailed($gestion_sportive);

$commentaire_histories = [];
$evaluation_histories = [];
foreach ($joueurs as $j) {
    $commentaire_histories[$j['id_joueur']] = getCommentaireHistory($gestion_sportive, (int)$j['id_joueur'], 3);
    $evaluation_histories[$j['id_joueur']] = getEvaluationHistory($gestion_sportive, (int)$j['id_joueur'], 3);
}

$postes = getAllPostesById($gestion_sportive);

$bench_slots = array_values(array_filter($postes, fn($p) => ($p['code'] ?? '') !== 'REM'));

$participations_existantes = getParticipationRolesByMatch($gestion_sportive, $id_match);

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
        $remplacants_existants[] = [
            'id_joueur' => $participation['id_joueur'],
            'id_poste' => $participation['id_poste']
        ];
    }
}

$titulaires_selected_ids = array_values(array_unique(array_filter($draft_titulaires !== null ? $draft_titulaires : $titulaires_existants, fn($v) => $v !== null && $v !== '')));
$remplacants_values = $draft_remplacants !== null ? array_values($draft_remplacants) : array_map(fn($p) => $p['id_joueur'], $remplacants_existants);
$remplacants_selected_ids = array_values(array_unique(array_filter($remplacants_values, fn($v) => $v !== null && $v !== '')));

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
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
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
                <!-- COLONNE GAUCHE : JOUEURS DISPONIBLES -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Joueurs disponibles</h3>
                    <p class="panel-description">Taille, poids et historiques pour chaque joueur actif.</p>
                    
                    <div class="players-list">
                        <?php foreach ($joueurs as $j):
                            $est_blesse = ($j['statut'] === 'BLE');
                            $est_suspendu = ($j['statut'] === 'SUS');
                            $comment_history = $commentaire_histories[$j['id_joueur']] ?? [];
                            $evaluation_history = $evaluation_histories[$j['id_joueur']] ?? [];
                        ?>
                            <div class="player-card player-info-card">
                                <div class="player-avatar <?= $est_blesse ? 'injured' : ($est_suspendu ? 'suspended' : '') ?>">
                                    <?= strtoupper(substr($j['prenom'], 0, 1) . substr($j['nom'], 0, 1)) ?>
                                </div>
                                <div class="player-info">
                                    <div class="player-name">
                                        <?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>
                                    </div>
                                    <div class="player-license">
                                        <?= htmlspecialchars($j['num_licence']) ?>
                                    </div>
                                    <div class="player-meta">
                                        Taille: <?= htmlspecialchars($j['taille_cm']) ?> cm · Poids: <?= htmlspecialchars($j['poids_kg']) ?> kg
                                    </div>
                                    <details class="player-history">
                                        <summary>Historique</summary>
                                        <div class="history-section">
                                            <div class="history-title">Commentaires</div>
                                            <?php if (!empty($comment_history)): ?>
                                                <?php foreach ($comment_history as $c): ?>
                                                    <div class="history-item">
                                                        <span class="history-date"><?= date('d/m/Y', strtotime($c['date_commentaire'])) ?></span>
                                                        <span><?= htmlspecialchars($c['texte']) ?></span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="history-item">Aucun commentaire.</div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="history-section">
                                            <div class="history-title">Évaluations</div>
                                            <?php if (!empty($evaluation_history)): ?>
                                                <?php foreach ($evaluation_history as $e): ?>
                                                    <div class="history-item">
                                                        <span class="history-date"><?= date('d/m/Y', strtotime($e['date_heure'])) ?></span>
                                                        <span><?= htmlspecialchars($e['adversaire']) ?> · <?= (int)$e['evaluation'] ?>/5</span>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <div class="history-item">Aucune évaluation.</div>
                                            <?php endif; ?>
                                        </div>
                                    </details>
                                </div>
                            </div>
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

                <!-- COLONNE DROITE : REMPLAÇANTS + INFOS -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Remplaçants par poste</h3>
                    <div class="bench-slots">
                        <?php $bench_used_players = []; ?>
                        <?php foreach ($bench_slots as $idx => $poste): 
                            $slot_key = "poste_" . $poste['id_poste'] . "_" . $idx;
                            $current_value = '';
                            if ($draft_remplacants !== null) {
                                $current_value = $draft_remplacants[$slot_key] ?? '';
                            } else {
                                foreach ($remplacants_existants as $p) {
                                    if ((int)$p['id_poste'] === (int)$poste['id_poste'] && !in_array($p['id_joueur'], $bench_used_players, true)) {
                                        $current_value = (string)$p['id_joueur'];
                                        $bench_used_players[] = $p['id_joueur'];
                                        break;
                                    }
                                }
                            }
                        ?>
                            <div class="bench-slot">
                                <label class="bench-label"><?= htmlspecialchars($poste['libelle']) ?></label>
                                <select name="remplacant_poste[<?= $slot_key ?>]" class="search-input bench-select">
                                    <option value="">-- Sélectionner --</option>
                                    <?php foreach ($joueurs as $j):
                                        $selected = ((string)$j['id_joueur'] === (string)$current_value);
                                        $disabled = ((string)$j['id_joueur'] !== (string)$current_value) && in_array($j['id_joueur'], $titulaires_selected_ids, true);
                                    ?>
                                        <option value="<?= $j['id_joueur'] ?>" <?= $selected ? 'selected' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($j['prenom'] . ' ' . $j['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="panel-divider"></div>

                    <h3 class="panel-title"><i class="fas fa-info-circle"></i> Instructions</h3>
                    <div class="panel-text">
                        <p>1. Sélectionnez les titulaires pour chaque poste sur le terrain.</p>
                        <p>2. Choisissez les remplaçants poste par poste à droite.</p>
                        <p>3. Cliquez sur "Enregistrer" pour valider la feuille de match.</p>
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
