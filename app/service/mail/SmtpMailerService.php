<?php

declare(strict_types=1);

namespace app\service\mail;

use RuntimeException;

final class SmtpMailerService
{
    public function send(array $smtpConfig, string $toEmail, string $subject, string $htmlBody, string $textBody): void
    {
        $payload = [
            'host' => (string) ($smtpConfig['host'] ?? ''),
            'port' => max(1, (int) ($smtpConfig['port'] ?? 25)),
            'username' => (string) ($smtpConfig['username'] ?? ''),
            'password' => (string) ($smtpConfig['password'] ?? ''),
            'encryption' => $this->normalizeEncryption((string) ($smtpConfig['encryption'] ?? '')),
            'from_email' => (string) ($smtpConfig['from_email'] ?? ''),
            'from_name' => (string) ($smtpConfig['from_name'] ?? ''),
            'to_email' => $toEmail,
            'subject' => $subject,
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ];

        $script = <<<'PY'
import json
import smtplib
import ssl
import sys
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.header import Header
from email.utils import formataddr

payload = json.loads(sys.stdin.read())
msg = MIMEMultipart('alternative')
msg['Subject'] = str(Header(payload['subject'], 'utf-8'))
msg['From'] = formataddr((str(Header(payload['from_name'], 'utf-8')), payload['from_email']))
msg['To'] = payload['to_email']
msg.attach(MIMEText(payload['text_body'], 'plain', 'utf-8'))
msg.attach(MIMEText(payload['html_body'], 'html', 'utf-8'))

host = payload['host']
port = int(payload['port'])
encryption = payload['encryption']
username = payload['username']
password = payload['password']

if encryption == 'ssl':
    server = smtplib.SMTP_SSL(host, port, timeout=10, context=ssl.create_default_context())
else:
    server = smtplib.SMTP(host, port, timeout=10)

with server:
    server.ehlo()
    if encryption == 'tls':
        server.starttls(context=ssl.create_default_context())
        server.ehlo()
    if username:
        server.login(username, password)
    server.sendmail(payload['from_email'], [payload['to_email']], msg.as_string())
PY;

        $command = 'python3 -c ' . escapeshellarg($script);
        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];
        $process = proc_open($command, $descriptor, $pipes, root_path());
        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start SMTP mailer process');
        }

        fwrite($pipes[0], json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        if ($exitCode !== 0) {
            $message = trim($stderr) !== '' ? trim($stderr) : (trim($stdout) !== '' ? trim($stdout) : 'SMTP delivery failed');
            throw new RuntimeException($message);
        }
    }

    private function normalizeEncryption(string $value): string
    {
        $value = strtolower(trim($value));

        return match ($value) {
            'ssl' => 'ssl',
            'tls', 'starttls' => 'tls',
            '', 'none' => 'none',
            default => throw new RuntimeException('Unsupported SMTP encryption'),
        };
    }
}
