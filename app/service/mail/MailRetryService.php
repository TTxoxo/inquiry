<?php

declare(strict_types=1);

namespace app\service\mail;

use app\model\EmailSendLog;
use DateTimeImmutable;
use RuntimeException;

final class MailRetryService
{
    public function __construct(
        private readonly EmailSendLog $emailSendLogModel = new EmailSendLog(),
        private readonly SmtpConfigService $smtpConfigService = new SmtpConfigService(),
        private readonly SmtpMailerService $smtpMailerService = new SmtpMailerService()
    ) {
    }

    public function failedBySiteId(int $siteId, int $limit = 20): array
    {
        $sql = sprintf(
            'SELECT %s FROM `%s` WHERE `site_id` = :site_id AND `send_status` = 2 ORDER BY `id` DESC LIMIT %d',
            $this->columnList(),
            $this->emailSendLogModel->table(),
            max(1, $limit)
        );
        $statement = $this->emailSendLogModel->pdo()->prepare($sql);
        $statement->execute(['site_id' => $siteId]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    public function retryPending(int $limit = 50): array
    {
        $rows = $this->pendingLogs($limit);
        $result = ['processed' => 0, 'sent' => 0, 'failed' => 0, 'items' => []];
        foreach ($rows as $row) {
            $result['processed']++;
            $item = $this->retryLog((int) $row['id']);
            $result['items'][] = $item;
            if ((int) ($item['send_status'] ?? 0) === 1) {
                $result['sent']++;
                continue;
            }
            $result['failed']++;
        }

        return $result;
    }

    public function retryLog(int $id): array
    {
        $row = $this->emailSendLogModel->findById($id);
        if ($row === null) {
            throw new RuntimeException('Email log not found');
        }
        if (!$this->isRetryable($row)) {
            return $row + ['retry_result' => 'skipped'];
        }

        $smtpConfig = $this->smtpConfigService->getActiveBySiteId((int) $row['site_id']);
        if ($smtpConfig === null) {
            return $this->markFailure($row, 'SMTP config unavailable');
        }

        try {
            $this->smtpMailerService->send(
                $smtpConfig,
                (string) $row['to_email'],
                (string) $row['subject'],
                (string) ($row['body_html'] ?? ''),
                (string) ($row['body_text'] ?? '')
            );

            $this->emailSendLogModel->updateById((int) $row['id'], [
                'status' => 1,
                'send_status' => 1,
                'error_message' => '',
                'sent_at' => date('Y-m-d H:i:s'),
                'next_retry_at' => null,
            ]);
        } catch (RuntimeException $exception) {
            return $this->markFailure($row, $exception->getMessage());
        }

        return ($this->emailSendLogModel->findById((int) $row['id']) ?? $row) + ['retry_result' => 'sent'];
    }

    private function pendingLogs(int $limit): array
    {
        $now = date('Y-m-d H:i:s');
        $sql = sprintf(
            'SELECT %s FROM `%s` WHERE `send_status` = 2 AND `retry_count` < 3 AND `next_retry_at` IS NOT NULL AND `next_retry_at` <= :now ORDER BY `id` ASC LIMIT %d',
            $this->columnList(),
            $this->emailSendLogModel->table(),
            max(1, $limit)
        );
        $statement = $this->emailSendLogModel->pdo()->prepare($sql);
        $statement->execute(['now' => $now]);

        return $statement->fetchAll(\PDO::FETCH_ASSOC) ?: [];
    }

    private function markFailure(array $row, string $message): array
    {
        $retryCount = min(3, (int) ($row['retry_count'] ?? 0) + 1);
        $nextRetryAt = $retryCount >= 3 ? null : $this->nextRetryAt($retryCount);
        $this->emailSendLogModel->updateById((int) $row['id'], [
            'status' => 0,
            'send_status' => 2,
            'retry_count' => $retryCount,
            'error_message' => mb_substr($message, 0, 500),
            'next_retry_at' => $nextRetryAt,
        ]);

        return ($this->emailSendLogModel->findById((int) $row['id']) ?? $row) + ['retry_result' => 'failed'];
    }

    private function isRetryable(array $row): bool
    {
        if ((int) ($row['send_status'] ?? 0) !== 2) {
            return false;
        }
        if ((int) ($row['retry_count'] ?? 0) >= 3) {
            return false;
        }
        $nextRetryAt = (string) ($row['next_retry_at'] ?? '');
        if ($nextRetryAt === '') {
            return false;
        }

        return strtotime($nextRetryAt) !== false && strtotime($nextRetryAt) <= time();
    }

    private function nextRetryAt(int $retryCount): string
    {
        $minutes = match ($retryCount) {
            1 => 5,
            2 => 15,
            default => 0,
        };

        return (new DateTimeImmutable('now'))->modify(sprintf('+%d minutes', $minutes))->format('Y-m-d H:i:s');
    }

    private function columnList(): string
    {
        return implode(', ', array_map(static fn (string $field): string => sprintf('`%s`', $field), $this->emailSendLogModel->fields()));
    }
}
