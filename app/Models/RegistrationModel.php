<?php
/**
 * EventHub Pro — app/Models/RegistrationModel.php
 * Encapsule toutes les requêtes SQL liées aux inscriptions.
 */

require_once __DIR__ . '/../../core/Database.php';

class RegistrationModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Compte le nombre d'inscriptions confirmées pour un événement donné.
     *
     * @param  int $eventId
     * @return int
     */
    /**
     * Recupere les donnees necessaires a la generation d'un ticket PDF.
     *
     * @return array|false
     */
    public function getTicketData(int $registrationId, string $token)
    {
        $stmt = $this->db->prepare("
            SELECT r.id AS registration_id,
                   r.event_id,
                   r.user_id,
                   r.name,
                   r.email,
                   r.token,
                   r.registered_at,
                   e.title,
                   e.event_date,
                   e.location,
                   e.category,
                   e.capacity,
                   (SELECT COUNT(*)
                    FROM registrations reg2
                    WHERE reg2.event_id = e.id AND reg2.status = 'confirmed') AS registered_count
            FROM registrations r
            JOIN events e ON e.id = r.event_id
            WHERE r.id = :rid
              AND r.token = :token
              AND r.status = 'confirmed'
            LIMIT 1
        ");
        $stmt->execute([':rid' => $registrationId, ':token' => $token]);
        return $stmt->fetch();
    }

    public function getCountByEventId(int $eventId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM registrations
            WHERE event_id = :id AND status = 'confirmed'
        ");
        $stmt->execute([':id' => $eventId]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Trouve une inscription par événement et par adresse email.
     *
     * @param  int    $eventId
     * @param  string $email
     * @return array|false
     */
    public function getByEventAndEmail(int $eventId, string $email)
    {
        $stmt = $this->db->prepare("
            SELECT id, status
            FROM registrations
            WHERE event_id = :eid AND email = :email
            LIMIT 1
        ");
        $stmt->execute([':eid' => $eventId, ':email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Génère un token d'inscription unique de 64 caractères.
     *
     * @return string
     */
    public function generateUniqueToken(): string
    {
        do {
            $token = bin2hex(random_bytes(32));
            $stmt = $this->db->prepare('SELECT id FROM registrations WHERE token = :token');
            $stmt->execute([':token' => $token]);
        } while ($stmt->fetch());

        return $token;
    }

    /**
     * Crée une nouvelle inscription.
     */
    public function insert(int $eventId, ?int $userId, string $name, string $email, string $token): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO registrations (event_id, user_id, name, email, token, status, registered_at)
            VALUES (:event_id, :user_id, :name, :email, :token, "confirmed", NOW())
        ');
        $stmt->execute([
            ':event_id' => $eventId,
            ':user_id'  => $userId,
            ':name'     => $name,
            ':email'    => $email,
            ':token'    => $token,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Met à jour et réactive une inscription existante précédemment annulée.
     */
    public function reactivate(int $id, ?int $userId, string $name, string $token): bool
    {
        $stmt = $this->db->prepare('
            UPDATE registrations
            SET user_id = :user_id,
                name = :name,
                token = :token,
                status = "confirmed",
                registered_at = NOW(),
                cancelled_at = NULL
            WHERE id = :id
        ');
        return $stmt->execute([
            ':user_id' => $userId,
            ':name'    => $name,
            ':token'   => $token,
            ':id'      => $id,
        ]);
    }

    /**
     * Annule une inscription via son token unique (désinscription).
     *
     * @param  string $token
     * @return bool
     */
    public function cancelByToken(string $token): bool
    {
        $stmt = $this->db->prepare("
            UPDATE registrations
            SET status = 'cancelled', cancelled_at = NOW()
            WHERE token = :token AND status = 'confirmed'
        ");
        return $stmt->execute([':token' => $token]) && $stmt->rowCount() > 0;
    }

    /**
     * Récupère la liste des inscrits confirmés pour un événement donné (triés par nom).
     *
     * @param  int $eventId
     * @return array
     */
    public function getConfirmedList(int $eventId): array
    {
        $stmt = $this->db->prepare("
            SELECT id, name, email, registered_at
            FROM registrations
            WHERE event_id = :id AND status = 'confirmed'
            ORDER BY name ASC
        ");
        $stmt->execute([':id' => $eventId]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les statistiques d'inscription des 7 derniers jours pour un événement (ou global).
     *
     * @param  int|null $eventId
     * @return array
     */
    public function getDailyStats(?int $eventId = null): array
    {
        $sql = "
            SELECT DATE(registered_at) AS day, COUNT(*) AS count
            FROM registrations
            WHERE status = 'confirmed'
              AND registered_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        ";
        $bindings = [];
        if ($eventId !== null) {
            $sql .= " AND event_id = :id ";
            $bindings[':id'] = $eventId;
        }
        $sql .= "
            GROUP BY DATE(registered_at)
            ORDER BY day ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return $stmt->fetchAll();
    }

    /**
     * Récupère le nombre d'inscriptions confirmées dans les dernières 24 heures.
     *
     * @return int
     */
    public function getCountLast24h(): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM registrations
            WHERE status = 'confirmed'
              AND registered_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)
        ");
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
