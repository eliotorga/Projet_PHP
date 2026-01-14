<?php
/**
 * JoueurRepository - Gestion des accès aux données des joueurs
 * Centralise toutes les requêtes SQL liées aux joueurs
 */

require_once __DIR__ . '/../core/Database.php';

class JoueurRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les joueurs
     * @return array Liste des joueurs avec statut
     */
    public function getAll(): array {
        $sql = "SELECT j.*, s.libelle AS statut_libelle
                FROM joueur j
                JOIN statut s ON s.id_statut = j.id_statut
                ORDER BY j.nom, j.prenom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère un joueur par son ID
     * @param int $id ID du joueur
     * @return array|false Données du joueur ou false
     */
    public function getById(int $id) {
        $sql = "SELECT * FROM joueur WHERE id_joueur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Récupère le profil complet d'un joueur avec statut et âge calculé
     * @param int $id_joueur ID du joueur
     * @return array|null Profil du joueur ou null
     */
    public function getProfile(int $id_joueur): ?array {
        $stmt = $this->db->prepare("
            SELECT
                j.*,
                s.code as statut_code,
                s.libelle as statut_libelle,
                YEAR(CURDATE()) - YEAR(date_naissance) - (DATE_FORMAT(CURDATE(), '%m%d') < DATE_FORMAT(date_naissance, '%m%d')) as age
            FROM joueur j
            LEFT JOIN statut s ON j.id_statut = s.id_statut
            WHERE j.id_joueur = ?
        ");
        $stmt->execute([$id_joueur]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Récupère tous les statuts disponibles
     * @return array Liste des statuts
     */
    public function getAllStatuts(): array {
        $stmt = $this->db->query("SELECT * FROM statut ORDER BY libelle");
        return $stmt->fetchAll();
    }

    /**
     * Compte le nombre de joueurs actifs
     * @return int Nombre de joueurs actifs
     */
    public function getActivePlayersCount(): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            WHERE s.code = 'ACT'
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Compte le nombre de joueurs indisponibles (blessés/suspendus)
     * @return int Nombre de joueurs indisponibles
     */
    public function getUnavailablePlayersCount(): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            WHERE s.code IN ('BLE', 'SUS')
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Récupère uniquement les joueurs actifs
     * @return array Liste des joueurs actifs
     */
    public function getActivePlayers(): array {
        $sql = "SELECT j.*, s.libelle AS statut_libelle
                FROM joueur j
                JOIN statut s ON s.id_statut = j.id_statut
                WHERE s.code = 'ACT'
                ORDER BY j.nom, j.prenom";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les joueurs actifs avec détails (pour feuille de match)
     * @return array Liste détaillée des joueurs actifs
     */
    public function getActivePlayersDetailed(): array {
        $sql = "
            SELECT j.id_joueur, j.nom, j.prenom, j.num_licence, j.taille_cm, j.poids_kg, s.code AS statut
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            WHERE s.code = 'ACT'
            ORDER BY j.nom, j.prenom
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Ajoute un nouveau joueur
     * @param array $data Données du joueur
     */
    public function insert(array $data): void {
        $sql = "INSERT INTO joueur (nom, prenom, num_licence, date_naissance, taille_cm, poids_kg, id_statut)
                VALUES (:nom, :prenom, :licence, :ddn, :taille, :poids, :statut)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":nom"     => $data["nom"],
            ":prenom"  => $data["prenom"],
            ":licence" => $data["num_licence"],
            ":ddn"     => $data["date_naissance"],
            ":taille"  => $data["taille_cm"],
            ":poids"   => $data["poids_kg"],
            ":statut"  => $data["id_statut"]
        ]);
    }

    /**
     * Modifie un joueur existant
     * @param int $id ID du joueur
     * @param array $data Nouvelles données
     */
    public function update(int $id, array $data): void {
        $sql = "UPDATE joueur
                SET nom = :nom,
                    prenom = :prenom,
                    num_licence = :licence,
                    date_naissance = :ddn,
                    taille_cm = :taille,
                    poids_kg = :poids,
                    id_statut = :statut
                WHERE id_joueur = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":nom"     => $data["nom"],
            ":prenom"  => $data["prenom"],
            ":licence" => $data["num_licence"],
            ":ddn"     => $data["date_naissance"],
            ":taille"  => $data["taille_cm"],
            ":poids"   => $data["poids_kg"],
            ":statut"  => $data["id_statut"],
            ":id"      => $id
        ]);
    }

    /**
     * Supprime un joueur
     * @param int $id ID du joueur
     */
    public function delete(int $id): void {
        $sql = "DELETE FROM joueur WHERE id_joueur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }

    /**
     * Ajoute un commentaire sur un joueur
     * @param int $id_joueur ID du joueur
     * @param string $texte Texte du commentaire
     */
    public function addComment(int $id_joueur, string $texte): void {
        $sql = "INSERT INTO commentaire (id_joueur, texte, date_commentaire)
                VALUES (?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_joueur, $texte]);
    }

    /**
     * Récupère l'historique des commentaires pour un joueur
     * @param int $id_joueur ID du joueur
     * @return array Historique des commentaires
     */
    public function getComments(int $id_joueur): array {
        $sql = "SELECT * FROM commentaire
                WHERE id_joueur = ?
                ORDER BY date_commentaire DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_joueur]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les derniers commentaires d'un joueur
     * @param int $id_joueur ID du joueur
     * @param int $limit Nombre maximum de commentaires
     * @return array Derniers commentaires
     */
    public function getRecentComments(int $id_joueur, int $limit = 5): array {
        $limit = max(1, (int)$limit);
        $stmt = $this->db->prepare("
            SELECT * FROM commentaire
            WHERE id_joueur = ?
            ORDER BY date_commentaire DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $id_joueur, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les joueurs avec leurs statistiques (matchs, notes, commentaires)
     * @return array Liste des joueurs avec stats
     */
    public function getWithStats(): array {
        $sql = "
            SELECT
                j.id_joueur,
                j.nom,
                j.prenom,
                j.num_licence,
                j.date_naissance,
                j.taille_cm,
                j.poids_kg,
                s.id_statut,
                s.code AS statut_code,
                s.libelle AS statut_libelle,
                COUNT(DISTINCT p.id_match) AS nb_matchs,
                ROUND(AVG(p.evaluation), 1) AS note_moyenne,
                COUNT(c.id_commentaire) AS nb_commentaires
            FROM joueur j
            JOIN statut s ON s.id_statut = j.id_statut
            LEFT JOIN participation p ON p.id_joueur = j.id_joueur
            LEFT JOIN commentaire c ON c.id_joueur = j.id_joueur
            GROUP BY j.id_joueur, j.nom, j.prenom, j.num_licence, j.date_naissance,
                     j.taille_cm, j.poids_kg, s.id_statut, s.code, s.libelle
            ORDER BY j.nom, j.prenom
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les statistiques globales par statut
     * @return array Comptage par statut
     */
    public function getStatutCounts(): array {
        $sql = "
            SELECT
                s.code AS statut_code,
                s.libelle AS statut,
                COUNT(j.id_joueur) AS nb_joueurs
            FROM statut s
            LEFT JOIN joueur j ON j.id_statut = s.id_statut
            GROUP BY s.id_statut, s.code, s.libelle
            ORDER BY s.id_statut
        ";
        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le nom complet d'un joueur
     * @param int $id_joueur ID du joueur
     * @return array|null Nom et prénom ou null
     */
    public function getNameById(int $id_joueur): ?array {
        $stmt = $this->db->prepare("SELECT nom, prenom FROM joueur WHERE id_joueur = ?");
        $stmt->execute([$id_joueur]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Vérifie si un numéro de licence est déjà utilisé (hors joueur courant)
     * @param string $num_licence Numéro de licence
     * @param int $id_joueur ID du joueur à exclure
     * @return bool True si utilisé, false sinon
     */
    public function isLicenseUsedByOtherPlayer(string $num_licence, int $id_joueur): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM joueur
            WHERE num_licence = ? AND id_joueur != ?
        ");
        $stmt->execute([$num_licence, $id_joueur]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Vérifie si un numéro de licence est déjà utilisé (pour ajout)
     * @param string $num_licence Numéro de licence
     * @return bool True si utilisé, false sinon
     */
    public function isLicenseUsed(string $num_licence): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM joueur WHERE num_licence = ?");
        $stmt->execute([$num_licence]);
        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Supprime un joueur et toutes ses dépendances (cascade)
     * @param int $id_joueur ID du joueur
     */
    public function deleteWithCascade(int $id_joueur): void {
        $stmt = $this->db->prepare("DELETE FROM commentaire WHERE id_joueur = ?");
        $stmt->execute([$id_joueur]);
        $stmt = $this->db->prepare("DELETE FROM participation WHERE id_joueur = ?");
        $stmt->execute([$id_joueur]);
        $stmt = $this->db->prepare("DELETE FROM joueur WHERE id_joueur = ?");
        $stmt->execute([$id_joueur]);
    }

    /**
     * Récupère plusieurs joueurs par leurs IDs
     * @param array $ids Liste des IDs
     * @return array Liste des joueurs
     */
    public function getByIds(array $ids): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT id_joueur, nom, prenom FROM joueur WHERE id_joueur IN ($placeholders)");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le nombre de participations d'un joueur
     * @param int $id_joueur ID du joueur
     * @return int Nombre de participations
     */
    public function getNbParticipations(int $id_joueur): int {
        $sql = "SELECT COUNT(*) AS total
                FROM participation
                WHERE id_joueur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_joueur]);
        return (int)$stmt->fetch()["total"];
    }

    /**
     * Récupère la moyenne des évaluations d'un joueur
     * @param int $id_joueur ID du joueur
     * @return float|null Moyenne ou null
     */
    public function getAvgEvaluation(int $id_joueur): ?float {
        $sql = "SELECT AVG(evaluation) AS moyenne
                FROM participation
                WHERE id_joueur = ? AND evaluation IS NOT NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id_joueur]);
        $result = $stmt->fetch();
        return $result["moyenne"] !== null ? (float)$result["moyenne"] : null;
    }

    /**
     * Récupère les informations avancées d'un joueur (moyenne, évaluations, commentaires)
     * @param int $id_joueur ID du joueur
     * @return array Informations avancées
     */
    public function getExtraInfo(int $id_joueur): array {
        // Moyenne des évaluations
        $stmt = $this->db->prepare("
            SELECT AVG(evaluation) AS moyenne
            FROM participation
            WHERE id_joueur = ? AND evaluation IS NOT NULL
        ");
        $stmt->execute([$id_joueur]);
        $moyenne = $stmt->fetch()["moyenne"];

        // 5 dernières évaluations
        $stmt = $this->db->prepare("
            SELECT p.evaluation, m.date_heure
            FROM participation p
            JOIN matchs m ON m.id_match = p.id_match
            WHERE p.id_joueur = ? AND p.evaluation IS NOT NULL
            ORDER BY m.date_heure DESC
            LIMIT 5
        ");
        $stmt->execute([$id_joueur]);
        $evaluations = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Commentaires récents
        $stmt = $this->db->prepare("
            SELECT texte as commentaire, date_commentaire
            FROM commentaire
            WHERE id_joueur = ?
            ORDER BY date_commentaire DESC
            LIMIT 3
        ");
        $stmt->execute([$id_joueur]);
        $commentaires = $stmt->fetchAll();

        return [
            "moyenne" => $moyenne ? round($moyenne, 2) : null,
            "evaluations" => $evaluations,
            "commentaires" => $commentaires
        ];
    }
}
