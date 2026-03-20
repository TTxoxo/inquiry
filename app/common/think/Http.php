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
        $request = $this->createRequest();
        $routes = require root_path('route/app.php');

        foreach ($routes as $route) {
            [$method, $path, $controllerClass, $action, $middlewares] = $route;
            if ($request['method'] !== $method || $request['path'] !== $path) {
                continue;
            }

            $controller = new $controllerClass();
            $core = static fn (array $requestData): Response => $controller->{$action}($requestData['input']);
            $pipeline = array_reduce(
                array_reverse($middlewares),
                static fn (callable $next, string $middlewareClass): callable => static function (array $requestData) use ($middlewareClass, $next): Response {
                    $middleware = new $middlewareClass();

                    return $middleware->handle($requestData, $next);
                },
                $core
            );

            return $pipeline($request);
        }

        return Response::html('Not Found', 404);
    }

    public function end(Response $response): void
    {
    }

    /**
     * @return array{method:string,path:string,input:array<string,mixed>,ip:string}
     */
    private function createRequest(): array
    {
        return [
            'method' => strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')),
            'path' => parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/',
            'input' => $this->getInput(),
            'ip' => mb_substr((string) ($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'), 0, 45),
        ];
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
