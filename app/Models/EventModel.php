<?php
/**
 * EventHub Pro — app/Models/EventModel.php
 * Encapsule toutes les requêtes SQL liées aux événements.
 */

require_once __DIR__ . '/../../core/Database.php';

class EventModel
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère un événement spécifique par son ID avec les statistiques agrégées.
     *
     * @param  int $id
     * @return array|false
     */
    public function getById(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.name AS organizer_name,
                   (SELECT COUNT(*)
                    FROM registrations r
                    WHERE r.event_id = e.id AND r.status = 'confirmed') AS registered_count
            FROM events e
            LEFT JOIN users u ON u.id = e.organizer_id
            WHERE e.id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Recherche des événements selon des filtres dynamiques avec pagination.
     */
    /**
     * Recupere un evenement avec verrou de ligne pour une inscription atomique.
     */
    public function getByIdForUpdate(int $id)
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.name AS organizer_name
            FROM events e
            LEFT JOIN users u ON u.id = e.organizer_id
            WHERE e.id = :id
            FOR UPDATE
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function search(
        string $keyword   = '',
        string $category  = '',
        string $dateFrom  = '',
        string $dateTo    = '',
        bool   $hasPlaces = false,
        int    $page      = 1,
        int    $perPage   = 6,
        string $tab       = 'all'
    ): array {
        $page    = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        $offset  = ($page - 1) * $perPage;

        $baseSelect = "SELECT e.id,
                              e.title,
                              e.description,
                              e.event_date,
                              e.location,
                              e.capacity,
                              e.category,
                              e.organizer_email,
                              COUNT(r.id)                                  AS registered_count,
                              (e.capacity - COUNT(r.id))                   AS available_places,
                              ROUND(COUNT(r.id) / e.capacity * 100)        AS fill_percentage
                       FROM   events e
                       LEFT JOIN registrations r
                              ON r.event_id = e.id
                             AND r.status = 'confirmed'";

        $conditions = [];
        $having     = [];
        $bindings   = [];

        if ($keyword !== '') {
            $conditions[] = '(e.title LIKE :keyword_title OR e.description LIKE :keyword_description)';
            $bindings[':keyword_title'] = '%' . $keyword . '%';
            $bindings[':keyword_description'] = '%' . $keyword . '%';
        }

        if ($category !== '') {
            $conditions[] = 'e.category = :category';
            $bindings[':category'] = $category;
        }

        if ($dateFrom !== '') {
            $from = DateTime::createFromFormat('Y-m-d', $dateFrom);
            $fromErrors = DateTime::getLastErrors();
            if (!$from || ($fromErrors !== false && ($fromErrors['warning_count'] > 0 || $fromErrors['error_count'] > 0))) {
                throw new InvalidArgumentException('date_from invalide.');
            }
            $conditions[] = 'e.event_date >= :date_from';
            $bindings[':date_from'] = $from->format('Y-m-d 00:00:00');
        }

        if ($dateTo !== '') {
            $to = DateTime::createFromFormat('Y-m-d', $dateTo);
            $toErrors = DateTime::getLastErrors();
            if (!$to || ($toErrors !== false && ($toErrors['warning_count'] > 0 || $toErrors['error_count'] > 0))) {
                throw new InvalidArgumentException('date_to invalide.');
            }
            $conditions[] = 'e.event_date <= :date_to';
            $bindings[':date_to'] = $to->format('Y-m-d 23:59:59');
        }

        if ($tab === 'full') {
            $having[] = '(e.capacity - COUNT(r.id)) <= 0';
        } elseif ($hasPlaces || $tab === 'upcoming') {
            $having[] = '(e.capacity - COUNT(r.id)) > 0';
        }

        $groupBy = ' GROUP BY e.id, e.title, e.description, e.event_date, e.location,
                             e.capacity, e.category, e.organizer_email';

        $sql = $baseSelect;

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= $groupBy;

        if (!empty($having)) {
            $sql .= ' HAVING ' . implode(' AND ', $having);
        }

        $countSql = "SELECT COUNT(*) AS total FROM ({$sql}) filtered_events";

        $sql .= ' ORDER BY e.event_date ASC LIMIT :limit OFFSET :offset';

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($bindings);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->db->prepare($sql);
        foreach ($bindings as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $events = $stmt->fetchAll();

        foreach ($events as &$event) {
            $event['id']               = (int) $event['id'];
            $event['capacity']         = (int) $event['capacity'];
            $event['registered_count'] = (int) $event['registered_count'];
            $event['available_places'] = (int) $event['available_places'];
            $event['fill_percentage']  = (int) $event['fill_percentage'];
        }
        unset($event);

        return ['events' => $events, 'total' => $total];
    }

    /**
     * Crée un nouvel événement en base de données.
     *
     * @param  array $data
     * @return int
     */
    public function create(array $data): int
    {
        $title          = trim((string) $data['title']);
        $description    = trim((string) $data['description']);
        $location       = trim((string) $data['location']);
        $category       = trim((string) $data['category']);
        $organizerEmail = filter_var(trim((string) $data['organizer_email']), FILTER_VALIDATE_EMAIL);
        $capacity       = filter_var($data['capacity'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if ($title === '' || strlen($title) > 255) {
            throw new InvalidArgumentException('Le titre est obligatoire (max 255).');
        }
        if ($description === '') {
            throw new InvalidArgumentException('La description est obligatoire.');
        }
        if ($location === '' || strlen($location) > 255) {
            throw new InvalidArgumentException('Le lieu est obligatoire (max 255).');
        }
        if ($category === '') {
            throw new InvalidArgumentException('La catégorie est obligatoire.');
        }
        if ($organizerEmail === false) {
            throw new InvalidArgumentException("L'email organisateur est invalide.");
        }
        if ($capacity === false) {
            throw new InvalidArgumentException('La capacité doit être un entier positif.');
        }

        $date = DateTime::createFromFormat('Y-m-d\TH:i', (string) $data['date'])
            ?: DateTime::createFromFormat('Y-m-d H:i:s', (string) $data['date'])
            ?: DateTime::createFromFormat('Y-m-d H:i', (string) $data['date']);
        $dateErrors = DateTime::getLastErrors();

        if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))) {
            throw new InvalidArgumentException('Format de date invalide.');
        }

        $sql = "INSERT INTO events
                    (title, description, event_date, location, capacity, category, organizer_email, created_at)
                VALUES
                    (:title, :description, :event_date, :location, :capacity, :category, :organizer_email, NOW())";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            ':title'           => $title,
            ':description'     => $description,
            ':event_date'      => $date->format('Y-m-d H:i:s'),
            ':location'        => $location,
            ':capacity'        => $capacity,
            ':category'        => $category,
            ':organizer_email' => $organizerEmail,
        ]);

        if (!$success || $stmt->rowCount() !== 1) {
            throw new RuntimeException("L'événement n'a pas pu être créé.");
        }

        return (int) $this->db->lastInsertId();
    }

    /**
     * Marque l'alerte à 80% comme envoyée pour un événement.
     *
     * @param  int $id
     * @return bool
     */
    public function markAlertSent(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE events SET alert_sent = 1 WHERE id = :id AND alert_sent = 0");
        return $stmt->execute([':id' => $id]) && $stmt->rowCount() > 0;
    }

    /**
     * Libere la reservation d'alerte si l'email de capacite n'a pas pu partir.
     */
    public function releaseAlertReservation(int $id): bool
    {
        $stmt = $this->db->prepare("UPDATE events SET alert_sent = 0 WHERE id = :id AND alert_sent = 1");
        return $stmt->execute([':id' => $id]);
    }

    /**
     * Récupère la liste des événements pour le dashboard (Partie 4.2).
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $sql = "
            SELECT e.id,
                   e.title,
                   e.capacity,
                   e.alert_sent,
                   COALESCE(r.registered, 0) AS registered,
                   ROUND(COALESCE(r.registered, 0) / e.capacity * 100) AS fill_pct,
                   CASE WHEN COALESCE(r.registered, 0) >= e.capacity THEN 1 ELSE 0 END AS is_full
            FROM events e
            LEFT JOIN (
                SELECT event_id, COUNT(*) AS registered
                FROM registrations
                WHERE status = 'confirmed'
                GROUP BY event_id
            ) r ON r.event_id = e.id
            ORDER BY fill_pct DESC, e.event_date ASC
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
