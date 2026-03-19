<?php

declare(strict_types=1);

namespace think;

final class Console
{
    public function __construct(private readonly App $app)
    {
    }

    public function run(): void
    {
        $output = [
            'Think console bootstrap is running.',
            'timezone=' . date_default_timezone_get(),
            'cache=file',
            'session=file',
            'log=file',
            'database=mysql',
            'view_suffix=html',
        ];

        fwrite(STDOUT, implode(PHP_EOL, $output) . PHP_EOL);
    }
}
