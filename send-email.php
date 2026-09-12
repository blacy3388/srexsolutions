<?php
declare(strict_types=1);
session_start();

function finish(string $result): never {
    header('Location: contact.html?form=' . rawurlencode($result), true, 303);
    exit;
}
function smtpRead($socket, array $expected): void {
    $response = '';
    do {
        $line = fgets($socket, 515);
        if ($line === false) throw new RuntimeException('SMTP stopped responding');
        $response .= $line;
    } while (isset($line[3]) && $line[3] === '-');
    if (!in_array((int) substr($response, 0, 3), $expected, true)) {
        throw new RuntimeException('Unexpected SMTP response ' . substr($response, 0, 3));
    }
}
function smtpSend($socket, string $command, array $expected): void {
    if (fwrite($socket, $command . "\r\n") === false) throw new RuntimeException('SMTP write failed');
    smtpRead($socket, $expected);
}
function headerValue(string $value): string {
    return trim(str_replace(["\r", "\n"], '', $value));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed');
}
if (trim((string) ($_POST['website'] ?? '')) !== '') finish('sent');
$started = filter_input(INPUT_POST, 'form_started', FILTER_VALIDATE_INT);
if (!$started || ((int) (microtime(true) * 1000) - $started) < 1500) finish('error');
if ((int) ($_SESSION['srex_last_email'] ?? 0) > time() - 30) finish('error');

$name = headerValue((string) ($_POST['name'] ?? ''));
$email = headerValue((string) ($_POST['email'] ?? ''));
$phone = headerValue((string) ($_POST['phone'] ?? ''));
$property = headerValue((string) ($_POST['property'] ?? ''));
$service = headerValue((string) ($_POST['service'] ?? ''));
$message = trim((string) ($_POST['message'] ?? ''));
if ($name === '' || strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($message) > 5000) finish('error');

$configPath = __DIR__ . '/mail-config.php';
if (!is_file($configPath)) {
    error_log('SREX mail: mail-config.php is missing');
    finish('error');
}
$config = require $configPath;
foreach (['host', 'port', 'username', 'password', 'from_email', 'from_name', 'to_email'] as $key) {
    if (empty($config[$key]) || $config[$key] === 'REPLACE_WITH_SMTP_PASSWORD') finish('error');
}

$subject = 'New website enquiry: ' . ($service ?: 'General enquiry');
$body = "A new enquiry was submitted on srexsolutions.com.\r\n\r\n"
    . "Name: $name\r\nEmail: $email\r\nPhone: $phone\r\nProperty: $property\r\nService: $service\r\n\r\nProject details:\r\n$message\r\n";

try {
    $socket = stream_socket_client('tcp://' . $config['host'] . ':' . (int) $config['port'], $number, $error, 15);
    if ($socket === false) throw new RuntimeException('SMTP connection failed');
    stream_set_timeout($socket, 15);
    smtpRead($socket, [220]);
    $hello = gethostname() ?: 'srexsolutions.com';
    smtpSend($socket, 'EHLO ' . $hello, [250]);
    smtpSend($socket, 'STARTTLS', [220]);
    if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) throw new RuntimeException('TLS failed');
    smtpSend($socket, 'EHLO ' . $hello, [250]);
    smtpSend($socket, 'AUTH LOGIN', [334]);
    smtpSend($socket, base64_encode((string) $config['username']), [334]);
    smtpSend($socket, base64_encode((string) $config['password']), [235]);
    smtpSend($socket, 'MAIL FROM:<' . headerValue((string) $config['from_email']) . '>', [250]);
    smtpSend($socket, 'RCPT TO:<' . headerValue((string) $config['to_email']) . '>', [250, 251]);
    smtpSend($socket, 'DATA', [354]);
    $headers = [
        'From: ' . headerValue((string) $config['from_name']) . ' <' . headerValue((string) $config['from_email']) . '>',
        'Reply-To: ' . $email,
        'To: <' . headerValue((string) $config['to_email']) . '>',
        'Subject: ' . headerValue($subject),
        'Date: ' . date(DATE_RFC2822),
        'Message-ID: <' . bin2hex(random_bytes(16)) . '@srexsolutions.com>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
    ];
    $payload = implode("\r\n", $headers) . "\r\n\r\n" . preg_replace('/^\./m', '..', $body) . "\r\n.";
    smtpSend($socket, $payload, [250]);
    smtpSend($socket, 'QUIT', [221]);
    fclose($socket);
    $_SESSION['srex_last_email'] = time();
    finish('sent');
} catch (Throwable $exception) {
    error_log('SREX SMTP error: ' . $exception->getMessage());
    if (isset($socket) && is_resource($socket)) fclose($socket);
    finish('error');
}
