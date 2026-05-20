<?php
/**
 * EventHub Pro — app/Controllers/MailController.php
 * Gère l'envoi d'emails de confirmation (avec ticket) et d'alertes de capacité.
 */

require_once __DIR__ . '/../../config/mailer.php';
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';

class MailController extends Controller
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Envoie l'email de confirmation d'inscription au participant.
     */
    public function sendConfirmation(
        array  $event,
        string $name,
        string $email,
        string $token,
        int    $registrationId,
        string $ticketPath = ''
    ): bool {
        $template = __DIR__ . '/../../mail/templates/confirmation.html';
        if (!is_file($template)) {
            logMailError($this->db, 'confirmation', $email, 'Template confirmation.html introuvable.', (int)($event['id'] ?? 0) ?: null);
            return false;
        }

        $baseUrl = $this->baseUrl();
        $ticketLink = $baseUrl . '/pdf/ticket?registration_id=' . $registrationId . '&token=' . urlencode($token);
        $unsubscribeLink = $baseUrl . '/events/unsubscribe?token=' . urlencode($token);

        $html = file_get_contents($template);
        $html = str_replace(
            [
                '{{PARTICIPANT_NAME}}',
                '{{EVENT_TITLE}}',
                '{{EVENT_DATE}}',
                '{{EVENT_LOCATION}}',
                '{{TICKET_LINK}}',
                '{{UNSUBSCRIBE_LINK}}',
                '{{YEAR}}',
            ],
            [
                $this->e($name),
                $this->e((string)$event['title']),
                $this->e($this->formatDate((string)$event['event_date'])),
                $this->e((string)$event['location']),
                $this->e($ticketLink),
                $this->e($unsubscribeLink),
                date('Y'),
            ],
            $html
        );

        try {
            $mail = createMailer();
            $mail->addAddress($email, $name);
            $mail->Subject = 'Confirmation inscription - ' . $event['title'];
            $mail->Body    = $html;
            $mail->AltBody = $this->plainText($html);

            if ($ticketPath !== '' && is_file($ticketPath)) {
                $mail->addAttachment($ticketPath, 'ticket_' . (int)$event['id'] . '_' . $registrationId . '.pdf');
            }

            $mail->send();
            return true;
        } catch (Throwable $e) {
            logMailError($this->db, 'confirmation', $email, $e->getMessage(), (int)($event['id'] ?? 0) ?: null);
            return false;
        }
    }

    /**
     * Envoie l'email d'alerte de capacité (80%) à l'organisateur.
     */
    public function sendCapacityAlert(array $event, string $pdfPath = ''): bool
    {
        $eventId = (int)$event['id'];
        $organizerEmail = (string)$event['organizer_email'];

        $template = __DIR__ . '/../../mail/templates/alert.html';
        if (!is_file($template)) {
            logMailError($this->db, 'capacity_alert', $organizerEmail, 'Template alert.html introuvable.', $eventId);
            return false;
        }

        $tempPdf = '';
        $attachment = $this->resolveReportPdf($eventId, $pdfPath, $tempPdf);

        $registered = (int)($event['registered_count'] ?? 0);
        $capacity = max(1, (int)$event['capacity']);
        $available = max(0, $capacity - $registered);
        $fillPct = (int)round(($registered / $capacity) * 100);
        $dashboardLink = $this->baseUrl() . '/dashboard';

        $html = file_get_contents($template);
        $html = str_replace(
            [
                '{{ORGANIZER_NAME}}',
                '{{EVENT_TITLE}}',
                '{{FILL_PCT}}',
                '{{REGISTERED}}',
                '{{CAPACITY}}',
                '{{AVAILABLE}}',
                '{{DASHBOARD_LINK}}',
                '{{YEAR}}',
            ],
            [
                $this->e($this->organizerName($event)),
                $this->e((string)$event['title']),
                (string)$fillPct,
                (string)$registered,
                (string)$capacity,
                (string)$available,
                $this->e($dashboardLink),
                date('Y'),
            ],
            $html
        );

        try {
            $mail = createMailer();
            $mail->addAddress($organizerEmail, $this->organizerName($event));
            $mail->Subject = 'Alerte capacité 80% - ' . $event['title'];
            $mail->Body    = $html;
            $mail->AltBody = $this->plainText($html);

            if ($attachment !== '' && is_file($attachment)) {
                $mail->addAttachment($attachment, 'rapport_event_' . $eventId . '.pdf');
            }

            $mail->send();
            return true;
        } catch (Throwable $e) {
            logMailError($this->db, 'capacity_alert', $organizerEmail, $e->getMessage(), $eventId);
            return false;
        } finally {
            if ($tempPdf !== '' && is_file($tempPdf)) {
                @unlink($tempPdf);
            }
        }
    }

    private function resolveReportPdf(int $eventId, string $pdfPath, string &$tempPdf): string
    {
        if ($pdfPath !== '' && is_file($pdfPath)) {
            return $pdfPath;
        }

        $pdfControllerFile = __DIR__ . '/PdfController.php';
        if (is_file($pdfControllerFile)) {
            require_once $pdfControllerFile;
            $tempPdf = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'eventhub_report_' . $eventId . '_' . uniqid('', true) . '.pdf';
            try {
                $pdfC = new PdfController();
                $pdfC->generateReportPDFToFile($eventId, $tempPdf);
                return is_file($tempPdf) ? $tempPdf : '';
            } catch (Throwable $e) {
                return '';
            }
        }

        return '';
    }

    private function organizerName(array $event): string
    {
        return !empty($event['organizer_name']) ? (string)$event['organizer_name'] : 'Organisateur';
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);
        return $timestamp ? date('d/m/Y à H:i', $timestamp) : $date;
    }

    private function baseUrl(): string
    {
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
        $scheme = $https ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $script = $_SERVER['SCRIPT_NAME'] ?? '/MVPexam/public/index.php';
        $projectPath = dirname(dirname($script));

        return rtrim($scheme . '://' . $host . str_replace('\\', '/', $projectPath), '/');
    }

    private function plainText(string $html): string
    {
        $text = preg_replace('/<\s*br\s*\/?>/i', "\n", $html);
        $text = preg_replace('/<\/p>/i', "\n\n", $text);
        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8'));
    }
}
