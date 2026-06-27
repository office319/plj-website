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

$recipient = 'kontakt@polsterreinigungjuelich.de';
$subject = 'Neue Anfrage Polsterreinigung Juelich';
$from = 'kontakt@polsterreinigungjuelich.de';
$safeReplyTo = $email !== '' ? $email : $from;

$bodyLines = [
    'Neue Anfrage ueber polsterreinigungjuelich.de',
    '',
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
            'content' => chunk_split(base64_encode((string)file_get_contents($tmpName))),
        ];
    }
}

$headers = [
    'From: Polsterreinigung Juelich <' . $from . '>',
    'Reply-To: ' . $safeReplyTo,
    'X-Mailer: PHP/' . PHP_VERSION,
];

if ($attachments === []) {
    $headers[] = 'Content-Type: text/plain; charset=UTF-8';
    $sent = mail($recipient, $subject, $textBody, implode("\r\n", $headers));
    finish_with_status($sent ? 'ok' : 'fehler');
}

$boundary = 'plj-' . bin2hex(random_bytes(16));
$headers[] = 'MIME-Version: 1.0';
$headers[] = 'Content-Type: multipart/mixed; boundary="' . $boundary . '"';

$messageParts = [];
$messageParts[] = '--' . $boundary . "\r\n"
    . "Content-Type: text/plain; charset=UTF-8\r\n"
    . "Content-Transfer-Encoding: 8bit\r\n\r\n"
    . $textBody . "\r\n";

foreach ($attachments as $attachment) {
    $messageParts[] = '--' . $boundary . "\r\n"
        . 'Content-Type: ' . $attachment['type'] . '; name="' . $attachment['name'] . '"' . "\r\n"
        . 'Content-Disposition: attachment; filename="' . $attachment['name'] . '"' . "\r\n"
        . "Content-Transfer-Encoding: base64\r\n\r\n"
        . $attachment['content'] . "\r\n";
}

$messageParts[] = '--' . $boundary . "--\r\n";

$sent = mail($recipient, $subject, implode('', $messageParts), implode("\r\n", $headers));
finish_with_status($sent ? 'ok' : 'fehler');
