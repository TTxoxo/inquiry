<?php

declare(strict_types=1);

namespace think;

use app\install\controller\InstallController;

final class Http
{
    public function __construct(private readonly App $app)
    {
    }

    public function run(): Response
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/';
        $controller = new InstallController();
        $input = $this->getInput();

        if ($method === 'GET' && $path === '/install') {
            return $controller->index();
        }

        if ($method === 'POST' && $path === '/install/check-env') {
            return $controller->checkEnv($input);
        }

        if ($method === 'POST' && $path === '/install/test-db') {
            return $controller->testDb($input);
        }

        if ($method === 'POST' && $path === '/install/execute') {
            return $controller->execute($input);
        }

        return Response::html('Not Found', 404);
    }

    public function end(Response $response): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    private function getInput(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '[]', true);

            return is_array($decoded) ? $decoded : [];
        }

        return $_POST;
    }
}
