<?php
declare(strict_types=1);

function wants_json(): bool
{
    $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
    $requestedWith = strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? ''));
    return str_contains($accept, 'application/json') || $requestedWith === 'xmlhttprequest';
}

function status_message(string $status): string
{
    if ($status === 'ok') {
        return 'Danke, Ihre Anfrage wurde gesendet. Wir melden uns schnellstmöglich mit einem Angebot.';
    }
    return 'Die Anfrage konnte nicht gesendet werden. Bitte prüfen Sie Ihre Angaben oder schreiben Sie uns direkt per WhatsApp.';
}

function finish_with_status(string $status, array $extra = []): void
{
    if (wants_json()) {
        $ok = $status === 'ok';
        http_response_code($ok ? 200 : 422);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(array_merge([
            'ok' => $ok,
            'status' => $status,
            'message' => status_message($status),
        ], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }

    redirect_with_status($status);
}

function redirect_with_status(string $status): void
{
    header('Location: /?anfrage=' . rawurlencode($status) . '#kontakt', true, 303);
    exit;
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function service_label(string $service): string
{
    $labels = [
        'sofa' => 'Sofa / Couch / Eckcouch',
        'sessel-stuehle' => 'Sessel / Stühle',
        'auto' => 'Autositze / Innenraum',
        'matratze-zusatz' => 'Matratze als Zusatzleistung',
        'sonstiges' => 'Sonstiges Polster',
    ];
    return $labels[$service] ?? $service;
}

function phone_href(string $phone): string
{
    $trimmed = trim($phone);
    if ($trimmed === '') {
        return '';
    }
    $normalized = preg_replace('/[^0-9+]+/', '', $trimmed) ?? '';
    if (str_starts_with($normalized, '00')) {
        $normalized = '+' . substr($normalized, 2);
    } elseif (str_starts_with($normalized, '0')) {
        $normalized = '+49' . substr($normalized, 1);
    }
    return $normalized !== '' ? 'tel:' . $normalized : '';
}

function whatsapp_href(string $phone): string
{
    $tel = phone_href($phone);
    if ($tel === '') {
        return '';
    }
    $digits = preg_replace('/\D+/', '', substr($tel, 4)) ?? '';
    return $digits !== '' ? 'https://wa.me/' . $digits : '';
}

function mailto_href(string $email, string $subject): string
{
    if ($email === '') {
        return '';
    }
    return 'mailto:' . rawurlencode($email) . '?subject=' . rawurlencode($subject);
}

function action_button(string $href, string $label, string $bg = '#009096'): string
{
    if ($href === '') {
        return '';
    }
    return '<a href="' . esc($href) . '" style="display:inline-block;margin:0 8px 10px 0;padding:13px 18px;border-radius:999px;background:' . esc($bg) . ';color:#ffffff;font:800 14px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-decoration:none;">' . esc($label) . '</a>';
}

function info_row(string $label, string $value, string $href = ''): string
{
    $display = $value !== '' ? $value : '-';
    $content = $href !== ''
        ? '<a href="' . esc($href) . '" style="color:#005f64;text-decoration:none;font-weight:800;">' . esc($display) . '</a>'
        : '<span style="color:#062f32;font-weight:800;">' . esc($display) . '</span>';

    return '<tr>'
        . '<td style="padding:11px 0;border-bottom:1px solid #dcebea;color:#5d7375;font:700 12px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-transform:uppercase;letter-spacing:.03em;">' . esc($label) . '</td>'
        . '<td style="padding:11px 0;border-bottom:1px solid #dcebea;text-align:right;font:700 15px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;">' . $content . '</td>'
        . '</tr>';
}

function build_html_mail(array $data): string
{
    $service = service_label($data['service']);
    $emailHref = mailto_href($data['email'], 'Re: Anfrage Polsterreinigung Juelich');
    $phoneHref = phone_href($data['phone']);
    $whatsappHref = whatsapp_href($data['phone']);
    $photoText = $data['attachment_count'] === 1 ? '1 Foto' : $data['attachment_count'] . ' Fotos';
    $messageHtml = nl2br(esc($data['message']));

    $buttons = action_button($emailHref, 'Per E-Mail antworten')
        . action_button($phoneHref, 'Anrufen', '#174f52')
        . action_button($whatsappHref, 'WhatsApp öffnen', '#25D366');

    if ($buttons === '') {
        $buttons = '<span style="color:#5d7375;font:700 14px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;">Keine direkte Kontaktaktion verfuegbar.</span>';
    }

    return '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>'
        . '<body style="margin:0;padding:0;background:#f2f8f7;color:#062f32;">'
        . '<div style="display:none;max-height:0;overflow:hidden;color:transparent;">Neue Anfrage von ' . esc($data['name']) . ' aus ' . esc($data['location']) . '.</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f2f8f7;margin:0;padding:24px 0;">'
        . '<tr><td align="center" style="padding:0 14px;">'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="width:100%;max-width:680px;background:#ffffff;border:1px solid #dcebea;border-radius:8px;overflow:hidden;box-shadow:0 22px 70px rgba(0,58,61,.14);">'
        . '<tr><td style="padding:26px 28px;background:#003a3d;color:#ffffff;">'
        . '<div style="font:900 13px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-transform:uppercase;letter-spacing:.05em;color:#8de8dc;">Polsterreinigung Juelich</div>'
        . '<h1 style="margin:8px 0 0;font:900 30px/1.08 Georgia,Times New Roman,serif;color:#ffffff;">Neue Anfrage</h1>'
        . '<p style="margin:12px 0 0;font:700 16px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;color:#e4fffb;">' . esc($service) . ' in ' . esc($data['location']) . '</p>'
        . '</td></tr>'
        . '<tr><td style="padding:24px 28px 8px;">'
        . '<div style="display:inline-block;padding:7px 11px;border-radius:999px;background:#dff7f4;color:#005f64;font:900 12px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-transform:uppercase;">' . esc($photoText) . ' angehängt</div>'
        . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:14px;border-collapse:collapse;">'
        . info_row('Name', $data['name'])
        . info_row('E-Mail', $data['email'], $emailHref)
        . info_row('Telefon', $data['phone'], $phoneHref)
        . info_row('Ort', $data['location'])
        . info_row('Leistung', $service)
        . info_row('Zeit', $data['time'])
        . '</table>'
        . '</td></tr>'
        . '<tr><td style="padding:16px 28px 0;">'
        . '<div style="padding:18px 20px;border-radius:8px;background:#f7fbfa;border:1px solid #dcebea;">'
        . '<div style="margin-bottom:8px;color:#5d7375;font:900 12px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-transform:uppercase;letter-spacing:.04em;">Nachricht vom Kunden</div>'
        . '<div style="font:700 16px/1.55 -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;color:#062f32;">' . $messageHtml . '</div>'
        . '</div>'
        . '</td></tr>'
        . '<tr><td style="padding:22px 28px 6px;">' . $buttons . '</td></tr>'
        . '<tr><td style="padding:0 28px 26px;">'
        . '<p style="margin:8px 0 0;color:#5d7375;font:600 13px/1.45 -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;">Datenschutz: Kunde hat der Verarbeitung zur Bearbeitung der Anfrage zugestimmt. Bilder sind nur als Mail-Anhang enthalten und werden nicht lokal auf der Website gespeichert.</p>'
        . '</td></tr>'
        . '</table>'
        . '<p style="max-width:680px;margin:14px 0 0;color:#7c8f91;font:600 12px -apple-system,BlinkMacSystemFont,Segoe UI,Arial,sans-serif;text-align:center;">Automatisch gesendet von polsterreinigungjuelich.de</p>'
        . '</td></tr></table></body></html>';
}

function attachment_log_data(array $attachments): array
{
    return array_map(static function (array $attachment): array {
        return [
            'name' => $attachment['name'] ?? '',
            'type' => $attachment['type'] ?? '',
            'size' => $attachment['size'] ?? 0,
        ];
    }, $attachments);
}

function attachment_relay_data(array $attachments): array
{
    return array_map(static function (array $attachment): array {
        return [
            'name' => $attachment['name'] ?? '',
            'type' => $attachment['type'] ?? '',
            'size' => $attachment['size'] ?? 0,
            'content_base64' => preg_replace('/\s+/', '', (string)($attachment['content'] ?? '')),
        ];
    }, $attachments);
}

function append_lead_archive(array $data, array $attachments, array $delivery): void
{
    $archiveDir = dirname(__DIR__) . '/lead_logs';
    if (!is_dir($archiveDir) && !mkdir($archiveDir, 0750, true) && !is_dir($archiveDir)) {
        error_log('PLJ lead archive: could not create directory');
        return;
    }

    $record = [
        'created_at' => date('c'),
        'source' => 'polsterreinigungjuelich.de/contact.php',
        'status' => 'received',
        'data' => [
            'lead_id' => $data['lead_id'] ?? '',
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'location' => $data['location'],
            'service' => $data['service'],
            'message' => $data['message'],
            'attachment_count' => $data['attachment_count'],
        ],
        'attachments' => attachment_log_data($attachments),
        'delivery' => $delivery,
        'request' => [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        ],
    ];

    $encoded = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($encoded === false) {
        error_log('PLJ lead archive: json encode failed');
        return;
    }

    $archiveFile = $archiveDir . '/leads.jsonl';
    $written = file_put_contents($archiveFile, $encoded . "\n", FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('PLJ lead archive: write failed');
        return;
    }

    @chmod($archiveFile, 0640);
}

function make_lead_id(): string
{
    try {
        $suffix = substr(bin2hex(random_bytes(4)), 0, 6);
    } catch (Throwable $error) {
        $suffix = substr(hash('sha256', microtime(true) . ($_SERVER['REMOTE_ADDR'] ?? '')), 0, 6);
    }

    return 'PLJ-' . gmdate('Ymd-His') . '-' . $suffix;
}

function relay_token(): string
{
    $path = dirname(__DIR__) . '/lead_logs/relay_token.txt';
    if (!is_readable($path)) {
        return '';
    }
    return trim((string)file_get_contents($path));
}

function post_relay(array $payload): array
{
    $token = relay_token();
    if ($token === '') {
        return [
            'transport' => 'app_naszefirmy_smtp_relay',
            'ok' => false,
            'status' => 0,
            'error' => 'relay_token_missing',
        ];
    }

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        return [
            'transport' => 'app_naszefirmy_smtp_relay',
            'ok' => false,
            'status' => 0,
            'error' => 'relay_json_failed',
        ];
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\nAccept: application/json\r\nX-NF-Relay-Token: " . $token . "\r\n",
            'content' => $json,
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);

    $body = @file_get_contents('https://app.naszefirmy.de/polsterreinigung-juelich/lead', false, $context);
    $status = 0;
    foreach (($http_response_header ?? []) as $header) {
        if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches)) {
            $status = (int)$matches[1];
            break;
        }
    }

    $decoded = is_string($body) ? json_decode($body, true) : null;
    $ok = $status >= 200 && $status < 300 && is_array($decoded) && !empty($decoded['ok']);

    return [
        'transport' => 'app_naszefirmy_smtp_relay',
        'ok' => $ok,
        'status' => $status,
        'error' => is_array($decoded) && isset($decoded['error']) ? (string)$decoded['error'] : '',
        'lead_id' => is_array($decoded) && isset($decoded['lead_id']) ? (string)$decoded['lead_id'] : '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finish_with_status('fehler');
}

if (!empty($_POST['website'] ?? '')) {
    finish_with_status('ok');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$location = trim((string)($_POST['location'] ?? ''));
$service = trim((string)($_POST['service'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$privacy = isset($_POST['privacy']);

if ($name === '' || $location === '' || $service === '' || $message === '' || !$privacy) {
    finish_with_status('fehler');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    finish_with_status('fehler');
}

$isTestMode = !empty($_POST['is_test']) || (($_SERVER['HTTP_X_NF_FORMS_TEST'] ?? '') === '1');
if ($isTestMode) {
    finish_with_status('ok', [
        'test_mode' => true,
        'side_effects_skipped' => true,
    ]);
}

$subject = 'Neue Anfrage Polsterreinigung Juelich';
$from = 'kontakt@polsterreinigungjuelich.de';
$safeReplyTo = $email !== '' ? $email : $from;
$leadId = make_lead_id();

$bodyLines = [
    'Neue Anfrage ueber polsterreinigungjuelich.de',
    '',
    'Lead ID: ' . $leadId,
    'Name: ' . $name,
    'E-Mail: ' . ($email !== '' ? $email : '-'),
    'Telefon: ' . ($phone !== '' ? $phone : '-'),
    'Ort: ' . $location,
    'Leistung: ' . $service,
    '',
    'Nachricht:',
    $message,
    '',
    'Datenschutz: zugestimmt',
    'Zeit: ' . date('c'),
];
$textBody = implode("\r\n", $bodyLines);

$attachments = [];
$allowedTypes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
];
$maxFileSize = 5 * 1024 * 1024;
$maxFiles = 4;

if (!empty($_FILES['photos']) && is_array($_FILES['photos']['name'])) {
    $fileCount = min(count($_FILES['photos']['name']), $maxFiles);
    for ($i = 0; $i < $fileCount; $i++) {
        $error = (int)($_FILES['photos']['error'][$i] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            finish_with_status('fehler');
        }

        $tmpName = (string)($_FILES['photos']['tmp_name'][$i] ?? '');
        $size = (int)($_FILES['photos']['size'][$i] ?? 0);
        if ($tmpName === '' || $size <= 0 || $size > $maxFileSize || !is_uploaded_file($tmpName)) {
            finish_with_status('fehler');
        }

        $mime = function_exists('mime_content_type') ? (string)mime_content_type($tmpName) : '';
        if (!isset($allowedTypes[$mime])) {
            finish_with_status('fehler');
        }

        $originalName = (string)($_FILES['photos']['name'][$i] ?? ('foto-' . ($i + 1) . '.' . $allowedTypes[$mime]));
        $baseName = preg_replace('/[^a-zA-Z0-9._-]+/', '-', basename($originalName));
        if ($baseName === '' || $baseName === '.' || $baseName === '..') {
            $baseName = 'foto-' . ($i + 1) . '.' . $allowedTypes[$mime];
        }

        $attachments[] = [
            'name' => $baseName,
            'type' => $mime,
            'size' => $size,
            'content' => chunk_split(base64_encode((string)file_get_contents($tmpName))),
        ];
    }
}

$mailData = [
    'lead_id' => $leadId,
    'name' => $name,
    'email' => $email,
    'phone' => $phone,
    'location' => $location,
    'service' => $service,
    'message' => $message,
    'attachment_count' => count($attachments),
    'time' => date('d.m.Y H:i'),
];
$htmlBody = build_html_mail($mailData);

$relay = post_relay([
    'lead_id' => $leadId,
    'submitted_at' => date('c'),
    'subject' => $subject,
    'plain' => $textBody,
    'html' => $htmlBody,
    'reply_to' => $safeReplyTo,
    'consent' => $privacy ? '1' : '0',
    'website' => (string)($_POST['website'] ?? ''),
    'data' => [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'location' => $location,
        'service' => $service,
        'message' => $message,
    ],
    'attachments' => attachment_relay_data($attachments),
]);

append_lead_archive($mailData, $attachments, [$relay]);
finish_with_status(!empty($relay['ok']) ? 'ok' : 'fehler', [
    'lead_id' => $leadId,
    'mail_transport' => 'app_naszefirmy_smtp_relay',
]);
