<?php

declare(strict_types=1);

namespace think;

use app\command\MailRetryCommand;

final class Console
{
    public function __construct(private readonly App $app)
    {
    }

    public function run(): void
    {
        $argv = $_SERVER['argv'] ?? [];
        $command = (string) ($argv[1] ?? '');
        $arguments = array_slice($argv, 2);

        if ($command === 'mail:retry') {
            exit((new MailRetryCommand())->execute($arguments));
        }

        $output = [
            'Think console bootstrap is running.',
            'timezone=' . date_default_timezone_get(),
            'cache=file',
            'session=file',
            'log=file',
            'database=mysql',
            'view_suffix=html',
            'available_commands=mail:retry',
        ];

        fwrite(STDOUT, implode(PHP_EOL, $output) . PHP_EOL);
    }
}
