<?php

declare(strict_types=1);

namespace think;

final class Http
{
    public function __construct(private readonly App $app)
    {
    }

    public function run(): Response
    {
        return new Response('Inquiry stage-1 bootstrap running.');
    }

    public function end(Response $response): void
    {
    }
}
