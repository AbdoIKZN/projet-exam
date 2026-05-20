<?php
/**
 * EventHub Pro — app/Controllers/PdfController.php
 * Gère la génération et le téléchargement des tickets et rapports PDF.
 */

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../Models/EventModel.php';
require_once __DIR__ . '/../Models/RegistrationModel.php';
require_once __DIR__ . '/../../pdf/pdf_helpers.php';

// Load TCPDF at file-scope so that EventHubReportPDF extends TCPDF can compile successfully
eventhubLoadTcpdf();

class PdfController extends Controller
{
    private $eventModel;
    private $registrationModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->registrationModel = new RegistrationModel();
    }

    /**
     * Génère et télécharge le ticket PDF pour l'inscription courante.
     * Route: GET /pdf/ticket
     */
    public function downloadTicket(): void
    {
        $registrationId = isset($_GET['registration_id']) ? (int)$_GET['registration_id'] : 0;
        $token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';

        if ($registrationId <= 0 || $token === '') {
            http_response_code(400);
            echo 'Paramètres manquants ou invalides.';
            exit;
        }

        try {
            $this->generateTicketPDFToOutput($registrationId, $token, 'D');
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Erreur génération ticket : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Génère et télécharge le rapport de gestion PDF pour un événement.
     * Route: GET /pdf/report
     */
    public function downloadReport(): void
    {
        $this->requireOrganizer();

        $eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
        if ($eventId <= 0) {
            http_response_code(400);
            echo 'Paramètre event_id manquant ou invalide.';
            exit;
        }

        try {
            $this->generateReportPDFToOutput($eventId, 'D');
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Erreur génération rapport : ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
        }
    }

    /**
     * Génère un fichier ticket PDF temporaire sur le disque.
     */
    public function generateTicketPDFToFile(int $registrationId, string $token, string $filePath): void
    {
        $this->generateTicketPDFToOutput($registrationId, $token, 'F', $filePath);
    }

    /**
     * Génère un fichier rapport PDF temporaire sur le disque.
     */
    public function generateReportPDFToFile(int $eventId, string $filePath): void
    {
        $this->generateReportPDFToOutput($eventId, 'F', $filePath);
    }

    private function generateTicketPDFToOutput(int $registrationId, string $token, string $output, string $filePath = '')
    {
        eventhubLoadTcpdf();

        $data = $this->registrationModel->getTicketData($registrationId, $token);

        if (!$data) {
            throw new RuntimeException('Inscription introuvable ou token invalide.');
        }

        $colors = eventhubCategoryColors((string)$data['category']);
        [$pr, $pg, $pb] = eventhubHexToRgb($colors['primary']);
        [$lr, $lg, $lb] = eventhubHexToRgb($colors['light']);

        $baseUrl = $this->baseUrl();
        $ticketLink = $baseUrl . '/pdf/ticket?registration_id=' . (int)$data['registration_id'] . '&token=' . urlencode($token);
        $unsubscribeLink = $baseUrl . '/events/unsubscribe?token=' . urlencode($token);
        $qrUserId = $data['user_id'] ?: 'guest-' . (int)$data['registration_id'];
        $qrData = (int)$data['event_id'] . '|' . $qrUserId . '|' . $token;

        $pdf = new TCPDF('L', 'mm', 'A5', true, 'UTF-8', false);
        $pdf->SetCreator('EventHub Pro');
        $pdf->SetAuthor('ENSA Marrakech');
        $pdf->SetTitle('Ticket - ' . $data['title']);
        $pdf->SetSubject('Ticket inscription EventHub Pro');
        $pdf->SetKeywords('EventHub, ticket, QR code');
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->SetMargins(9, 9, 9);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->AddPage();

        // Fond et bandeau categorie.
        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, 0, 210, 148, 'F');
        $pdf->SetFillColor($pr, $pg, $pb);
        $pdf->Rect(0, 0, 210, 18, 'F');

        // Filigrane creatif.
        $pdf->SetAlpha(0.07);
        $pdf->SetFont('helvetica', 'B', 54);
        $pdf->SetTextColor($pr, $pg, $pb);
        $pdf->StartTransform();
        $pdf->Rotate(18, 110, 75);
        $pdf->Text(44, 73, 'EVENTHUB');
        $pdf->StopTransform();
        $pdf->SetAlpha(1);

        eventhubDrawLogoOrText($pdf, 14, 24, 36);

        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 13);
        $pdf->SetXY(14, 5);
        $pdf->Cell(110, 8, strtoupper((string)$data['category']), 0, 0, 'L');
        $pdf->SetXY(138, 5);
        $pdf->Cell(58, 8, 'TICKET N ' . str_pad((string)$data['registration_id'], 5, '0', STR_PAD_LEFT), 0, 0, 'R');

        // Zone principale.
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect(12, 38, 186, 84, 4, '1111', 'DF');
        $pdf->SetFillColor($lr, $lg, $lb);
        $pdf->RoundedRect(16, 44, 116, 36, 3, '1111', 'F');

        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 17);
        $pdf->SetXY(21, 49);
        $pdf->MultiCell(104, 8, (string)$data['title'], 0, 'L');

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetXY(21, 70);
        $pdf->Cell(104, 5, 'Date : ' . eventhubFormatDate((string)$data['event_date']), 0, 1);
        $pdf->SetX(21);
        $pdf->Cell(104, 5, 'Lieu : ' . (string)$data['location'], 0, 1);
        $pdf->SetX(21);
        $pdf->Cell(104, 5, 'Capacite : ' . (int)$data['registered_count'] . ' / ' . (int)$data['capacity'], 0, 1);

        // Bloc QR Code.
        $pdf->SetDrawColor($pr, $pg, $pb);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->RoundedRect(144, 44, 38, 38, 3, '1111', 'DF');
        $pdf->write2DBarcode($qrData, 'QRCODE,M', 148, 48, 30, 30, [
            'border' => 0,
            'padding' => 0,
            'fgcolor' => [15, 31, 61],
            'bgcolor' => false,
        ], 'N');
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(139, 84);
        $pdf->Cell(48, 4, 'Scan validation entree', 0, 0, 'C');

        // Participant.
        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 11);
        $pdf->SetXY(20, 91);
        $pdf->Cell(35, 6, 'Participant', 0, 0);
        $pdf->SetFont('helvetica', '', 11);
        $pdf->Cell(83, 6, (string)$data['name'], 0, 1);

        $pdf->SetX(20);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 5, 'Email', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(83, 5, (string)$data['email'], 0, 1);

        $pdf->SetX(20);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(35, 5, 'Inscrit le', 0, 0);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(83, 5, eventhubFormatDate((string)$data['registered_at']), 0, 1);

        // Bande decorative detachable.
        $pdf->SetFillColor($pr, $pg, $pb);
        $pdf->RoundedRect(142, 93, 42, 20, 2, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetXY(146, 98);
        $pdf->Cell(34, 5, 'PASS UNIQUE', 0, 2, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(34, 4, '#' . str_pad((string)$data['registration_id'], 5, '0', STR_PAD_LEFT), 0, 0, 'C');

        // Liens.
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->Line(15, 126, 195, 126);
        $pdf->SetFont('helvetica', '', 7.5);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->SetXY(15, 129);
        $pdf->MultiCell(180, 4, 'Ticket : ' . $ticketLink, 0, 'L');
        $pdf->SetX(15);
        $pdf->MultiCell(180, 4, 'Desinscription : ' . $unsubscribeLink, 0, 'L');

        return eventhubOutputPdf($pdf, 'ticket_' . (int)$data['registration_id'] . '.pdf', $output, $filePath);
    }

    private function generateReportPDFToOutput(int $eventId, string $output, string $filePath = '')
    {
        eventhubLoadTcpdf();


        $event = $this->eventModel->getById($eventId);

        if (!$event) {
            throw new RuntimeException('Événement introuvable.');
        }

        $event['registered_count'] = (int)$event['registered_count'];
        $event['capacity'] = max(1, (int)$event['capacity']);
        $event['available_places'] = max(0, $event['capacity'] - $event['registered_count']);
        $event['fill_pct'] = (int)round(($event['registered_count'] / $event['capacity']) * 100);

        $registrations = $this->registrationModel->getConfirmedList($eventId);
        $statsByDay = $this->buildSevenDayStats($this->registrationModel->getDailyStats($eventId));

        $colors = eventhubCategoryColors((string)$event['category']);
        [$pr, $pg, $pb] = eventhubHexToRgb($colors['primary']);
        [$lr, $lg, $lb] = eventhubHexToRgb($colors['light']);

        $pdf = new EventHubReportPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->reportTitle = 'Rapport - ' . $event['title'];
        $pdf->SetCreator('EventHub Pro');
        $pdf->SetAuthor('ENSA Marrakech');
        $pdf->SetTitle('Rapport - ' . $event['title']);
        $pdf->SetMargins(12, 28, 12);
        $pdf->SetHeaderMargin(7);
        $pdf->SetFooterMargin(12);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->setFontSubsetting(true);

        $this->drawExecutiveSummary($pdf, $event, [$pr, $pg, $pb], [$lr, $lg, $lb]);
        $this->drawRegistrationTable($pdf, $registrations, [$pr, $pg, $pb]);
        $this->drawStatsChart($pdf, $event, $statsByDay, [$pr, $pg, $pb], [$lr, $lg, $lb]);

        return eventhubOutputPdf($pdf, 'rapport_event_' . $eventId . '.pdf', $output, $filePath);
    }

    private function drawExecutiveSummary(TCPDF $pdf, array $event, array $primary, array $light): void
    {
        [$pr, $pg, $pb] = $primary;
        [$lr, $lg, $lb] = $light;

        $pdf->AddPage();
        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 22);
        $pdf->Cell(0, 10, 'Résumé exécutif', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->MultiCell(0, 6, 'Vue synthétique de la performance de l\'événement et de son remplissage.', 0, 'L');

        $pdf->Ln(5);
        $pdf->SetFillColor($lr, $lg, $lb);
        $pdf->SetDrawColor(226, 232, 240);
        $pdf->RoundedRect(12, 48, 186, 48, 4, '1111', 'DF');

        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->SetXY(20, 56);
        $pdf->MultiCell(120, 8, (string)$event['title'], 0, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetX(20);
        $pdf->Cell(120, 6, 'Date : ' . eventhubFormatDate((string)$event['event_date']), 0, 1);
        $pdf->SetX(20);
        $pdf->Cell(120, 6, 'Lieu : ' . (string)$event['location'], 0, 1);
        $pdf->SetX(20);
        $pdf->Cell(120, 6, 'Organisateur : ' . (($event['organizer_name'] ?? '') ?: $event['organizer_email']), 0, 1);

        $pdf->SetFillColor($pr, $pg, $pb);
        $pdf->RoundedRect(152, 56, 32, 28, 3, '1111', 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 20);
        $pdf->SetXY(152, 61);
        $pdf->Cell(32, 8, $event['fill_pct'] . '%', 0, 2, 'C');
        $pdf->SetFont('helvetica', '', 8);
        $pdf->Cell(32, 5, 'remplissage', 0, 0, 'C');

        $stats = [
            ['Capacité', (string)$event['capacity']],
            ['Inscrits', (string)$event['registered_count']],
            ['Places dispo', (string)$event['available_places']],
            ['Revenus', '0 MAD'],
        ];

        $x = 12;
        $y = 112;
        foreach ($stats as $stat) {
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->RoundedRect($x, $y, 43, 28, 3, '1111', 'DF');
            $pdf->SetTextColor($pr, $pg, $pb);
            $pdf->SetFont('helvetica', 'B', 17);
            $pdf->SetXY($x, $y + 6);
            $pdf->Cell(43, 7, $stat[1], 0, 2, 'C');
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->Cell(43, 5, $stat[0], 0, 0, 'C');
            $x += 47;
        }

        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(12, 156);
        $pdf->MultiCell(186, 6, 'Note revenus : le MVP ne contient pas encore de champ prix. Le rapport affiche donc 0 MAD par défaut et reste compatible avec un futur champ payant.', 0, 'L');
    }

    private function drawRegistrationTable(TCPDF $pdf, array $registrations, array $primary): void
    {
        $pdf->AddPage();
        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 19);
        $pdf->Cell(0, 9, 'Liste des inscrits', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 6, 'Tri alphabétique par nom. Le tableau se poursuit automatiquement sur plusieurs pages.', 0, 1);
        $pdf->Ln(4);

        $this->drawTableHeader($pdf, $primary);

        if (!$registrations) {
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->Cell(0, 10, 'Aucun inscrit confirmé pour le moment.', 1, 1, 'C');
            return;
        }

        $i = 1;
        foreach ($registrations as $row) {
            if ($pdf->GetY() > 260) {
                $pdf->AddPage();
                $this->drawTableHeader($pdf, $primary);
            }

            $fill = $i % 2 === 0;
            $pdf->SetFillColor($fill ? 248 : 255, $fill ? 250 : 255, $fill ? 252 : 255);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('helvetica', '', 8.5);
            $pdf->Cell(12, 8, (string)$i, 1, 0, 'C', $fill);
            $pdf->Cell(48, 8, $this->truncateText((string)$row['name'], 28), 1, 0, 'L', $fill);
            $pdf->Cell(78, 8, $this->truncateText((string)$row['email'], 43), 1, 0, 'L', $fill);
            $pdf->Cell(38, 8, eventhubFormatDate((string)$row['registered_at']), 1, 1, 'L', $fill);
            $i++;
        }
    }

    private function drawTableHeader(TCPDF $pdf, array $primary): void
    {
        [$pr, $pg, $pb] = $primary;
        $pdf->SetFillColor($pr, $pg, $pb);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('helvetica', 'B', 8.5);
        $pdf->Cell(12, 8, 'N°', 1, 0, 'C', true);
        $pdf->Cell(48, 8, 'Nom', 1, 0, 'L', true);
        $pdf->Cell(78, 8, 'Email', 1, 0, 'L', true);
        $pdf->Cell(38, 8, 'Inscription', 1, 1, 'L', true);
    }

    private function drawStatsChart(TCPDF $pdf, array $event, array $statsByDay, array $primary, array $light): void
    {
        [$pr, $pg, $pb] = $primary;
        [$lr, $lg, $lb] = $light;

        $pdf->AddPage();
        $pdf->SetTextColor(15, 31, 61);
        $pdf->SetFont('helvetica', 'B', 19);
        $pdf->Cell(0, 9, 'Statistiques visuelles', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetTextColor(100, 116, 139);
        $pdf->Cell(0, 6, 'Inscriptions par jour sur les 7 derniers jours, dessinées avec les primitives TCPDF.', 0, 1);

        $originX = 30;
        $originY = 220;
        $chartH = 92;
        $chartW = 150;
        $barGap = 5;
        $barW = ($chartW - ($barGap * 6)) / 7;
        $maxCount = max(1, max(array_column($statsByDay, 'count')));

        $pdf->SetDrawColor(148, 163, 184);
        $pdf->Line($originX, $originY - $chartH, $originX, $originY);
        $pdf->Line($originX, $originY, $originX + $chartW + 8, $originY);

        $pdf->SetFont('helvetica', '', 7);
        for ($step = 0; $step <= 4; $step++) {
            $value = (int)round(($maxCount / 4) * $step);
            $y = $originY - (($chartH / 4) * $step);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Line($originX, $y, $originX + $chartW, $y);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY(12, $y - 3);
            $pdf->Cell(14, 5, (string)$value, 0, 0, 'R');
        }

        foreach ($statsByDay as $i => $row) {
            $count = (int)$row['count'];
            $barH = $count > 0 ? ($count / $maxCount) * $chartH : 1;
            $x = $originX + $i * ($barW + $barGap);
            $y = $originY - $barH;

            $pdf->SetFillColor($pr, $pg, $pb);
            $pdf->Rect($x, $y, $barW, $barH, 'F');
            $pdf->SetFillColor($lr, $lg, $lb);
            if ($count === 0) {
                $pdf->Rect($x, $originY - 1, $barW, 1, 'F');
            }

            $pdf->SetTextColor(15, 31, 61);
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($x, $y - 6);
            $pdf->Cell($barW, 5, (string)$count, 0, 0, 'C');

            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetFont('helvetica', '', 7);
            $pdf->SetXY($x, $originY + 3);
            $pdf->Cell($barW, 5, date('d/m', strtotime((string)$row['day'])), 0, 0, 'C');
        }

        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetXY(30, 238);
        $pdf->MultiCell(150, 6, 'Synthèse : ' . (int)$event['registered_count'] . ' inscrit(s), ' . (int)$event['available_places'] . ' place(s) disponible(s), taux de remplissage ' . (int)$event['fill_pct'] . '%.', 0, 'L');
    }

    private function buildSevenDayStats(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string)$row['day']] = (int)$row['count'];
        }

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime('-' . $i . ' day'));
            $result[] = [
                'day' => $day,
                'count' => $counts[$day] ?? 0,
            ];
        }

        return $result;
    }

    private function truncateText(string $value, int $max): string
    {
        return strlen($value) > $max ? substr($value, 0, $max - 3) . '...' : $value;
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
}

/**
 * EventHubReportPDF Class helper for custom TCPDF headers/footers.
 */
if (!class_exists('EventHubReportPDF', false)) {
    class EventHubReportPDF extends TCPDF
    {
        public $reportTitle = 'Rapport EventHub Pro';

        public function Header()
        {
            eventhubDrawLogoOrText($this, 12, 7, 28);
            $this->SetFont('helvetica', 'B', 11);
            $this->SetTextColor(15, 31, 61);
            $this->SetXY(92, 9);
            $this->Cell(106, 6, $this->reportTitle, 0, 0, 'R');
            $this->SetDrawColor(226, 232, 240);
            $this->Line(12, 22, 198, 22);
        }

        public function Footer()
        {
            $this->SetY(-13);
            $this->SetDrawColor(226, 232, 240);
            $this->Line(12, 284, 198, 284);
            $this->SetFont('helvetica', '', 8);
            $this->SetTextColor(100, 116, 139);
            $this->Cell(93, 6, 'Généré le ' . date('d/m/Y H:i'), 0, 0, 'L');
            $this->Cell(93, 6, 'Page ' . $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages(), 0, 0, 'R');
        }
    }
}
