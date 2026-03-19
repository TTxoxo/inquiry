<?php

declare(strict_types=1);

namespace think;

final class Response
{
    public function __construct(private readonly string $content, private readonly int $statusCode = 200)
    {
    }

    public function send(): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: text/html; charset=utf-8');
        }

        echo $this->content;
    }
}
