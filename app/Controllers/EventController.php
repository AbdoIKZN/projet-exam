<?php
/**
 * EventHub Pro — app/Controllers/EventController.php
 * Contrôleur gérant les actions principales de l'application (événements, inscriptions, dashboard).
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/EventModel.php';
require_once __DIR__ . '/../Models/RegistrationModel.php';
require_once __DIR__ . '/../Models/UserModel.php';
require_once __DIR__ . '/MailController.php';
require_once __DIR__ . '/PdfController.php';

class EventController extends Controller
{
    private $eventModel;
    private $registrationModel;
    private $userModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->registrationModel = new RegistrationModel();
        $this->userModel = new UserModel();
    }

    /**
     * Affiche la liste des événements (landing page).
     * Route: GET /
     */
    public function index(): void
    {
        $this->render('events/index', ['activePage' => 'events']);
    }

    /**
     * Affiche le formulaire de création d'un événement.
     * Route: GET /events/create
     */
    public function createForm(): void
    {
        $this->render('events/create', ['activePage' => 'create']);
    }

    /**
     * Gère la création d'un événement via POST JSON.
     * Route: POST /events/create
     */
    public function createSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
        }

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        if (!$data) {
            $this->json(['success' => false, 'error' => 'Données JSON invalides.'], 400);
        }

        $required = ['title', 'description', 'date', 'location', 'capacity', 'category', 'organizer_email'];
        foreach ($required as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === null || !is_scalar($data[$field]) || trim((string)$data[$field]) === '') {
                $this->json(['success' => false, 'error' => "Champ obligatoire manquant : {$field}."], 400);
            }
        }

        try {
            $eventId = $this->eventModel->create($data);
            $this->json([
                'success'  => true,
                'event_id' => $eventId,
                'message'  => 'Événement créé avec succès.'
            ]);
        } catch (InvalidArgumentException $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()], 400);
        } catch (Throwable $e) {
            error_log('[EventHub] EventController@createSubmit: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur lors de la création.'], 500);
        }
    }

    /**
     * Gère l'inscription d'un participant via POST JSON (avec locks et transactions).
     * Route: POST /events/register
     */
    public function registerSubmit(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['success' => false, 'error' => 'Méthode non autorisée.'], 405);
        }

        $body = file_get_contents('php://input');
        $data = json_decode($body, true);

        $eventId = isset($data['event_id']) ? (int)$data['event_id'] : 0;
        $name    = isset($data['name'])     ? trim($data['name'])     : '';
        $email   = isset($data['email'])    ? trim($data['email'])    : '';

        if (!$eventId || !$name || !$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'error' => 'Données manquantes ou invalides.'], 400);
        }

        $dbConnection = Database::getInstance()->getConnection();

        try {
            $dbConnection->beginTransaction();

            // Récupérer l'événement avec verrou de ligne
            $event = $this->eventModel->getByIdForUpdate($eventId);

            if (!$event) {
                $dbConnection->rollBack();
                $this->json(['success' => false, 'error' => 'Événement introuvable.'], 404);
            }

            // Compter les inscrits existants confirmés
            $registeredCount = $this->registrationModel->getCountByEventId($eventId);
            $event['registered_count'] = $registeredCount;

            // Vérifier capacité
            if ($registeredCount >= (int)$event['capacity']) {
                $dbConnection->rollBack();
                $this->json(['success' => false, 'error' => 'Événement complet.', 'full' => true]);
            }

            // Vérifier doublon
            $existing = $this->registrationModel->getByEventAndEmail($eventId, $email);
            if ($existing && $existing['status'] === 'confirmed') {
                $dbConnection->rollBack();
                $this->json(['success' => false, 'error' => 'Vous êtes déjà inscrit(e) à cet événement.']);
            }

            // Récupérer l'ID utilisateur éventuel
            $user = $this->userModel->getByEmail($email);
            $userId = $user ? (int)$user['id'] : null;

            $token = $this->registrationModel->generateUniqueToken();

            if ($existing) {
                $this->registrationModel->reactivate((int)$existing['id'], $userId, $name, $token);
                $registrationId = (int)$existing['id'];
            } else {
                $registrationId = $this->registrationModel->insert($eventId, $userId, $name, $email, $token);
            }

            $dbConnection->commit();

            // Paramètres post-inscription
            $newCount = $registeredCount + 1;
            $capacity = max(1, (int)$event['capacity']);
            $pct = (int)round(($newCount / $capacity) * 100);
            $isFull = $newCount >= $capacity;

            $eventForMail = $event;
            $eventForMail['registered_count'] = $newCount;
            $eventForMail['available_places'] = max(0, $capacity - $newCount);
            $eventForMail['fill_pct'] = $pct;

            // Génération du ticket PDF
            $ticketPath = '';
            try {
                $pdfC = new PdfController();
                $ticketPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ticket_' . $registrationId . '_' . uniqid() . '.pdf';
                $pdfC->generateTicketPDFToFile($registrationId, $token, $ticketPath);
            } catch (Throwable $e) {
                logMailError($dbConnection, 'ticket', $email, $e->getMessage(), $eventId);
                $ticketPath = '';
            }

            // Envoi de l'email de confirmation
            $mailC = new MailController();
            $emailSent = $mailC->sendConfirmation($eventForMail, $name, $email, $token, $registrationId, $ticketPath);

            if ($ticketPath !== '' && is_file($ticketPath)) {
                @unlink($ticketPath);
            }

            // Déclenchement de l'alerte 80% (avec protection anti-doublon atomique)
            $alertSent = false;
            if ($pct >= 80) {
                // Reservation atomique de l'alerte via le modele.
                if ($this->eventModel->markAlertSent($eventId)) {
                    $alertSent = $mailC->sendCapacityAlert($eventForMail);
                    if (!$alertSent) {
                        $this->eventModel->releaseAlertReservation($eventId);
                    }
                }
            }

            $this->json([
                'success'          => true,
                'registration_id'  => $registrationId,
                'token'            => $token,
                'capacity_pct'     => $pct,
                'registered_count' => $newCount,
                'available_places' => max(0, $capacity - $newCount),
                'is_full'          => $isFull,
                'email_sent'       => $emailSent,
                'alert_sent'       => $alertSent,
                'message'          => 'Inscription réussie.'
            ]);

        } catch (Throwable $e) {
            if ($dbConnection->inTransaction()) {
                $dbConnection->rollBack();
            }
            error_log('[EventHub] EventController@registerSubmit failed: ' . $e->getMessage());
            $this->json(['success' => false, 'error' => 'Erreur serveur lors de l\'inscription.'], 500);
        }
    }

    /**
     * Gère la désinscription via le lien d'email.
     * Route: GET /events/unsubscribe
     */
    public function unsubscribe(): void
    {
        header('Content-Type: text/html; charset=UTF-8');

        $token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
        if (!preg_match('/^[a-f0-9]{64}$/i', $token)) {
            http_response_code(400);
            echo '<h1>Lien invalide</h1><p>Le lien de désinscription est invalide.</p>';
            exit;
        }

        try {
            $success = $this->registrationModel->cancelByToken($token);
            if (!$success) {
                echo '<h1>Désinscription déjà traitée</h1><p>Cette inscription est introuvable ou déjà annulée.</p>';
                exit;
            }

            echo '<h1>Désinscription confirmée</h1><p>Votre inscription a bien été annulée.</p>';
        } catch (Throwable $e) {
            error_log('[EventHub] EventController@unsubscribe: ' . $e->getMessage());
            http_response_code(500);
            echo '<h1>Erreur serveur</h1><p>Impossible de traiter la désinscription pour le moment.</p>';
        }
    }

    /**
     * Affiche le dashboard organisateur sécurisé.
     * Route: GET /dashboard
     */
    public function dashboard(): void
    {
        $this->requireOrganizer();
        $this->render('dashboard/index', ['activePage' => 'dashboard']);
    }
}
