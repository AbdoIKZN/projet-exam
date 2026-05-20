<?php
/**
 * EventHub Pro - mail/SendConfirmation.php
 * Adaptateur de compatibilite vers le controleur MVC.
 */

require_once __DIR__ . '/../app/Controllers/MailController.php';

class SendConfirmation
{
    /**
     * Ancienne signature conservee pour compatibilite avec les appels legacy.
     */
    public static function send(
        PDO $pdo,
        array $event,
        string $name,
        string $email,
        string $token,
        int $registrationId = 0,
        string $ticketPath = ''
    ): bool {
        unset($pdo);

        $mailController = new MailController();
        return $mailController->sendConfirmation(
            $event,
            $name,
            $email,
            $token,
            $registrationId,
            $ticketPath
        );
    }
}
