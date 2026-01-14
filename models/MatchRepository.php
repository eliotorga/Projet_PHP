<?php
/**
 * MatchRepository - Gestion des accès aux données des matchs
 * Centralise toutes les requêtes SQL liées aux matchs
 */

require_once __DIR__ . '/../core/Database.php';

class MatchRepository {
    private PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Récupère tous les matchs
     * @return array Liste des matchs triés par date décroissante
     */
    public function getAll(): array {
        $sql = "SELECT * FROM matchs ORDER BY date_heure DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Récupère un match par son ID
     * @param int $id ID du match
     * @return array|false Données du match ou false
     */
    public function getById(int $id) {
        $stmt = $this->db->prepare("SELECT * FROM matchs WHERE id_match = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Récupère un match avec stats de participation
     * @param int $id_match ID du match
     * @return array|null Données du match avec stats ou null
     */
    public function getWithParticipationStats(int $id_match): ?array {
        $stmt = $this->db->prepare("
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
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Récupère les statistiques simples d'un match
     * @param int $id_match ID du match
     * @return array Statistiques du match
     */
    public function getStatsSummary(int $id_match): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) as nb_joueurs,
                AVG(evaluation) as moyenne_eval
            FROM participation
            WHERE id_match = ?
        ");
        $stmt->execute([$id_match]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Récupère la liste des adversaires existants
     * @return array Liste des adversaires
     */
    public function getDistinctAdversaires(): array {
        $stmt = $this->db->query("
            SELECT DISTINCT adversaire
            FROM matchs
            WHERE adversaire IS NOT NULL
              AND adversaire != ''
            ORDER BY adversaire
        ");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Récupère les prochains matchs (non joués)
     * @return array Liste des prochains matchs
     */
    public function getUpcoming(): array {
        $sql = "SELECT * FROM matchs
                WHERE etat != 'JOUE'
                ORDER BY date_heure ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Récupère les matchs à préparer
     * @return array Liste des matchs à préparer
     */
    public function getToPrepare(): array {
        $sql = "SELECT * FROM matchs
                WHERE etat = 'A_PREPARER'
                ORDER BY date_heure ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Récupère les matchs préparés mais pas joués
     * @return array Liste des matchs préparés
     */
    public function getPrepared(): array {
        $sql = "SELECT * FROM matchs
                WHERE etat = 'PREPARE'
                ORDER BY date_heure ASC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Récupère les matchs joués
     * @return array Liste des matchs joués
     */
    public function getPlayed(): array {
        $sql = "SELECT * FROM matchs
                WHERE etat = 'JOUE'
                ORDER BY date_heure DESC";
        return $this->db->query($sql)->fetchAll();
    }

    /**
     * Ajoute un nouveau match
     * @param array $data Données du match
     */
    public function insert(array $data): void {
        $sql = "INSERT INTO matchs (date_heure, adversaire, lieu, adresse, score_equipe, score_adverse, resultat, etat)
                VALUES (:dh, :adv, :lieu, :adresse, NULL, NULL, NULL, 'A_PREPARER')";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":dh"      => $data["date_heure"],
            ":adv"     => $data["adversaire"],
            ":lieu"    => $data["lieu"],
            ":adresse"  => $data["adresse"] ?? null
        ]);
    }

    /**
     * Modifie un match existant
     * @param int $id ID du match
     * @param array $data Nouvelles données
     */
    public function update(int $id, array $data): void {
        $sql = "UPDATE matchs
                SET date_heure = :dh,
                    adversaire = :adv,
                    lieu = :lieu,
                    score_equipe = :score_equipe,
                    score_adverse = :score_adverse,
                    resultat = :resultat,
                    etat = :etat
                WHERE id_match = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":dh"   => $data["date_heure"],
            ":adv"  => $data["adversaire"],
            ":lieu" => $data["lieu"],
            ":score_equipe" => $data["score_equipe"],
            ":score_adverse" => $data["score_adverse"],
            ":resultat" => $data["resultat"],
            ":etat" => $data["etat"],
            ":id"   => $id
        ]);
    }

    /**
     * Supprime un match
     * @param int $id ID du match
     */
    public function delete(int $id): void {
        $stmt = $this->db->prepare("DELETE FROM matchs WHERE id_match = ?");
        $stmt->execute([$id]);
    }

    /**
     * Met à jour le résultat d'un match
     * @param int $id_match ID du match
     * @param int $score_equipe Score de l'équipe
     * @param int $score_adverse Score adverse
     */
    public function setResult(int $id_match, int $score_equipe, int $score_adverse): void {
        // Détermination du résultat
        if ($score_equipe > $score_adverse) {
            $resultat = "VICTOIRE";
        } elseif ($score_equipe < $score_adverse) {
            $resultat = "DEFAITE";
        } else {
            $resultat = "NUL";
        }

        $sql = "UPDATE matchs
                SET score_equipe = :se,
                    score_adverse = :sa,
                    resultat = :res,
                    etat = 'JOUE'
                WHERE id_match = :id";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ":se"  => $score_equipe,
            ":sa"  => $score_adverse,
            ":res" => $resultat,
            ":id"  => $id_match
        ]);
    }

    /**
     * Récupère les statistiques globales des matchs
     * @return array Statistiques des matchs
     */
    public function getStats(): array {
        $sql = "
            SELECT
                SUM(resultat = 'VICTOIRE') AS victoires,
                SUM(resultat = 'DEFAITE') AS defaites,
                SUM(resultat = 'NUL') AS nuls,
                COUNT(*) AS total
            FROM matchs
            WHERE resultat IS NOT NULL
        ";
        return $this->db->query($sql)->fetch();
    }

    /**
     * Récupère les matchs avec stats et filtres
     * @param array $filters Filtres à appliquer
     * @return array Liste des matchs filtrés avec stats
     */
    public function getWithStats(array $filters = []): array {
        $filterEtat = $filters['etat'] ?? 'all';
        $filterResultat = $filters['resultat'] ?? 'all';
        $filterDate = $filters['date'] ?? 'all';

        $sql = "
            SELECT
                m.id_match,
                m.date_heure,
                m.adversaire,
                m.lieu,
                m.resultat,
                m.etat,
                m.score_equipe,
                m.score_adverse,
                COUNT(p.id_joueur) AS nb_joueurs,
                AVG(p.evaluation) AS moyenne_eval
            FROM matchs m
            LEFT JOIN participation p ON p.id_match = m.id_match
            WHERE 1=1
        ";

        $params = [];

        if ($filterEtat !== 'all') {
            $sql .= " AND m.etat = :etat";
            $params[':etat'] = $filterEtat;
        }

        if ($filterResultat !== 'all') {
            if ($filterResultat === 'null') {
                $sql .= " AND m.resultat IS NULL";
            } else {
                $sql .= " AND m.resultat = :resultat";
                $params[':resultat'] = $filterResultat;
            }
        }

        if ($filterDate !== 'all') {
            if ($filterDate === 'future') {
                $sql .= " AND m.date_heure > NOW()";
            } elseif ($filterDate === 'past') {
                $sql .= " AND m.date_heure <= NOW()";
            } elseif ($filterDate === 'month') {
                $sql .= " AND MONTH(m.date_heure) = MONTH(CURRENT_DATE()) AND YEAR(m.date_heure) = YEAR(CURRENT_DATE())";
            }
        }

        $sql .= "
            GROUP BY m.id_match
            ORDER BY m.date_heure DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les matchs avec stats pour suppression
     * @return array Liste des matchs avec stats de suppression
     */
    public function getWithDeleteStats(): array {
        $stmt = $this->db->prepare("
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
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère les infos basiques d'un match
     * @param int $id_match ID du match
     * @return array|null Infos basiques ou null
     */
    public function getBasicInfo(int $id_match): ?array {
        $stmt = $this->db->prepare("
            SELECT adversaire, date_heure
            FROM matchs
            WHERE id_match = ?
        ");
        $stmt->execute([$id_match]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Récupère les infos basiques de plusieurs matchs
     * @param array $ids Liste des IDs
     * @return array Liste des infos basiques
     */
    public function getBasicInfoByIds(array $ids): array {
        $ids = array_values(array_filter(array_map('intval', $ids)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("
            SELECT id_match, adversaire, date_heure
            FROM matchs
            WHERE id_match IN ($placeholders)
        ");
        $stmt->execute($ids);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifie si un match entre en conflit avec un autre
     * @param string $date_heure Date et heure du match
     * @return bool True si conflit, false sinon
     */
    public function hasConflict(string $date_heure): bool {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM matchs
            WHERE DATE(date_heure) = DATE(?) AND etat != 'JOUE'
        ");
        $stmt->execute([$date_heure]);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Récupère les prochains matchs limités
     * @param int $limit Nombre maximum de matchs
     * @return array Liste des prochains matchs
     */
    public function getUpcomingLimited(int $limit = 10): array {
        $stmt = $this->db->prepare("
            SELECT date_heure, adversaire, lieu
            FROM matchs
            WHERE date_heure > NOW()
            ORDER BY date_heure ASC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
}
