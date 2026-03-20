<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\EmailSendLog;

final class InquiryMailService
{
    public function __construct(
        private readonly SmtpConfigService $smtpConfigService = new SmtpConfigService(),
        private readonly NotifyEmailService $notifyEmailService = new NotifyEmailService(),
        private readonly EmailSendLog $emailSendLogModel = new EmailSendLog()
    ) {
    }

    public function sendInquiryNotification(array $inquiry, array $site, array $payload): array
    {
        $siteId = (int) $site['id'];
        $smtpConfig = $this->smtpConfigService->getActiveBySiteId($siteId);
        $recipients = $this->notifyEmailService->getActiveBySiteId($siteId);
        $subject = sprintf('[%s] New Inquiry #%d', (string) $site['name'], (int) $inquiry['id']);

        if ($recipients === []) {
            return ['sent' => 0, 'failed' => 0, 'logs' => []];
        }

        $logs = [];
        foreach ($recipients as $recipient) {
            $success = $smtpConfig !== null;
            $logs[] = $this->createSendLog([
                'site_id' => $siteId,
                'inquiry_id' => (int) $inquiry['id'],
                'to_email' => $recipient,
                'subject' => $subject,
                'status' => $success ? 1 : 0,
                'error_message' => $success ? '' : 'SMTP config unavailable',
                'sent_at' => $success ? date('Y-m-d H:i:s') : null,
            ]);
        }

        return [
            'sent' => count(array_filter($logs, static fn (array $log): bool => (int) $log['status'] === 1)),
            'failed' => count(array_filter($logs, static fn (array $log): bool => (int) $log['status'] !== 1)),
            'logs' => $logs,
            'smtp_configured' => $smtpConfig !== null,
            'payload_snapshot' => $payload,
        ];
    }

    private function createSendLog(array $data): array
    {
        $id = $this->emailSendLogModel->insert([
            'site_id' => (int) $data['site_id'],
            'inquiry_id' => $data['inquiry_id'],
            'to_email' => (string) $data['to_email'],
            'subject' => (string) $data['subject'],
            'status' => (int) $data['status'],
            'error_message' => (string) ($data['error_message'] ?? ''),
            'sent_at' => $data['sent_at'],
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->emailSendLogModel->findById($id) ?? [];
    }
}
