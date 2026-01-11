<?php
// permet de supprimer un ou plusieurs joueurs de la base de donnees
// affiche la liste des joueurs avec leurs stats et gere la confirmation avant suppression

require_once "../includes/auth_check.php";
require_once "../includes/config.php";
require_once "../bdd/db_joueur.php";

// Initialiser les messages
$message = "";
$error = "";

// Récupérer tous les joueurs avec leurs statistiques
$joueurs = getPlayersWithStats($gestion_sportive);

// Calculer l'âge pour chaque joueur
foreach ($joueurs as &$joueur) {
    if ($joueur['date_naissance']) {
        $naissance = new DateTime($joueur['date_naissance']);
        $aujourdhui = new DateTime();
        $joueur['age'] = $aujourdhui->diff($naissance)->y;
    } else {
        $joueur['age'] = 'N/A';
    }
}
unset($joueur);

// Traitement de la suppression
$show_confirmation = false;
$players_to_confirm = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Cas 2 : Confirmation finale de la suppression
    if (isset($_POST['confirm_deletion']) && !empty($_POST['ids_to_delete'])) {
        $ids_joueurs = array_map('intval', $_POST['ids_to_delete']);
        
        try {
            $gestion_sportive->beginTransaction();
            
            $joueurs_supprimes = [];
            
            foreach ($ids_joueurs as $id_joueur) {
                // Récupérer le nom du joueur avant suppression
                $joueur_info = getPlayerNameById($gestion_sportive, $id_joueur);
                if ($joueur_info) {
                    $joueurs_supprimes[] = $joueur_info['prenom'] . ' ' . $joueur_info['nom'];
                }

                deletePlayerCascade($gestion_sportive, $id_joueur);
            }
            
            $gestion_sportive->commit();
            
            if (!empty($joueurs_supprimes)) {
                $_SESSION['success_message'] = "✅ " . count($joueurs_supprimes) . " joueur(s) supprimé(s) avec succès :<br>" . 
                                               implode(', ', $joueurs_supprimes);
            }
            
            // Recharger la page pour voir les changements
            header("Location: " . $_SERVER['PHP_SELF']);
            exit;
            
        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $error = "Erreur lors de la suppression : " . $e->getMessage();
        }
    }
    // Cas 1 : Demande de suppression (premier clic)
    elseif (isset($_POST['supprimer_joueurs'])) {
        if (!empty($_POST['joueurs_selectionnes'])) {
            $show_confirmation = true;
            $ids = array_map('intval', $_POST['joueurs_selectionnes']);
            
            // Récupérer les infos des joueurs pour la confirmation
            $players_to_confirm = getPlayersByIds($gestion_sportive, $ids);
        } else {
            $error = "Veuillez sélectionner au moins un joueur à supprimer.";
        }
    }
}

include "../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supprimer des Joueurs</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/supprimer_joueur.css">
</head>
<body>
    <div class="container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="header-content">
                <div class="header-title">
                    <i class="fas fa-trash-alt"></i>
                    <h1>Supprimer des Joueurs</h1>
                </div>
                <div class="header-subtitle">
                    Sélectionnez les joueurs que vous souhaitez supprimer de la base de données.
                    Cette action est irréversible et supprimera également leurs commentaires et participations aux matchs.
                </div>
                <div class="warning-box">
                    <i class="fas fa-exclamation-triangle" style="color: var(--warning); font-size: 1.2rem;"></i>
                    <div>
                        <strong>Attention :</strong> La suppression est définitive. 
                        Assurez-vous de ne plus avoir besoin des données des joueurs sélectionnés.
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
                <div class="players-count">
                    <i class="fas fa-users"></i> <?= count($joueurs) ?> joueur(s)
                </div>
                <div class="selection-info">
                    <span class="count"><?= (isset($_GET['select_all']) && $_GET['select_all'] == 1) ? count($joueurs) : '0' ?></span> joueur(s) sélectionné(s)
                </div>
            </div>
            
            <div class="controls-buttons">
                <a href="?select_all=1" class="btn btn-select-all">
                    <i class="fas fa-check-square"></i> Tout sélectionner
                </a>
                
                <a href="?select_all=0" class="btn btn-deselect-all">
                    <i class="fas fa-square"></i> Tout désélectionner
                </a>
                
                <button type="submit" form="deleteForm" name="supprimer_joueurs" class="btn btn-delete-selected">
                    <i class="fas fa-trash-alt"></i> Supprimer la sélection
                </button>
                
                <a href="liste_joueurs.php" class="btn btn-cancel">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
            </div>
        </div>

        <!-- TABLEAU DES JOUEURS -->
        <?php if (!empty($joueurs)): ?>
            <div class="players-table-container">
                <div class="table-responsive">
                    <form method="POST" id="deleteForm">
                        <table class="players-table">
                            <thead>
                                <tr>
                                    <th class="checkbox-cell">
                                        <i class="fas fa-check"></i>
                                    </th>
                                    <th>Joueur</th>
                                    <th>Statut</th>
                                    <th>Âge</th>
                                    <th>Statistiques</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($joueurs as $joueur): ?>
                                    <tr>
                                        <td class="checkbox-cell">
                                            <input type="checkbox" 
                                                   name="joueurs_selectionnes[]" 
                                                   value="<?= $joueur['id_joueur'] ?>" 
                                                   class="player-checkbox player-select"
                                                   <?= (isset($_GET['select_all']) && $_GET['select_all'] == 1) ? 'checked' : '' ?>>
                                        </td>
                                        <td>
                                            <div class="player-info">
                                                <div class="player-avatar">
                                                    <?= strtoupper(substr($joueur['prenom'], 0, 1) . substr($joueur['nom'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <div class="player-name">
                                                        <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?>
                                                    </div>
                                                    <div class="player-license">
                                                        <?= htmlspecialchars($joueur['num_licence']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= $joueur['statut_code'] ?>">
                                                <?= htmlspecialchars($joueur['statut_libelle']) ?>
                                            </span>
                                        </td>
                                        <td class="stats-cell">
                                            <?= $joueur['age'] ?> ans
                                        </td>
                                        <td class="stats-cell">
                                            <div class="stats-icons">
                                                <div class="stat-icon">
                                                    <i class="fas fa-gamepad"></i>
                                                    <span><?= $joueur['nb_matchs'] ?> matchs</span>
                                                </div>
                                                <div class="stat-icon">
                                                    <i class="fas fa-comment"></i>
                                                    <span><?= $joueur['nb_commentaires'] ?> avis</span>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <!-- Bouton de suppression caché (supprimé) -->
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="fas fa-user-slash"></i>
                </div>
                <h2 class="empty-title">Aucun joueur à supprimer</h2>
                <p class="empty-text">
                    Aucun joueur n'est actuellement enregistré dans la base de données.
                </p>
                <a href="liste_joueurs.php" class="btn btn-cancel" style="margin-top: 20px;">
                    <i class="fas fa-arrow-left"></i> Retour à la liste
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DE CONFIRMATION (PHP) -->
    <?php if ($show_confirmation): ?>
    <div class="confirmation-modal" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmation de suppression</h3>
            </div>
            <div class="modal-body">
                <p><strong>⚠️ Attention : Cette action est irréversible !</strong></p>
                <p>Vous êtes sur le point de supprimer <strong><?= count($players_to_confirm) ?></strong> joueur(s) de la base de données.</p>
                <p>Cette suppression entraînera également la suppression de :</p>
                <ul style="margin-left: 20px; margin-bottom: 15px;">
                    <li>Tous leurs commentaires</li>
                    <li>Toutes leurs participations aux matchs</li>
                    <li>Toutes les statistiques associées</li>
                </ul>
                <div class="players-to-delete">
                    <?php foreach ($players_to_confirm as $p): ?>
                        <div class="player-to-delete">
                            <span><?= htmlspecialchars($p['prenom'] . ' ' . $p['nom']) ?></span>
                            <i class="fas fa-user-times" style="color: var(--danger);"></i>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <a href="supprimer_joueur.php" class="btn btn-modal-cancel">
                    <i class="fas fa-times"></i> Annuler
                </a>
                
                <form method="POST">
                    <input type="hidden" name="confirm_deletion" value="1">
                    <?php foreach ($players_to_confirm as $p): ?>
                        <input type="hidden" name="ids_to_delete[]" value="<?= $p['id_joueur'] ?>">
                    <?php endforeach; ?>
                    <button type="submit" class="btn btn-modal-confirm">
                        <i class="fas fa-trash-alt"></i> Confirmer la suppression
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

</body>
</html>
<?php include "../includes/footer.php"; ?>
