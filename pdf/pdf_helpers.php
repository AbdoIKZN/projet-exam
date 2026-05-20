<?php
/**
 * Shared PDF helpers for EventHub Pro.
 *
 * Library choice: TCPDF.
 * Justification: Part 3 asks for a server-side bar chart built with PDF drawing
 * primitives. TCPDF exposes direct primitives such as Rect(), Line(), Cell(),
 * SetFillColor() and write2DBarcode(), so it is a better fit than Dompdf for
 * the chart and QR code requirements.
 */

function eventhubLoadTcpdf(): void
{
    if (class_exists('TCPDF')) {
        return;
    }

    eventhubDefineMissingCurlConstants();

    $edsWww = dirname(__DIR__, 2);
    $easyPhpRoot = dirname($edsWww);
    $candidates = [
        __DIR__ . '/../lib/tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php',
        __DIR__ . '/../vendor/autoload.php',
        $edsWww . '/project/lib/tcpdf/tcpdf.php',
        $easyPhpRoot . '/eds-modules/phpmyadmin470x260218075410/vendor/tecnickcom/tcpdf/tcpdf.php',
    ];

    foreach ($candidates as $file) {
        if (is_file($file)) {
            require_once $file;
            if (class_exists('TCPDF')) {
                return;
            }
        }
    }

    throw new RuntimeException('TCPDF introuvable. Ajoutez-le dans lib/tcpdf/ ou vendor/.');
}

function eventhubDefineMissingCurlConstants(): void
{
    // Some EasyPHP builds load TCPDF without the cURL extension. TCPDF references
    // cURL constants while loading, so defining the missing constants keeps API
    // JSON clean instead of leaking PHP notices before the response body.
    $constants = [
        'CURLOPT_CONNECTTIMEOUT' => 78,
        'CURLOPT_MAXREDIRS' => 68,
        'CURLOPT_PROTOCOLS' => 181,
        'CURLPROTO_HTTPS' => 2,
        'CURLPROTO_HTTP' => 1,
        'CURLPROTO_FTP' => 4,
        'CURLPROTO_FTPS' => 8,
        'CURLOPT_SSL_VERIFYHOST' => 81,
        'CURLOPT_SSL_VERIFYPEER' => 64,
        'CURLOPT_TIMEOUT' => 13,
        'CURLOPT_USERAGENT' => 10018,
        'CURLOPT_FAILONERROR' => 45,
        'CURLOPT_RETURNTRANSFER' => 19913,
        'CURLOPT_FOLLOWLOCATION' => 52,
        'CURLOPT_URL' => 10002,
    ];

    foreach ($constants as $name => $value) {
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

function eventhubHexToRgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }

    return [
        hexdec(substr($hex, 0, 2)),
        hexdec(substr($hex, 2, 2)),
        hexdec(substr($hex, 4, 2)),
    ];
}

function eventhubCategoryColors(string $category): array
{
    $colors = [
        'tech'     => ['primary' => '#2563EB', 'light' => '#DBEAFE'],
        'design'   => ['primary' => '#7C3AED', 'light' => '#EDE9FE'],
        'business' => ['primary' => '#EA580C', 'light' => '#FEF3C7'],
        'science'  => ['primary' => '#16A34A', 'light' => '#DCFCE7'],
    ];

    return $colors[$category] ?? ['primary' => '#0F1F3D', 'light' => '#F8FAFC'];
}

function eventhubFormatDate(?string $date): string
{
    if (!$date) {
        return '-';
    }

    $timestamp = strtotime($date);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : $date;
}

function eventhubBaseUrl(): string
{
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/MVPexam/pdf/ticket.php';
    $projectPath = dirname(dirname($script));

    return rtrim($scheme . '://' . $host . str_replace('\\', '/', $projectPath), '/');
}

function eventhubLogoPath(): string
{
    $path = __DIR__ . '/../assets/img/logo.png';
    return is_file($path) ? $path : '';
}

function eventhubOutputPdf(TCPDF $pdf, string $filename, string $output, string $filePath = '')
{
    $output = strtoupper($output);

    if ($output === 'F') {
        if ($filePath === '') {
            $filePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('eventhub_', true) . '_' . $filename;
        }
        $pdf->Output($filePath, 'F');
        return $filePath;
    }

    if ($output === 'S') {
        return $pdf->Output($filename, 'S');
    }

    $pdf->Output($filename, 'D');
    return null;
}

function eventhubDrawLogoOrText(TCPDF $pdf, float $x, float $y, float $w = 34): void
{
    $logo = eventhubLogoPath();
    if ($logo !== '') {
        $pdf->Image($logo, $x, $y, $w, 0, 'PNG');
        return;
    }

    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(15, 31, 61);
    $pdf->SetXY($x, $y + 2);
    $pdf->Cell($w + 30, 8, 'EventHub Pro', 0, 0, 'L');
}
