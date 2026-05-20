<?php
/**
 * EventHub Pro — app/Controllers/ApiController.php
 * Endpoints API JSON pour la recherche d'événements et statistiques du dashboard.
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/EventModel.php';
require_once __DIR__ . '/../Models/RegistrationModel.php';

class ApiController extends Controller
{
    private $eventModel;
    private $registrationModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->registrationModel = new RegistrationModel();
    }

    /**
     * Recherche et renvoie les événements au format JSON.
     * Route: GET/POST /api/events
     */
    public function searchEvents(): void
    {
        $params = [];
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $body   = file_get_contents('php://input');
            $params = json_decode($body, true) ?? [];
        } else {
            $params = $_GET;
        }

        $keyword   = isset($params['keyword'])  ? trim($params['keyword'])   : (isset($params['kw']) ? trim($params['kw']) : '');
        $category  = isset($params['category']) ? trim($params['category'])  : (isset($params['cat']) ? trim($params['cat']) : '');
        $dateFrom  = isset($params['date_from'])? trim($params['date_from']) : '';
        $dateTo    = isset($params['date_to'])  ? trim($params['date_to'])   : '';
        $hasPlaces = isset($params['has_places'])? (bool)$params['has_places']: (isset($params['pl']) && (string)$params['pl'] === '1');
        $tab       = isset($params['tab'])      ? trim($params['tab'])       : 'all';
        $page      = isset($params['page'])     ? max(1, (int)$params['page']): 1;
        $perPage   = 6;

        try {
            $result = $this->eventModel->search($keyword, $category, $dateFrom, $dateTo, $hasPlaces, $page, $perPage, $tab);
            $this->json([
                'success' => true,
                'data'    => $result['events'],
                'meta'    => [
                    'total'    => $result['total'],
                    'page'     => $page,
                    'per_page' => $perPage,
                    'pages'    => ceil($result['total'] / $perPage),
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            error_log('[EventHub] ApiController@searchEvents failed: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur.'], 500);
        }
    }

    /**
     * Renvoie les statistiques en temps réel pour le Dashboard (Partie 4.2).
     * Sécurisé par session (Rôle organisateur requis).
     * Route: GET /api/stats
     */
    public function dashboardStats(): void
    {
        $this->requireOrganizer(true);

        try {
            $perEvent = $this->eventModel->getDashboardStats();
            $totalEvents = count($perEvent);
            $totalRegistered = 0;
            $fillSum = 0;
            $alertCount = 0;

            foreach ($perEvent as &$event) {
                $event['id'] = (int)$event['id'];
                $event['capacity'] = (int)$event['capacity'];
                $event['registered'] = (int)$event['registered'];
                $event['fill_pct'] = (int)$event['fill_pct'];
                $event['is_full'] = (bool)$event['is_full'];
                $totalRegistered += $event['registered'];
                $fillSum += $event['fill_pct'];
                if ($event['fill_pct'] >= 80 || (int)$event['alert_sent'] === 1) {
                    $alertCount++;
                }
                unset($event['alert_sent']);
            }
            unset($event);

            $newLast24h = $this->registrationModel->getCountLast24h();
            $byDay = $this->registrationModel->getDailyStats();

            // Remplissage des 7 derniers jours (zéro s'il n'y a pas d'inscriptions)
            $countsByDay = [];
            foreach ($byDay as $row) {
                $countsByDay[$row['day']] = (int)$row['count'];
            }

            $formattedByDay = [];
            for ($i = 6; $i >= 0; $i--) {
                $day = date('Y-m-d', strtotime('-' . $i . ' day'));
                $formattedByDay[] = [
                    'day' => $day,
                    'count' => $countsByDay[$day] ?? 0,
                ];
            }

            $this->json([
                'success' => true,
                'generated_at' => date('Y-m-d H:i:s'),
                'summary' => [
                    'total_events' => $totalEvents,
                    'total_registered' => $totalRegistered,
                    'new_last_24h' => $newLast24h,
                    'avg_fill_pct' => $totalEvents > 0 ? (int)round($fillSum / $totalEvents) : 0,
                    'alert_count' => $alertCount,
                ],
                'top3' => array_slice($perEvent, 0, 3),
                'per_event' => $perEvent,
                'registrations_by_day' => $formattedByDay,
            ]);
        } catch (Throwable $e) {
            error_log('[EventHub] ApiController@dashboardStats failed: ' . $e->getMessage());
            $this->json([
                'success' => false,
                'error'   => 'Erreur serveur lors de la récupération des statistiques.'
            ], 500);
        }
    }
}
