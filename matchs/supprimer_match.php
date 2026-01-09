<?php
// permet de supprimer un ou plusieurs matchs de la base de donnees
// affiche la liste des matchs avec stats et gere la confirmation avant suppression

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";

// =====================
// SUPPRESSION DIRECTE
// =====================
if (isset($_GET['id'])) {
    $id_match = intval($_GET['id']);
    
    try {
        $gestion_sportive->beginTransaction();
        
        // 1. Supprimer les participations
        $stmt = $gestion_sportive->prepare("DELETE FROM participation WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        // 2. Supprimer le match
        $stmt = $gestion_sportive->prepare("DELETE FROM matchs WHERE id_match = ?");
        $stmt->execute([$id_match]);
        
        $gestion_sportive->commit();
        
        $_SESSION['success_message'] = "✅ Match supprimé avec succès.";
        header("Location: liste_matchs.php");
        exit;
        
    } catch (Exception $e) {
        $gestion_sportive->rollBack();
        $error = "❌ Erreur lors de la suppression : " . $e->getMessage();
    }
}

// =====================
// SUPPRESSION PAR LOT (code existant)
// =====================
// ... le reste de votre code actuel ...

// Initialiser les messages
$message = "";
$error = "";

// Récupérer tous les matchs avec leurs statistiques
$matchs = $gestion_sportive->query("
    SELECT 
        m.id_match,
        m.date_heure,
        m.adversaire,
        m.lieu,
        m.score_equipe,
        m.score_adverse,
        m.resultat,
        m.etat,
        COUNT(DISTINCT p.id_joueur) AS nb_participants,
        ROUND(AVG(p.evaluation), 1) AS note_moyenne
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    GROUP BY m.id_match
    ORDER BY m.date_heure DESC
")->fetchAll(PDO::FETCH_ASSOC);

$select_all = isset($_GET['select_all']) ? intval($_GET['select_all']) : 0;
$show_confirmation = false;
$matches_to_confirm = [];

// Suppression directe (Block redundant supprimé)

// Traitement de la suppression
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['confirm_deletion']) && !empty($_POST['ids_to_delete'])) {
        $ids_matchs = array_map('intval', $_POST['ids_to_delete']);
        try {
            $gestion_sportive->beginTransaction();
            $matchs_supprimes = [];
            $matchs_avec_participations = 0;
            foreach ($ids_matchs as $id_match) {
                $stmt = $gestion_sportive->prepare("SELECT adversaire, date_heure FROM matchs WHERE id_match = ?");
                $stmt->execute([$id_match]);
                if ($match_info = $stmt->fetch()) {
                    $date_format = date("d/m/Y", strtotime($match_info['date_heure']));
                    $matchs_supprimes[] = $match_info['adversaire'] . " (" . $date_format . ")";
                }
                $stmt = $gestion_sportive->prepare("SELECT COUNT(*) FROM participation WHERE id_match = ?");
                $stmt->execute([$id_match]);
                $nb_participations = $stmt->fetchColumn();
                if ($nb_participations > 0) {
                    $matchs_avec_participations++;
                }
                $stmt = $gestion_sportive->prepare("DELETE FROM participation WHERE id_match = ?");
                $stmt->execute([$id_match]);
                $stmt = $gestion_sportive->prepare("DELETE FROM matchs WHERE id_match = ?");
                $stmt->execute([$id_match]);
            }
            $gestion_sportive->commit();
            if (!empty($matchs_supprimes)) {
                $message = "✅ " . count($matchs_supprimes) . " match(s) supprimé(s) avec succès.";
                if ($matchs_avec_participations > 0) {
                    $message .= "<br><small>(" . $matchs_avec_participations . " match(s) avec évaluations supprimées)</small>";
                }
                $_SESSION['success_message'] = $message;
            }
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    } elseif (isset($_POST['demander_confirmation'])) {
        if (!empty($_POST['matchs_selectionnes'])) {
            $ids = array_map('intval', $_POST['matchs_selectionnes']);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $gestion_sportive->prepare("SELECT id_match, adversaire, date_heure FROM matchs WHERE id_match IN ($placeholders)");
            $stmt->execute($ids);
            $matches_to_confirm = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $show_confirmation = true;
        } else {
            $error = "Veuillez sélectionner au moins un match à supprimer.";
        }
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer des Matchs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/supprimer_match.css">
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-trash-alt"></i>
                    <h1>Supprimer des Matchs</h1>
                </div>
                <div class="header-subtitle">
                    Sélectionnez les matchs que vous souhaitez supprimer de la base de données.
                    Cette action est irréversible et supprimera également toutes les évaluations des joueurs pour ces matchs.
                </div>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning); font-size: 1.2rem;"></i>
                    <div>
                        <strong>Attention :</strong> La suppression des matchs déjà joués entraînera la perte des statistiques des joueurs pour ces rencontres.
                    </div>
                </div>
            </div>
        </div>

        <!-- MESSAGES -->
        <div class="message-container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <div><?= $_SESSION['success_message'] ?></div>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= $error ?></div>
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTROLS BAR -->
        <div class="controls-bar">
            <div class="stats-info">
                <div class="matches-count">
                    <i class="fas fa-calendar-alt"></i> <?= count($matchs) ?> match(s)
                </div>
                <div class="selection-info">
                    <?php $selected_count = $select_all ? count($matchs) : 0; ?>
                    <span class="count"><?= $selected_count ?></span> match(s) sélectionné(s)
                </div>
            </div>
            
            <div class="controls-buttons">
                <a href="?select_all=1" class="btn btn-select-all">
                    <i class="fas fa-check-square"></i> Tout sélectionner
                </a>
                
                <a href="?select_all=0" class="btn btn-deselect-all">
                    <i class="fas fa-square"></i> Tout désélectionner
                </a>
                
                <button type="submit" form="deleteForm" name="demander_confirmation" class="btn btn-delete-selected">
                    <i class="fas fa-trash-alt"></i> Supprimer la sélection
                </button>
                
                <a href="liste_matchs.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- TABLEAU DES MATCHS -->
        <?php if (!empty($matchs)): ?>
            <div class="matches-table-container">
                <div class="table-responsive">
                    <form method="POST" id="deleteForm">
                        <table class="matches-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <!-- -->
                                    </th>
                                    <th>Date & Heure</th>
                                    <th>Adversaire & Lieu</th>
                                    <th>Score</th>
                                    <th>Résultat</th>
                                    <th>État</th>
                                    <th>Statistiques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($matchs as $match): 
                                    $date_heure = new DateTime($match['date_heure']);
                                    $formatted_date = $date_heure->format('d/m/Y');
                                    $formatted_time = $date_heure->format('H:i');
                                    
                                    // Déterminer la couleur du score
                                    $score_class = '';
                                    if ($match['resultat'] === 'VICTOIRE') {
                                        $score_class = 'VICTOIRE';
                                    } elseif ($match['resultat'] === 'DEFAITE') {
                                        $score_class = 'DEFAITE';
                                    } elseif ($match['resultat'] === 'NUL') {
                                        $score_class = 'NUL';
                                    }
                                ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   name="matchs_selectionnes[]" 
                                                   value="<?= $match['id_match'] ?>" 
                                                   class="match-checkbox match-select"
                                                   <?= $select_all ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <div class="match-date"><?= $formatted_date ?></div>
                                            <div class="match-time"><?= $formatted_time ?></div>
                                        </td>
                                        <td>
                                            <div class="match-adversaire">
                                                <?= htmlspecialchars($match['adversaire']) ?>
                                            </div>
                                            <span class="match-lieu <?= $match['lieu'] ?>">
                                                <i class="fas <?= $match['lieu'] === 'DOMICILE' ? 'fa-home' : 'fa-plane' ?>"></i>
                                                <?= $match['lieu'] === 'DOMICILE' ? 'Domicile' : 'Extérieur' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($match['score_equipe'] !== null): ?>
                                                <div class="match-score">
                                                    <?= $match['score_equipe'] ?> - <?= $match['score_adverse'] ?>
                                                </div>
                                            <?php else: ?>
                                                <div style="color: #95a5a6; font-style: italic;">
                                                    À venir
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($match['resultat']): ?>
                                                <span class="resultat-badge <?= $score_class ?>">
                                                    <?= htmlspecialchars($match['resultat']) ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color: #95a5a6;">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="etat-badge <?= $match['etat'] ?>">
                                                <?= htmlspecialchars($match['etat']) ?>
                                            </span>
                                        </td>
                                        <td class="match-stats">
                                            <div class="stats-icons">
                                                <div class="stat-icon">
                                                    <i class="fas fa-users"></i>
                                                    <span><?= $match['nb_participants'] ?> joueurs</span>
                                                </div>
                                                <?php if ($match['note_moyenne']): ?>
                                                    <div class="stat-icon">
                                                        <i class="fas fa-star"></i>
                                                        <span><?= $match['note_moyenne'] ?>/5</span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <h2 class="empty-title">Aucun match à supprimer</h2>
                <p class="empty-text">
                    Aucun match n'est actuellement enregistré dans la base de données.
                </p>
                <a href="liste_matchs.php" class="btn btn-cancel" style="margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($show_confirmation): ?>
    <div class="confirmation-modal" style="display:flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h3>
            </div>
            <div class="modal-body">
                <p><strong>⚠️ Attention : Cette action est irréversible !</strong></p>
                <p>Vous êtes sur le point de supprimer <?= count($matches_to_confirm) ?> match(s) de la base de données.</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Toutes les évaluations des joueurs pour ces matchs</li>
                    <li>Toutes les participations enregistrées</li>
                    <li>Toutes les statistiques associées</li>
                </ul>
                <div class="matches-to-delete">
                    <?php foreach ($matches_to_confirm as $m): ?>
                        <div class="match-to-delete">
                            <span><?= htmlspecialchars($m['adversaire']) ?> (<?= date("d/m/Y", strtotime($m['date_heure'])) ?>)</span>
                            <i class="fas fa-trash" style="color: var(--danger);"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <?php foreach ($matches_to_confirm as $m): ?>
                        <input type="hidden" name="ids_to_delete[]" value="<?= intval($m['id_match']) ?>">
                    <?php endforeach; ?>
                    <button type="submit" name="confirm_deletion" class="btn btn-modal-confirm">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
                    </button>
                </form>
                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-modal-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    
</body>
</html>
<?php include __DIR__ . "/../includes/footer.php"; ?>
