<?php
// permet de modifier les informations d'un joueur existant
// affiche un formulaire avec les donnees actuelles et les statistiques du joueur

require_once __DIR__ . "/../includes/auth_check.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../bdd/db_joueur.php";
require_once __DIR__ . "/../bdd/db_participation.php";

// Vérifier qu'un ID a été envoyé
if (!isset($_GET["id"])) {
    $_SESSION['error_message'] = "ID joueur manquant.";
    header("Location: liste_joueurs.php");
    exit;
}

$id_joueur = intval($_GET["id"]);

// Récupération du joueur à modifier
$joueur = getPlayerById($gestion_sportive, $id_joueur);

if (!$joueur) {
    $_SESSION['error_message'] = "Joueur introuvable.";
    header("Location: liste_joueurs.php");
    exit;
}

// Récupérer la liste des statuts
$statuts = getAllStatuts($gestion_sportive);

// Récupérer les commentaires du joueur
$commentaires = getRecentComments($gestion_sportive, $id_joueur, 5);

// Ajout d'un commentaire
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add_comment") {
    $comment_text = trim($_POST["comment_texte"] ?? "");
    if ($comment_text === "") {
        $_SESSION["comment_error"] = "Le commentaire est obligatoire.";
    } else {
        addComment($gestion_sportive, $id_joueur, $comment_text);
        $_SESSION["comment_success"] = "✅ Commentaire ajouté.";
    }
    header("Location: modifier_joueur.php?id=" . $id_joueur);
    exit;
}

// Récupérer les statistiques du joueur
$stats = getPlayerMatchStats($gestion_sportive, $id_joueur);

$error = "";
$success = "";

// Formulaire soumis ?
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $data = [
        "nom"            => trim($_POST["nom"] ?? ""),
        "prenom"         => trim($_POST["prenom"] ?? ""),
        "num_licence"    => strtoupper(trim($_POST["num_licence"] ?? "")),
        "date_naissance" => $_POST["date_naissance"] ?? null,
        "taille_cm"      => $_POST["taille_cm"] ? intval($_POST["taille_cm"]) : null,
        "poids_kg"       => $_POST["poids_kg"] ? floatval($_POST["poids_kg"]) : null,
        "id_statut"      => $_POST["id_statut"] ? intval($_POST["id_statut"]) : null
    ];

    // Validation
    $errors = [];
    
    if (empty($data["nom"])) {
        $errors[] = "Le nom est requis.";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["nom"])) {
        $errors[] = "Le nom ne doit contenir que des lettres, espaces ou tirets.";
    }
    if (empty($data["prenom"])) {
        $errors[] = "Le prénom est requis.";
    } elseif (!preg_match('/^[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]+$/u', $data["prenom"])) {
        $errors[] = "Le prénom ne doit contenir que des lettres, espaces ou tirets.";
    }
    if (empty($data["num_licence"])) $errors[] = "Le numéro de licence est requis.";
    if (empty($data["date_naissance"])) $errors[] = "La date de naissance est requise.";
    if ($data["taille_cm"] === null) $errors[] = "La taille est requise.";
    if ($data["poids_kg"] === null) $errors[] = "Le poids est requis.";
    if (empty($data["id_statut"])) $errors[] = "Le statut est requis.";

    if (!empty($data["num_licence"]) && !preg_match('/^LIC[0-9]{3}$/', $data["num_licence"])) {
        $errors[] = "Le numéro de licence doit respecter le format LIC001 (LIC suivi de 3 chiffres).";
    }
    
    // Validation spécifique
    if (!empty($data["num_licence"]) && $data["num_licence"] !== strtoupper($joueur["num_licence"])) {
        // Vérifier si le numéro de licence existe déjà
        if (isLicenseUsedByOtherPlayer($gestion_sportive, $data["num_licence"], $id_joueur)) {
            $errors[] = "Ce numéro de licence est déjà utilisé par un autre joueur.";
        }
    }
    
    if ($data["taille_cm"] !== null && ($data["taille_cm"] < 140 || $data["taille_cm"] > 220)) {
        $errors[] = "La taille doit être comprise entre 140 et 220 cm.";
    }
    
    if ($data["poids_kg"] !== null && ($data["poids_kg"] < 40 || $data["poids_kg"] > 120)) {
        $errors[] = "Le poids doit être compris entre 40 et 120 kg.";
    }
    
    if ($data["date_naissance"]) {
        $date_naissance = DateTime::createFromFormat('Y-m-d', $data["date_naissance"]);
        $min_date = (new DateTime())->modify('-50 years');
        $max_date = (new DateTime())->modify('+5 years');
        
        if ($date_naissance === false || $date_naissance < $min_date || $date_naissance > $max_date) {
            $errors[] = "La date de naissance doit être comprise entre " . 
                       $min_date->format('d/m/Y') . " et " . $max_date->format('d/m/Y');
        }
    }

    if (empty($errors)) {
        try {
            updatePlayer($gestion_sportive, $id_joueur, $data);
            $_SESSION['success_message'] = "✅ Joueur modifié avec succès !";
            header("Location: liste_joueurs.php");
            exit;
        } catch (Exception $e) {
            $error = "Erreur lors de la modification : " . $e->getMessage();
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include __DIR__ . "/../includes/header.php";
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier le Joueur - <?= htmlspecialchars($joueur['prenom'] . ' ' . $joueur['nom']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/modifier_joueur.css">
    <link rel="stylesheet" href="/Projet_PHP/assets/css/theme.css">
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="page-header">
            <div class="header-title">
                <h1><i class="fas fa-user-edit"></i> Modifier le Joueur</h1>
                <span class="player-badge">
                    <i class="fas fa-hashtag"></i>
                    <?= htmlspecialchars($joueur['num_licence']) ?>
                </span>
            </div>
            <a href="liste_joueurs.php" class="back-btn">
                <i class="fas fa-arrow-left"></i> Retour à la liste
            </a>
        </div>

        <!-- Messages -->
        <?php if ($error): ?>
            <div class="message-container">
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><?= $error ?></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="main-layout">
            <!-- Form Section -->
            <div class="form-card">
                <div class="form-header">
                    <h2><i class="fas fa-user-circle"></i> Informations Personnelles</h2>
                </div>

                <form method="POST" id="editForm">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="nom" class="form-label required">Nom</label>
                            <input type="text" 
                                id="nom" 
                                name="nom" 
                                class="form-control" 
                                value="<?= htmlspecialchars($joueur['nom']) ?>" 
                                required
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Maximum 50 caractères
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="prenom" class="form-label required">Prénom</label>
                            <input type="text" 
                                id="prenom" 
                                name="prenom" 
                                class="form-control" 
                                value="<?= htmlspecialchars($joueur['prenom']) ?>" 
                                required
                                maxlength="50"
                                pattern="[A-Za-zÀ-ÖØ-öø-ÿ\-\' ]{2,50}"
                                title="Lettres, espaces ou tirets uniquement">
                        </div>
                        
                        <div class="form-group">
                            <label for="num_licence" class="form-label required">Numéro de Licence</label>
                            <input type="text" 
                                   id="num_licence" 
                                   name="num_licence" 
                                   class="form-control" 
                                   value="<?= htmlspecialchars($joueur['num_licence']) ?>" 
                                   required
                                   maxlength="20">
                            <div class="form-help">
                                <i class="fas fa-info-circle"></i>
                                Format: LIC001 (LIC suivi de 3 chiffres)
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="date_naissance" class="form-label required">Date de Naissance</label>
                            <input type="date" 
                                   id="date_naissance" 
                                   name="date_naissance" 
                                   class="form-control" 
                                   value="<?= $joueur['date_naissance'] ?>"
                                   required
                                   min="1970-01-01"
                                   max="<?= date('Y-m-d', strtotime('-15 years')) ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="taille_cm" class="form-label required">Taille (cm)</label>
                            <input type="number" 
                                   id="taille_cm" 
                                   name="taille_cm" 
                                   class="form-control" 
                                   min="140" 
                                   max="220" 
                                   step="1"
                                   required
                                   value="<?= $joueur['taille_cm'] ?>">
                            <div class="form-help">
                                <i class="fas fa-ruler-vertical"></i>
                                Entre 140 et 220 cm
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="poids_kg" class="form-label required">Poids (kg)</label>
                            <input type="number" 
                                   id="poids_kg" 
                                   name="poids_kg" 
                                   class="form-control" 
                                   min="40" 
                                   max="120" 
                                   step="0.1"
                                   required
                                   value="<?= $joueur['poids_kg'] ?>">
                            <div class="form-help">
                                <i class="fas fa-weight"></i>
                                Entre 40 et 120 kg
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="id_statut" class="form-label required">Statut</label>
                            <select id="id_statut" name="id_statut" class="form-control" required>
                                <option value="">-- Sélectionner un statut --</option>
                                <?php foreach ($statuts as $s): ?>
                                    <option value="<?= $s["id_statut"] ?>" 
                                        <?= ($s["id_statut"] == $joueur["id_statut"]) ? "selected" : "" ?>
                                        data-code="<?= $s['code'] ?>">
                                        <?= htmlspecialchars($s["libelle"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-save"></i> Enregistrer les modifications
                        </button>
                        <button type="reset" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Réinitialiser
                        </button>
                        <a href="liste_joueurs.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Annuler
                        </a>
                    </div>
                </form>
            </div>

            <!-- Sidebar -->
            <div class="player-sidebar">
                <!-- Statistics -->
                <div class="stats-card">
                    <h3 class="card-title"><i class="fas fa-chart-line"></i> Statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-label">Matchs Joués</div>
                            <div class="stat-value"><?= $stats['total_matchs'] ?: 0 ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Titulaire</div>
                            <div class="stat-value"><?= $stats['matchs_titulaire'] ?: 0 ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Moyenne</div>
                            <div class="stat-value"><?= $stats['moyenne_evaluation'] ? number_format($stats['moyenne_evaluation'], 1) : 'N/A' ?></div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-label">Évalués</div>
                            <div class="stat-value"><?= $stats['matchs_evalues'] ?: 0 ?></div>
                        </div>
                    </div>
                </div>

                <!-- Commentaires -->
                <div class="comments-card">
                    <h3 class="card-title"><i class="fas fa-comment-alt"></i> Commentaires</h3>

                    <?php if (isset($_SESSION["comment_success"])): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <div><?= htmlspecialchars($_SESSION["comment_success"]) ?></div>
                        </div>
                        <?php unset($_SESSION["comment_success"]); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION["comment_error"])): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <div><?= htmlspecialchars($_SESSION["comment_error"]) ?></div>
                        </div>
                        <?php unset($_SESSION["comment_error"]); ?>
                    <?php endif; ?>

                    <form method="POST" class="comment-form">
                        <input type="hidden" name="action" value="add_comment">
                        <label for="comment_texte" class="form-label">Ajouter un commentaire</label>
                        <textarea id="comment_texte" name="comment_texte" class="comment-textarea" rows="4" maxlength="500" required></textarea>
                        <div class="comment-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Ajouter
                            </button>
                        </div>
                    </form>

                    <div class="comments-list">
                        <?php if (!empty($commentaires)): ?>
                            <?php foreach ($commentaires as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-date">
                                        <i class="far fa-calendar"></i>
                                        <?= date('d/m/Y H:i', strtotime($comment['date_commentaire'])) ?>
                                    </div>
                                    <div class="comment-text">
                                        <?= nl2br(htmlspecialchars($comment['texte'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="comment-item">
                                <div class="comment-text">Aucun commentaire pour ce joueur.</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
    </div>
    </div>
    </div>
</body>
</html>

<?php include __DIR__ . "/../includes/footer.php"; ?>
