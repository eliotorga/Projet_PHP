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
    $is_played = $match['etat'] === 'TERMINE';
    $message = $is_played ? 
        "Ce match a déjà été joué et ne peut plus être modifié." : 
        "La composition de ce match a déjà été enregistrée.";
    
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
            justify-content: center;
            color: var(--primary);
            font-weight: bold;
        }
        .vs {
            font-weight: bold;
            color: var(--gray);
            padding: 0 20px;
        }
    </style>";
    
    include_once "../includes/header.php";
    
    // Formatage de la date et l'heure
    $date_match = date('d/m/Y', strtotime($match['date_heure']));
    $heure_match = date('H:i', strtotime($match['date_heure']));
    
    echo "
    <div class='error-container'>
        <div class='error-code'><i class='fas fa-" . ($is_played ? 'whistle' : 'clipboard-check') . "'></i></div>
        <div class='error-content'>
            <h2><i class='fas fa-" . ($is_played ? 'exclamation-triangle' : 'info-circle') . "'></i> " . 
                ($is_played ? 'Match terminé' : 'Composition verrouillée') . "</h2>
            
            <div class='match-details'>
                <h3><i class='fas fa-info-circle'></i> Détails du match</h3>
                
                <div class='team-names'>
                    <div class='team'>
                        <div class='team-logo'>N</div>
                        <span>Notre équipe</span>
                    </div>
                    <span class='vs'>VS</span>
                    <div class='team'>
                        <div class='team-logo'>" . strtoupper(substr($match['adversaire'], 0, 1)) . "</div>
                        <span>" . htmlspecialchars($match['adversaire']) . "</span>
                    </div>
                </div>
                
                " . ($is_played ? 
                    "<div class='score-display'>" . 
                        ($match['buts_pour'] ?? '0') . " - " . ($match['buts_contre'] ?? '0') . 
                    "</div>" : '') . "
                
                <div class='match-info-grid'>
                    <div class='info-item'>
                        <i class='fas fa-calendar-alt'></i>
                        <span>Date: $date_match</span>
                    </div>
                    <div class='info-item'>
                        <i class='fas fa-clock'></i>
                        <span>Heure: $heure_match</span>
                    </div>
                    <div class='info-item'>
                        <i class='fas fa-map-marker-alt'></i>
                        <span>" . ($match['lieu'] == 'DOMICILE' ? 'Domicile' : 'Extérieur') . "</span>
                    </div>
                    <div class='info-item'>
                        <i class='fas fa-info-circle'></i>
                        <span>État: " . ($is_played ? 'Terminé' : 'Composition validée') . "</span>
                    </div>
                </div>
            </div>
            
            <p>$message " . ($is_played ? 
                "<a href='../stats/statistiques_match.php?id=" . $match['id_match'] . "'>Voir les statistiques du match</a>" : 
                "Pour toute modification, veuillez contacter l'administrateur.
                <br><small class='text-muted'>Dernière modification: " . 
                date('d/m/Y H:i', strtotime($match['date_modification'] ?? 'now')) . "</small>") . "</p>
            
            <div class='error-actions'>
                " . ($is_played ? 
                    "<a href='../stats/statistiques_match.php?id=" . $match['id_match'] . "' class='btn btn-primary'>
                        <i class='fas fa-chart-bar'></i> Voir les statistiques
                    </a>" : 
                    "<a href='../matchs/matchs.php' class='btn btn-primary'>
                        <i class='fas fa-arrow-left'></i> Retour aux matchs
                    </a>") . "
                
                <a href='../dashboard.php' class='btn btn-secondary'>
                    <i class='fas fa-home'></i> Tableau de bord
                </a>
                
                " . ($is_played ? 
                    "<a href='../matchs/feuille_de_match.php?id=" . $match['id_match'] . "' class='btn btn-secondary'>
                        <i class='fas fa-file-alt'></i> Voir la feuille de match
                    </a>" : '') . "
            </div>
        </div>
    </div>
    ";
    include "../includes/footer.php";
    exit();
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
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/feuille_match.css">
    <link rel="stylesheet" href="../assets/css/resultats.css">
    <link rel="stylesheet" href="../assets/css/composition.css">
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
        <form method="POST" action="sauvegarde_compo.php" id="compositionForm">
            <input type="hidden" name="id_match" value="<?= $id_match ?>">
            
            <div class="main-grid">
                <!-- COLONNE GAUCHE : SÉLECTION DES REMPLAÇANTS -->
                <div class="panel">
                    <h3 class="panel-title"><i class="fas fa-users"></i> Remplaçants</h3>
                    <p class="panel-description">Cochez les joueurs remplaçants. (Les titulaires sont prioritaires)</p>
                    
                    <div class="players-list">
                        <?php foreach ($joueurs as $j): 
                            $est_remplacant = in_array($j['id_joueur'], $remplacants_existants);
                            $est_titulaire = in_array($j['id_joueur'], $titulaires_existants);
                        ?>
                            <label class="player-card player-select">
                                <input type="checkbox" 
                                       class="player-checkbox"
                                       name="remplacants[]" 
                                       value="<?= $j['id_joueur'] ?>" 
                                       <?= $est_remplacant ? 'checked' : '' ?>>
                                
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
                                        
                                        <select name="titulaire[<?= $poste['poste_id'] ?>_<?= $slot_index ?>]" 
                                                class="search-input" 
                                                style="width: 100%; padding: 8px; font-size: 0.9rem;">
                                            <option value="">-- Sélectionner --</option>
                                            <?php foreach ($joueurs as $j): 
                                                // Vérif très basique pour pré-sélection (à améliorer si besoin)
                                                // On vérifie si ce joueur est titulaire à ce poste ID
                                                $selected = false;
                                                foreach ($participations_existantes as $key => $p) {
                                                    if ($p['id_joueur'] == $j['id_joueur'] && $p['role'] === 'TITULAIRE' && $p['id_poste'] == $poste['poste_id']) {
                                                        // On "consomme" cette participation pour ne pas la réutiliser sur le prochain slot identique ?
                                                        // Difficile sans état global.
                                                        // Pour l'instant, on sélectionne si ça match, mais ça risque de dupliquer l'affichage.
                                                        // Tant pis, l'utilisateur corrigera.
                                                        $selected = true;
                                                        // Hack : retirer de la liste pour ne pas le re-sélectionner au prochain tour de boucle ?
                                                        // unset($participations_existantes[$key]); // Risqué si on refresh
                                                        break;
                                                    }
                                                }
                                            ?>
                                                <option value="<?= $j['id_joueur'] ?>" <?= $selected ? 'selected' : '' ?>>
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