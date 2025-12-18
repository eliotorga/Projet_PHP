<?php
require_once "../includes/auth_check.php";
require_once "../includes/config.php";

/* Vérification ID match */
if (!isset($_GET["id_match"])) {
    die("Match non spécifié.");
}
$id_match = (int) $_GET["id_match"];

/* Récupération du match avec plus de détails */
$stmt = $gestion_sportive->prepare("
    SELECT 
        m.date_heure, 
        m.adversaire, 
        m.lieu, 
        m.resultat,
        m.score_equipe,
        m.score_adverse,
        m.etat,
        COUNT(p.id_joueur) as nb_participants,
        ROUND(AVG(p.evaluation), 2) as moyenne_existante
    FROM matchs m
    LEFT JOIN participation p ON p.id_match = m.id_match
    WHERE m.id_match = ?
    GROUP BY m.id_match
");
$stmt->execute([$id_match]);
$match = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$match) {
    die("Match introuvable.");
}

/* Récupération des joueurs ayant participé */
$stmt = $gestion_sportive->prepare("
    SELECT 
        p.id_joueur,
        p.role,
        p.evaluation,
        j.nom,
        j.prenom,
        j.num_licence,
        po.libelle AS poste,
        s.code as statut_code,
        s.libelle as statut_libelle
    FROM participation p
    JOIN joueur j ON j.id_joueur = p.id_joueur
    LEFT JOIN poste po ON po.id_poste = p.id_poste
    LEFT JOIN statut s ON j.id_statut = s.id_statut
    WHERE p.id_match = ?
    ORDER BY 
        CASE p.role 
            WHEN 'TITULAIRE' THEN 1 
            WHEN 'REMPLACANT' THEN 2 
            ELSE 3 
        END,
        po.libelle
");
$stmt->execute([$id_match]);
$participants = $stmt->fetchAll(PDO::FETCH_ASSOC);

/* Calcul des stats pour affichage */
$nb_notes = 0;
$somme_notes = 0;
$distribution = [0, 0, 0, 0, 0];

foreach ($participants as $p) {
    if ($p['evaluation']) {
        $nb_notes++;
        $somme_notes += $p['evaluation'];
        $distribution[$p['evaluation'] - 1]++;
    }
}
$moyenne_calc = $nb_notes > 0 ? round($somme_notes / $nb_notes, 2) : 0;

/* Enregistrement du formulaire */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $resultat = $_POST["resultat"] ?? null;
    $score_equipe = !empty($_POST["score_equipe"]) ? (int)$_POST["score_equipe"] : null;
    $score_adverse = !empty($_POST["score_adverse"]) ? (int)$_POST["score_adverse"] : null;
    $evaluations = $_POST["evaluation"] ?? [];

    /* Validation */
    $errors = [];
    if (!$resultat) {
        $errors[] = "Le résultat du match est requis.";
    }
    
    if ($score_equipe !== null && $score_adverse !== null) {
        if ($score_equipe < 0 || $score_adverse < 0) {
            $errors[] = "Les scores ne peuvent pas être négatifs.";
        }
    }
    
    /* Validation des notes */
    $notes_valides = true;
    foreach ($evaluations as $id_joueur => $note) {
        if ($note !== "" && ($note < 1 || $note > 5)) {
            $notes_valides = false;
            break;
        }
    }
    
    if (!$notes_valides) {
        $errors[] = "Les notes doivent être comprises entre 1 et 5.";
    }

    if (empty($errors)) {
        try {
            $gestion_sportive->beginTransaction();
            
            /* Mise à jour du match */
            $stmt = $gestion_sportive->prepare("
                UPDATE matchs
                SET resultat = ?, 
                    score_equipe = ?, 
                    score_adverse = ?,
                    etat = 'JOUE'
                WHERE id_match = ?
            ");
            $stmt->execute([$resultat, $score_equipe, $score_adverse, $id_match]);

            /* Mise à jour des évaluations */
            $stmtEval = $gestion_sportive->prepare("
                UPDATE participation
                SET evaluation = ?
                WHERE id_match = ? AND id_joueur = ?
            ");

            foreach ($evaluations as $id_joueur => $note) {
                $note_value = $note !== "" ? (int)$note : null;
                $stmtEval->execute([$note_value, $id_match, (int)$id_joueur]);
            }
            
            $gestion_sportive->commit();
            
            /* Message de succès */
            $_SESSION['success_message'] = "✅ Évaluations enregistrées avec succès !";
            
            header("Location: ../matchs/liste_matchs.php");
            exit;
            
        } catch (Exception $e) {
            $gestion_sportive->rollBack();
            $errors[] = "Erreur lors de l'enregistrement : " . $e->getMessage();
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
    <title>Évaluation des Joueurs - <?= htmlspecialchars($match['adversaire']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/feuille_match.css">
    <link rel="stylesheet" href="../assets/css/resultats.css">
    <link rel="stylesheet" href="../assets/css/evaluation.css">
</head>
<body>
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>Veuillez corriger les erreurs suivantes :</strong>
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="page-header">
            <div class="header-content">
                <div class="match-info">
                    <h1><i class="fas fa-star"></i> Évaluation du Match</h1>
                    <div class="match-details">
                        <span class="detail-item">
                            <i class="fas fa-calendar-alt"></i>
                            <?= date("d/m/Y H:i", strtotime($match["date_heure"])) ?>
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-users"></i>
                            <?= htmlspecialchars($match["adversaire"]) ?>
                        </span>
                        <span class="detail-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <?= htmlspecialchars($match["lieu"]) ?>
                        </span>
                        <?php if ($match["score_equipe"] !== null): ?>
                            <span class="detail-item">
                                <i class="fas fa-futbol"></i>
                                <?= $match["score_equipe"] ?> - <?= $match["score_adverse"] ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="match-stats">
                    <div class="stat-item">
                        <span class="stat-label">Joueurs</span>
                        <span class="stat-value"><?= $match["nb_participants"] ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">État actuel</span>
                        <span class="stat-value status-<?= strtolower($match["etat"]) ?>">
                            <?= htmlspecialchars($match["etat"]) ?>
                        </span>
                    </div>
                    <?php if ($match["moyenne_existante"]): ?>
                        <div class="stat-item">
                            <span class="stat-label">Moyenne existante</span>
                            <span class="stat-value"><?= $match["moyenne_existante"] ?>/5</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <form method="post" id="evaluationForm">
            <!-- Résultat du Match -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-flag-checkered"></i> Résultat du Match</h2>
                
                <!-- Scores -->
                <div class="score-inputs">
                    <div class="score-input">
                        <span>Notre équipe</span>
                        <input type="number" name="score_equipe" min="0" 
                               value="<?= htmlspecialchars($match['score_equipe'] ?? '') ?>"
                               placeholder="0">
                    </div>
                    <span class="score-separator">-</span>
                    <div class="score-input">
                        <input type="number" name="score_adverse" min="0" 
                               value="<?= htmlspecialchars($match['score_adverse'] ?? '') ?>"
                               placeholder="0">
                        <span><?= htmlspecialchars($match['adversaire']) ?></span>
                    </div>
                </div>

                <!-- Boutons de résultat -->
                <div class="result-buttons">
                    <label class="result-label">
                        <input type="radio" name="resultat" value="VICTOIRE" <?= ($match['resultat'] ?? '') === 'VICTOIRE' ? 'checked' : '' ?> required>
                        <span class="result-victory"><i class="fas fa-trophy"></i> Victoire</span>
                    </label>
                    <label class="result-label">
                        <input type="radio" name="resultat" value="DEFAITE" <?= ($match['resultat'] ?? '') === 'DEFAITE' ? 'checked' : '' ?>>
                        <span class="result-defeat"><i class="fas fa-times-circle"></i> Défaite</span>
                    </label>
                    <label class="result-label">
                        <input type="radio" name="resultat" value="NUL" <?= ($match['resultat'] ?? '') === 'NUL' ? 'checked' : '' ?>>
                        <span class="result-draw"><i class="fas fa-handshake"></i> Nul</span>
                    </label>
                </div>
            </div>

            <!-- Évaluation des Joueurs -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-users"></i> Évaluation des Joueurs</h2>
                
                <!-- Prévisualisation -->
                <div class="preview-stats">
                    <div class="preview-stat">
                        <i class="fas fa-user-check"></i>
                        <span><?= $nb_notes ?> joueurs notés</span>
                    </div>
                    <div class="preview-stat">
                        <i class="fas fa-star"></i>
                        <span>Moyenne : <?= $moyenne_calc ?>/5</span>
                    </div>
                </div>

                <!-- Table des joueurs -->
                <div class="players-table-container">
                    <table class="players-table">
                        <thead>
                            <tr>
                                <th>Joueur</th>
                                <th>Poste</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Note (1 à 5)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($participants as $p): ?>
                                <tr>
                                    <td>
                                        <div class="player-name">
                                            <?= htmlspecialchars($p["prenom"] . " " . $p["nom"]) ?>
                                        </div>
                                        <div class="player-licence">
                                            #<?= htmlspecialchars($p["num_licence"]) ?>
                                        </div>
                                    </td>
                                    <td><?= htmlspecialchars($p["poste"]) ?></td>
                                    <td>
                                        <span class="badge <?= $p["role"] === "TITULAIRE" ? "badge-titulaire" : "badge-remplacant" ?>">
                                            <?= $p["role"] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-statut-<?= $p["statut_code"] ?>">
                                            <?= htmlspecialchars($p["statut_libelle"]) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <select name="evaluation[<?= $p["id_joueur"] ?>]" class="rating-select">
                                            <option value="">-- Note --</option>
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <option value="<?= $i ?>" <?= ($p["evaluation"] == $i) ? 'selected' : '' ?>>
                                                    <?= $i ?> - <?= 
                                                        $i == 5 ? 'Excellent' : 
                                                        ($i == 4 ? 'Bon' : 
                                                        ($i == 3 ? 'Moyen' : 
                                                        ($i == 2 ? 'Mauvais' : 'Très mauvais'))) 
                                                    ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Actions -->
            <div class="form-actions">
                <a href="../matchs/liste_matchs.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Retour
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Enregistrer les évaluations
                </button>
            </div>
        </form>
    </div>
</body>
</html>

<?php include "../includes/footer.php"; ?>
