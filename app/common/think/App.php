<?php

declare(strict_types=1);

namespace think;

final class App
{
    public Console $console;

    public Http $http;

    public function __construct()
    {
        $this->bootstrapEnvironment();
        $this->prepareRuntimeDirectories();
        date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Shanghai'));
        $this->console = new Console($this);
        $this->http = new Http($this);
    }

    private function bootstrapEnvironment(): void
    {
        $envFile = root_path('.env');
        $exampleFile = root_path('.env.example');
        $file = is_file($envFile) ? $envFile : $exampleFile;

        if (!is_file($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
                continue;
            }

            [$name, $value] = array_map('trim', explode('=', $line, 2));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
            putenv($name . '=' . $value);
        }
    }

    private function prepareRuntimeDirectories(): void
    {
        foreach (['cache', 'log', 'session'] as $directory) {
            $path = runtime_path($directory);
            if (!is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }
}
