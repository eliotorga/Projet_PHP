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
    <title>⭐ Évaluation du Match</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1d4ed8;
        --secondary: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1f2937;
        --light: #f9fafb;
        --gray: #6b7280;
        --radius: 12px;
        --shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
        font-family: 'Montserrat', sans-serif;
    }

    body {
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        color: var(--dark);
        min-height: 100vh;
    }

    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    /* Header */
    .page-header {
        background: white;
        border-radius: var(--radius);
        padding: 2.5rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        animation: fadeInDown 0.6s ease;
        position: relative;
        overflow: hidden;
    }

    .page-header::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .header-content {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 1.5rem;
    }

    .match-info {
        flex: 1;
    }

    .match-info h1 {
        font-size: 2.25rem;
        margin-bottom: 1rem;
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .match-details {
        display: flex;
        gap: 1.5rem;
        flex-wrap: wrap;
        margin-top: 1rem;
    }

    .detail-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: var(--light);
        border-radius: 50px;
        font-size: 0.9rem;
        color: var(--gray);
    }

    .detail-item i {
        color: var(--primary);
    }

    .match-stats {
        background: var(--light);
        padding: 1.5rem;
        border-radius: var(--radius);
        min-width: 250px;
    }

    .stat-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0.75rem 0;
        border-bottom: 1px solid #e5e7eb;
    }

    .stat-item:last-child {
        border-bottom: none;
    }

    .stat-label {
        color: var(--gray);
        font-size: 0.9rem;
    }

    .stat-value {
        font-weight: 600;
        color: var(--dark);
    }

    /* Form Section */
    .form-section {
        background: white;
        border-radius: var(--radius);
        padding: 2rem;
        margin-bottom: 2rem;
        box-shadow: var(--shadow);
        animation: fadeInUp 0.6s ease 0.2s both;
    }

    .section-title {
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid var(--light);
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .score-inputs {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .score-input {
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .score-input input {
        width: 80px;
        padding: 0.75rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        text-align: center;
        font-size: 1.25rem;
        font-weight: 600;
        transition: var(--transition);
    }

    .score-input input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .score-separator {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--gray);
    }

    /* Result Buttons */
    .result-buttons {
        display: flex;
        gap: 1rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .result-btn {
        flex: 1;
        min-width: 120px;
        padding: 1rem;
        border: 2px solid #e5e7eb;
        border-radius: 8px;
        background: white;
        color: var(--dark);
        font-weight: 600;
        cursor: pointer;
        transition: var(--transition);
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .result-btn:hover {
        transform: translateY(-2px);
        box-shadow: var(--shadow);
    }

    .result-btn.active {
        border-color: var(--primary);
        background: linear-gradient(135deg, #eff6ff, #dbeafe);
        color: var(--primary);
    }

    .result-btn.victoire.active {
        border-color: var(--secondary);
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #047857;
    }

    .result-btn.defaite.active {
        border-color: var(--danger);
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: #dc2626;
    }

    .result-btn.nul.active {
        border-color: var(--warning);
        background: linear-gradient(135deg, #fffbeb, #fef3c7);
        color: #d97706;
    }

    /* Players Table */
    .players-table-container {
        overflow-x: auto;
        margin-bottom: 2rem;
    }

    .players-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        min-width: 800px;
    }

    .players-table thead {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
        position: sticky;
        top: 0;
    }

    .players-table th {
        padding: 1rem 1.5rem;
        text-align: left;
        font-weight: 600;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .players-table tbody tr {
        background: white;
        transition: var(--transition);
    }

    .players-table tbody tr:nth-child(even) {
        background: #fafafa;
    }

    .players-table tbody tr:hover {
        background: #f3f4f6;
        transform: translateX(4px);
    }

    .players-table td {
        padding: 1.25rem 1.5rem;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: middle;
    }

    .player-name {
        font-weight: 600;
        color: var(--dark);
    }

    .player-licence {
        font-size: 0.8rem;
        color: var(--gray);
        margin-top: 0.25rem;
    }

    .badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .badge-titulaire {
        background: #dbeafe;
        color: #1e40af;
    }

    .badge-remplacant {
        background: #fef3c7;
        color: #92400e;
    }

    .badge-statut-ACT { background: #d1fae5; color: #065f46; }
    .badge-statut-BLE { background: #fef3c7; color: #92400e; }
    .badge-statut-SUS { background: #fee2e2; color: #991b1b; }
    .badge-statut-ABS { background: #e5e7eb; color: #374151; }

    /* Star Rating */
    .star-rating {
        display: flex;
        gap: 0.25rem;
        align-items: center;
    }

    .star-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0.25rem;
        transition: var(--transition);
        font-size: 1.5rem;
        color: #d1d5db;
    }

    .star-btn:hover {
        transform: scale(1.2);
    }

    .star-btn.selected {
        color: #fbbf24;
    }

    .star-btn.selected:hover {
        color: #f59e0b;
    }

    .rating-value {
        font-weight: 600;
        margin-left: 0.75rem;
        min-width: 30px;
        text-align: center;
    }

    /* Form Actions */
    .form-actions {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        padding-top: 2rem;
        border-top: 2px solid var(--light);
    }

    .btn {
        padding: 0.875rem 2rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: var(--transition);
        cursor: pointer;
        border: none;
        font-size: 1rem;
    }

    .btn-primary {
        background: linear-gradient(135deg, var(--primary), var(--primary-dark));
        color: white;
    }

    .btn-primary:hover {
        background: linear-gradient(135deg, var(--primary-dark), #1e40af);
        transform: translateY(-2px);
        box-shadow: 0 10px 20px rgba(37, 99, 235, 0.3);
    }

    .btn-secondary {
        background: white;
        color: var(--dark);
        border: 2px solid #e5e7eb;
    }

    .btn-secondary:hover {
        background: var(--light);
        transform: translateY(-2px);
    }

    /* Preview Stats */
    .preview-stats {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
        flex-wrap: wrap;
    }

    .preview-stat {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1rem;
        background: var(--light);
        border-radius: var(--radius);
        font-size: 0.9rem;
    }

    .preview-stat i {
        color: var(--primary);
    }

    /* Error Messages */
    .error-message {
        background: linear-gradient(135deg, #fef2f2, #fee2e2);
        color: #dc2626;
        padding: 1rem 1.5rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        border-left: 4px solid #dc2626;
        animation: fadeIn 0.5s ease;
    }

    .error-message ul {
        margin-left: 1rem;
        margin-top: 0.5rem;
    }

    /* Success Message */
    .success-message {
        background: linear-gradient(135deg, #ecfdf5, #d1fae5);
        color: #047857;
        padding: 1rem 1.5rem;
        border-radius: var(--radius);
        margin-bottom: 1.5rem;
        border-left: 4px solid #10b981;
        animation: fadeIn 0.5s ease;
    }

    /* Animations */
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    @keyframes fadeInDown {
        from { opacity: 0; transform: translateY(-20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Responsive */
    @media (max-width: 768px) {
        .container {
            padding: 1rem;
        }
        
        .page-header {
            padding: 1.5rem;
        }
        
        .header-content {
            flex-direction: column;
        }
        
        .match-info h1 {
            font-size: 1.75rem;
        }
        
        .players-table td,
        .players-table th {
            padding: 0.75rem 1rem;
        }
        
        .form-actions {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            justify-content: center;
        }
    }
    </style>
</head>
<body>
    <div class="container">
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="success-message animate__animated animate__fadeInDown">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['success_message'] ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="error-message animate__animated animate__shakeX">
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
        <div class="page-header animate__animated animate__fadeInDown">
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
                        <span class="stat-value" style="color: <?= 
                            $match["etat"] === 'JOUE' ? '#10b981' : 
                            ($match["etat"] === 'PREPARE' ? '#f59e0b' : '#6b7280') 
                        ?>;">
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
                <div class="result-buttons" id="resultButtons">
                    <button type="button" class="result-btn victoire" data-value="VICTOIRE">
                        <i class="fas fa-trophy"></i>
                        <span>Victoire</span>
                    </button>
                    <button type="button" class="result-btn defaite" data-value="DEFAITE">
                        <i class="fas fa-times-circle"></i>
                        <span>Défaite</span>
                    </button>
                    <button type="button" class="result-btn nul" data-value="NUL">
                        <i class="fas fa-handshake"></i>
                        <span>Match nul</span>
                    </button>
                </div>
                <input type="hidden" name="resultat" id="resultatInput" value="<?= htmlspecialchars($match['resultat'] ?? '') ?>" required>
            </div>

            <!-- Évaluation des Joueurs -->
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-users"></i> Évaluation des Joueurs</h2>
                
                <!-- Prévisualisation -->
                <div class="preview-stats" id="previewStats">
                    <div class="preview-stat">
                        <i class="fas fa-user-check"></i>
                        <span><span id="notedPlayers">0</span> joueurs notés</span>
                    </div>
                    <div class="preview-stat">
                        <i class="fas fa-star"></i>
                        <span>Moyenne : <span id="averageRating">0.00</span>/5</span>
                    </div>
                    <div class="preview-stat">
                        <i class="fas fa-chart-line"></i>
                        <span>Distribution : <span id="ratingDistribution">0/0/0/0/0</span></span>
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
                            <?php foreach ($participants as $index => $p): ?>
                                <tr class="animate__animated animate__fadeInUp" style="animation-delay: <?= $index * 0.05 ?>s">
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
                                        <div class="star-rating" data-player="<?= $p["id_joueur"] ?>">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <button type="button" class="star-btn" data-value="<?= $i ?>">
                                                    <i class="fas fa-star"></i>
                                                </button>
                                            <?php endfor; ?>
                                            <span class="rating-value">
                                                <?= $p["evaluation"] ?: "-" ?>
                                            </span>
                                            <input type="hidden" 
                                                   name="evaluation[<?= $p["id_joueur"] ?>]" 
                                                   class="rating-input" 
                                                   value="<?= $p["evaluation"] ?: "" ?>">
                                        </div>
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

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion des boutons de résultat
        const resultButtons = document.querySelectorAll('.result-btn');
        const resultatInput = document.getElementById('resultatInput');
        
        // Initialiser l'état des boutons
        const currentResult = resultatInput.value;
        if (currentResult) {
            const activeBtn = document.querySelector(`.result-btn[data-value="${currentResult}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
        }
        
        resultButtons.forEach(btn => {
            btn.addEventListener('click', function() {
                // Retirer la classe active de tous les boutons
                resultButtons.forEach(b => b.classList.remove('active'));
                // Ajouter la classe active au bouton cliqué
                this.classList.add('active');
                // Mettre à jour la valeur cachée
                resultatInput.value = this.dataset.value;
            });
        });

        // Gestion des étoiles
        document.querySelectorAll('.star-rating').forEach(rating => {
            const stars = rating.querySelectorAll('.star-btn');
            const ratingValue = rating.querySelector('.rating-value');
            const hiddenInput = rating.querySelector('.rating-input');
            const playerId = rating.dataset.player;

            // Initialiser les étoiles
            const currentValue = hiddenInput.value ? parseInt(hiddenInput.value) : 0;
            updateStars(stars, currentValue);
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const value = parseInt(this.dataset.value);
                    hiddenInput.value = value;
                    ratingValue.textContent = value;
                    updateStars(stars, value);
                    updatePreviewStats();
                });
                
                // Hover effect
                star.addEventListener('mouseenter', function() {
                    const hoverValue = parseInt(this.dataset.value);
                    updateStars(stars, hoverValue, true);
                });
                
                star.addEventListener('mouseleave', function() {
                    const currentValue = hiddenInput.value ? parseInt(hiddenInput.value) : 0;
                    updateStars(stars, currentValue);
                });
            });
        });

        function updateStars(stars, value, isHover = false) {
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('selected');
                    star.innerHTML = '<i class="fas fa-star"></i>';
                } else {
                    star.classList.remove('selected');
                    star.innerHTML = '<i class="far fa-star"></i>';
                }
                
                // Animation pour l'étoile sélectionnée
                if (!isHover && index === value - 1) {
                    star.style.transform = 'scale(1.3)';
                    setTimeout(() => {
                        star.style.transform = '';
                    }, 200);
                }
            });
        }

        // Mise à jour des statistiques en direct
        function updatePreviewStats() {
            const ratingInputs = document.querySelectorAll('.rating-input');
            let totalNotes = 0;
            let sumNotes = 0;
            const distribution = [0, 0, 0, 0, 0];
            
            ratingInputs.forEach(input => {
                if (input.value) {
                    const note = parseInt(input.value);
                    totalNotes++;
                    sumNotes += note;
                    distribution[note - 1]++;
                }
            });
            
            // Mettre à jour l'affichage
            document.getElementById('notedPlayers').textContent = totalNotes;
            
            const average = totalNotes > 0 ? (sumNotes / totalNotes).toFixed(2) : "0.00";
            document.getElementById('averageRating').textContent = average;
            
            document.getElementById('ratingDistribution').textContent = 
                distribution.reverse().join('/');
            
            // Animation de la moyenne
            const avgElement = document.getElementById('averageRating');
            avgElement.style.transform = 'scale(1.2)';
            avgElement.style.color = getRatingColor(average);
            setTimeout(() => {
                avgElement.style.transform = '';
            }, 300);
        }
        
        function getRatingColor(average) {
            const avg = parseFloat(average);
            if (avg >= 4) return '#10b981'; // Vert
            if (avg >= 3) return '#f59e0b'; // Orange
            return '#ef4444'; // Rouge
        }

        // Initialiser les statistiques
        updatePreviewStats();

        // Validation du formulaire
        document.getElementById('evaluationForm').addEventListener('submit', function(e) {
            if (!resultatInput.value) {
                e.preventDefault();
                alert('Veuillez sélectionner un résultat pour le match.');
                resultButtons[0].focus();
                return;
            }
            
            // Vérifier qu'au moins une note est donnée
            const hasNotes = Array.from(document.querySelectorAll('.rating-input'))
                .some(input => input.value);
                
            if (!hasNotes) {
                if (!confirm("Aucune note n'a été attribuée. Souhaitez-vous quand même continuer ?")) {
                    e.preventDefault();
                    return;
                }
            }
            
            // Ajouter une animation de chargement
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
            submitBtn.disabled = true;
        });

        // Animation au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        // Observer les éléments pour l'animation
        document.querySelectorAll('.form-section').forEach((section, index) => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            section.style.transitionDelay = (index * 0.1) + 's';
            observer.observe(section);
        });
    });
    </script>
</body>
</html>

<?php include "../includes/footer.php"; ?>