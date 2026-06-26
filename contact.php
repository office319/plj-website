<?php
declare(strict_types=1);

function redirect_with_status(string $status)
{
    header('Location: /?anfrage=' . rawurlencode($status) . '#kontakt', true, 303);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with_status('fehler');
}

if (!empty($_POST['website'] ?? '')) {
    redirect_with_status('ok');
}

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$phone = trim((string)($_POST['phone'] ?? ''));
$location = trim((string)($_POST['location'] ?? ''));
$service = trim((string)($_POST['service'] ?? ''));
$message = trim((string)($_POST['message'] ?? ''));
$privacy = isset($_POST['privacy']);

if ($name === '' || $location === '' || $service === '' || $message === '' || !$privacy) {
    redirect_with_status('fehler');
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with_status('fehler');
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
            redirect_with_status('fehler');
        }

        $tmpName = (string)($_FILES['photos']['tmp_name'][$i] ?? '');
        $size = (int)($_FILES['photos']['size'][$i] ?? 0);
        if ($tmpName === '' || $size <= 0 || $size > $maxFileSize || !is_uploaded_file($tmpName)) {
            redirect_with_status('fehler');
        }

        $mime = function_exists('mime_content_type') ? (string)mime_content_type($tmpName) : '';
        if (!isset($allowedTypes[$mime])) {
            redirect_with_status('fehler');
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
    redirect_with_status($sent ? 'ok' : 'fehler');
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
redirect_with_status($sent ? 'ok' : 'fehler');
