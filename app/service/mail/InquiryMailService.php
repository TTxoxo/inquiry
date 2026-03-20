<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\EmailSendLog;
use RuntimeException;

final class InquiryMailService
{
    public function __construct(
        private readonly SmtpConfigService $smtpConfigService = new SmtpConfigService(),
        private readonly NotifyEmailService $notifyEmailService = new NotifyEmailService(),
        private readonly EmailSendLog $emailSendLogModel = new EmailSendLog(),
        private readonly SmtpMailerService $smtpMailerService = new SmtpMailerService()
    ) {
    }

    public function sendInquiryNotification(array $inquiry, array $site, array $payload): array
    {
        $siteId = (int) $site['id'];
        $smtpConfig = $this->smtpConfigService->getActiveBySiteId($siteId);
        $recipients = $this->notifyEmailService->getActiveBySiteId($siteId);
        $subject = sprintf('[%s] New Inquiry #%d', (string) $site['name'], (int) $inquiry['id']);
        [$htmlBody, $textBody] = $this->buildInquiryBodies($site, $inquiry, $payload);

        if ($recipients === []) {
            return ['sent' => 0, 'failed' => 0, 'logs' => []];
        }

        $logs = [];
        foreach ($recipients as $recipient) {
            try {
                if ($smtpConfig === null) {
                    throw new RuntimeException('SMTP config unavailable');
                }
                $this->smtpMailerService->send($smtpConfig, $recipient, $subject, $htmlBody, $textBody);
                $logs[] = $this->createSendLog([
                    'site_id' => $siteId,
                    'inquiry_id' => (int) $inquiry['id'],
                    'to_email' => $recipient,
                    'subject' => $subject,
                    'status' => 1,
                    'send_status' => 1,
                    'retry_count' => 0,
                    'next_retry_at' => null,
                    'body_html' => $htmlBody,
                    'body_text' => $textBody,
                    'error_message' => '',
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
            } catch (RuntimeException $exception) {
                $logs[] = $this->createSendLog([
                    'site_id' => $siteId,
                    'inquiry_id' => (int) $inquiry['id'],
                    'to_email' => $recipient,
                    'subject' => $subject,
                    'status' => 0,
                    'send_status' => 2,
                    'retry_count' => 0,
                    'next_retry_at' => date('Y-m-d H:i:s', strtotime('+5 minutes')),
                    'body_html' => $htmlBody,
                    'body_text' => $textBody,
                    'error_message' => $exception->getMessage(),
                    'sent_at' => null,
                ]);
            }
        }

        return [
            'sent' => count(array_filter($logs, static fn (array $log): bool => (int) $log['send_status'] === 1)),
            'failed' => count(array_filter($logs, static fn (array $log): bool => (int) $log['send_status'] !== 1)),
            'logs' => $logs,
            'smtp_configured' => $smtpConfig !== null,
            'payload_snapshot' => $payload,
        ];
    }

    public function sendSmtpTest(array $smtpConfig, string $toEmail): array
    {
        $subject = sprintf('[SMTP Test] %s', (string) ($smtpConfig['from_name'] ?? 'Inquiry'));
        $htmlBody = '<h1>SMTP Test</h1><p>This is a test email from the inquiry project.</p>';
        $textBody = "SMTP Test\n\nThis is a test email from the inquiry project.";
        $status = 1;
        $errorMessage = '';
        $sentAt = date('Y-m-d H:i:s');

        try {
            $this->smtpMailerService->send($smtpConfig, $toEmail, $subject, $htmlBody, $textBody);
            $this->writeApplicationLog('smtp_test_send_success', [
                'site_id' => (int) ($smtpConfig['site_id'] ?? 0),
                'to_email' => $toEmail,
                'subject' => $subject,
            ]);
        } catch (RuntimeException $exception) {
            $status = 0;
            $errorMessage = $exception->getMessage();
            $sentAt = null;
            $this->writeApplicationLog('smtp_test_send_failure', [
                'site_id' => (int) ($smtpConfig['site_id'] ?? 0),
                'to_email' => $toEmail,
                'subject' => $subject,
                'error_message' => $errorMessage,
            ]);
            throw $exception;
        }

        return [
            'site_id' => (int) ($smtpConfig['site_id'] ?? 0),
            'to_email' => $toEmail,
            'subject' => $subject,
            'status' => $status,
            'sent_at' => $sentAt,
            'error_message' => $errorMessage,
        ];
    }

    private function createSendLog(array $data): array
    {
        $id = $this->emailSendLogModel->insert([
            'site_id' => (int) $data['site_id'],
            'inquiry_id' => $data['inquiry_id'],
            'to_email' => (string) $data['to_email'],
            'subject' => (string) $data['subject'],
            'body_html' => (string) ($data['body_html'] ?? ''),
            'body_text' => (string) ($data['body_text'] ?? ''),
            'status' => (int) $data['status'],
            'send_status' => (int) $data['send_status'],
            'retry_count' => (int) ($data['retry_count'] ?? 0),
            'next_retry_at' => $data['next_retry_at'] ?? null,
            'error_message' => (string) ($data['error_message'] ?? ''),
            'sent_at' => $data['sent_at'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->emailSendLogModel->findById($id) ?? [];
    }

    private function buildInquiryBodies(array $site, array $inquiry, array $payload): array
    {
        $htmlLines = [
            '<h1>New Inquiry Notification</h1>',
            '<ul>',
            sprintf('<li><strong>Site:</strong> %s</li>', htmlspecialchars((string) ($site['name'] ?? ''), ENT_QUOTES, 'UTF-8')),
            sprintf('<li><strong>Inquiry ID:</strong> %d</li>', (int) ($inquiry['id'] ?? 0)),
            sprintf('<li><strong>Source URL:</strong> %s</li>', htmlspecialchars((string) ($inquiry['source_url'] ?? ''), ENT_QUOTES, 'UTF-8')),
            sprintf('<li><strong>Submitted At:</strong> %s</li>', htmlspecialchars((string) ($inquiry['created_at'] ?? ''), ENT_QUOTES, 'UTF-8')),
            '</ul>',
            '<table border="1" cellpadding="6" cellspacing="0">',
            '<thead><tr><th>Field</th><th>Value</th></tr></thead>',
            '<tbody>',
        ];
        $textLines = [
            'New Inquiry Notification',
            'Site: ' . (string) ($site['name'] ?? ''),
            'Inquiry ID: ' . (int) ($inquiry['id'] ?? 0),
            'Source URL: ' . (string) ($inquiry['source_url'] ?? ''),
            'Submitted At: ' . (string) ($inquiry['created_at'] ?? ''),
            '',
            'Payload:',
        ];

        foreach ($this->flattenPayload($payload) as $key => $value) {
            $displayValue = is_scalar($value) || $value === null ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $htmlLines[] = sprintf(
                '<tr><td>%s</td><td>%s</td></tr>',
                htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8'),
                nl2br(htmlspecialchars((string) $displayValue, ENT_QUOTES, 'UTF-8'))
            );
            $textLines[] = sprintf('%s: %s', $key, (string) $displayValue);
        }

        $htmlLines[] = '</tbody></table>';

        return [implode('', $htmlLines), implode("\n", $textLines)];
    }

    private function flattenPayload(array $payload, string $prefix = ''): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result += $this->flattenPayload($value, $field);
                continue;
            }
            $result[$field] = $value;
        }
        return $result;
    }

    private function writeApplicationLog(string $event, array $context): void
    {
        $path = runtime_path('log/app.log');
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents(
            $path,
            json_encode([
                'timestamp' => date('Y-m-d H:i:s'),
                'event' => $event,
                'context' => $context,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            FILE_APPEND
        );
    }
}
