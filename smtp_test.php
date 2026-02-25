<?php

require_once 'vendor/autoload.php';

use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mime\Email;

// Load .env
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[$name] = trim($value, '"\'');
    }
}

$dsn = $_ENV['MAILER_DSN'] ?? null;
echo "Testing Primary DSN: $dsn\n";

// Also test a forced gmail+smtp format
$altDsn = "gmail+smtp://ecospot076%40gmail.com:lobuvbdyezvifsco@default";
echo "Testing Alternate DSN: $altDsn\n";

function testDsn($dsn) {
    try {
        $transport = Transport::fromDsn($dsn);
        $mailer = new Mailer($transport);

        $email = (new Email())
            ->from('ecospot076@gmail.com')
            ->to('ecospot076@gmail.com')
            ->subject('Direct SMTP Test')
            ->text('Testing DSN: ' . $dsn);

        $mailer->send($email);
        echo "SUCCESS for DSN: $dsn\n";
        return true;
    } catch (\Exception $e) {
        echo "FAILURE for DSN: $dsn\n";
        echo "Error: " . $e->getMessage() . "\n";
        return false;
    }
}

testDsn($dsn);
testDsn($altDsn);
exit;
