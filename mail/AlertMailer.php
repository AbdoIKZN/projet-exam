<?php
/**
 * EventHub Pro - mail/AlertMailer.php
 * Adaptateur de compatibilite vers le controleur MVC.
 */

require_once __DIR__ . '/../app/Models/EventModel.php';
require_once __DIR__ . '/../app/Controllers/MailController.php';

class AlertMailer
{
    /**
     * Ancienne signature conservee pour compatibilite avec les appels legacy.
     */
    public static function sendCapacityAlert(PDO $pdo, array $event, string $pdfPath = ''): bool
    {
        unset($pdo);

        $eventId = (int)($event['id'] ?? 0);
        if ($eventId <= 0) {
            return false;
        }

        $eventModel = new EventModel();
        if (!$eventModel->markAlertSent($eventId)) {
            return false;
        }

        $mailController = new MailController();
        $sent = $mailController->sendCapacityAlert($event, $pdfPath);
        if (!$sent) {
            $eventModel->releaseAlertReservation($eventId);
        }

        return $sent;
    }
}
