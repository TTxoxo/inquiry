<?php

declare(strict_types=1);

namespace think;

final class Response
{
    /**
     * @param array<string, mixed>|string $content
     */
    public function __construct(
        private readonly array|string $content,
        private readonly int $statusCode = 200,
        private readonly string $contentType = 'text/html; charset=utf-8'
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function json(array $data, int $statusCode = 200): self
    {
        return new self($data, $statusCode, 'application/json; charset=utf-8');
    }

    public static function html(string $content, int $statusCode = 200): self
    {
        return new self($content, $statusCode);
    }

    public function send(): void
    {
        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code($this->statusCode);
            header('Content-Type: ' . $this->contentType);
        }

        echo is_array($this->content)
            ? json_encode($this->content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
            : $this->content;
    }
}
