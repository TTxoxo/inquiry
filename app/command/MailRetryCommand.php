<?php

declare(strict_types=1);

namespace app\command;

use app\service\mail\MailRetryService;

final class MailRetryCommand
{
    public function __construct(private readonly MailRetryService $mailRetryService = new MailRetryService())
    {
    }

    public function execute(array $arguments = []): int
    {
        $limit = isset($arguments[0]) ? max(1, (int) $arguments[0]) : 50;
        $result = $this->mailRetryService->retryPending($limit);

        fwrite(STDOUT, json_encode([
            'code' => 0,
            'message' => 'ok',
            'data' => $result,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL);

        return 0;
    }
}
