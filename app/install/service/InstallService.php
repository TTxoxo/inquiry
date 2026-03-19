<?php

declare(strict_types=1);

namespace app\install\service;

use PDO;
use PDOException;
use RuntimeException;

final class InstallService
{
    private string $lockFile;

    public function __construct()
    {
        $this->lockFile = runtime_path('install.lock');
    }

    public function getInstallPageData(): array
    {
        return [
            'locked' => $this->isInstalled(),
            'lock_file' => $this->lockFile,
            'env_template_exists' => is_file(root_path('.env.example')),
        ];
    }

    public function checkEnvironment(): array
    {
        return [
            'php_version' => PHP_VERSION,
            'php_ok' => version_compare(PHP_VERSION, '8.3.0', '>='),
            'pdo_mysql' => extension_loaded('pdo_mysql'),
            'openssl' => extension_loaded('openssl'),
            'mbstring' => extension_loaded('mbstring'),
            'writable' => [
                'root' => is_writable(root_path()),
                'runtime' => is_dir(runtime_path()) && is_writable(runtime_path()),
                'install_sql' => is_dir(root_path('install/sql')),
            ],
            'installed' => $this->isInstalled(),
        ];
    }

    public function testDatabase(array $input): array
    {
        $pdo = $this->createPdo($input);

        return [
            'version' => (string) $pdo->query('SELECT VERSION()')->fetchColumn(),
            'database' => (string) $input['db_name'],
            'prefix' => (string) ($input['db_prefix'] ?? ''),
        ];
    }

    public function executeInstall(array $input): array
    {
        if ($this->isInstalled()) {
            throw new RuntimeException('Install locked', 4013);
        }

        $appKey = $this->generateAppKey();
        $envContent = $this->buildEnvContent($input, $appKey);
        $pdo = $this->createPdo($input);

        try {
            $pdo->beginTransaction();
            $this->importSqlFile($pdo, root_path('install/sql/schema.sql'), (string) ($input['db_prefix'] ?? ''));
            $this->importSqlFile($pdo, root_path('install/sql/init_data.sql'), (string) ($input['db_prefix'] ?? ''));
            $this->createAdministrator($pdo, $input);

            $envWritten = @file_put_contents(root_path('.env'), $envContent);
            if ($envWritten === false) {
                throw new RuntimeException('Write env failed', 4013);
            }

            $lockWritten = @file_put_contents($this->lockFile, 'installed_at=' . date('c') . PHP_EOL);
            if ($lockWritten === false) {
                throw new RuntimeException('Write install lock failed', 4013);
            }

            $pdo->commit();
        } catch (PDOException|RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            @unlink(root_path('.env'));
            @unlink($this->lockFile);
            throw $exception;
        }

        return [
            'app_key' => $appKey,
            'lock_file' => $this->lockFile,
            'env_file' => root_path('.env'),
        ];
    }

    public function isInstalled(): bool
    {
        return is_file($this->lockFile);
    }

    private function createPdo(array $input): PDO
    {
        $host = (string) ($input['db_host'] ?? '127.0.0.1');
        $port = (string) ($input['db_port'] ?? '3306');
        $database = (string) ($input['db_name'] ?? '');
        $username = (string) ($input['db_user'] ?? '');
        $password = (string) ($input['db_password'] ?? '');
        $charset = 'utf8mb4';

        try {
            return new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed', 4012, $exception);
        }
    }

    private function importSqlFile(PDO $pdo, string $file, string $prefix): void
    {
        $sql = @file_get_contents($file);
        if ($sql === false) {
            throw new RuntimeException('Install schema failed', 4013);
        }

        $sql = str_replace('__PREFIX__', $prefix, $sql);
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));

        try {
            foreach ($statements as $statement) {
                if ($statement === '' || str_starts_with($statement, '--')) {
                    continue;
                }

                $pdo->exec($statement);
            }
        } catch (PDOException $exception) {
            throw new RuntimeException('Install schema failed', 4013, $exception);
        }
    }

    private function createAdministrator(PDO $pdo, array $input): void
    {
        $table = (string) ($input['db_prefix'] ?? '') . 'users';
        $username = trim((string) $input['admin_username']);
        $password = password_hash((string) $input['admin_password'], PASSWORD_DEFAULT);
        $email = trim((string) ($input['admin_email'] ?? 'admin@example.com'));
        $now = date('Y-m-d H:i:s');

        $statement = $pdo->prepare(
            sprintf('INSERT INTO `%s` (`site_id`, `username`, `password`, `email`, `nickname`, `status`, `is_super_admin`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at`) VALUES (:site_id, :username, :password, :email, :nickname, :status, :is_super_admin, :last_login_at, :last_login_ip, :created_at, :updated_at)', $table)
        );

        $success = $statement->execute([
            'site_id' => 1,
            'username' => $username,
            'password' => $password,
            'email' => $email,
            'nickname' => 'Super Admin',
            'status' => 1,
            'is_super_admin' => 1,
            'last_login_at' => null,
            'last_login_ip' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($success !== true) {
            throw new RuntimeException('Create admin failed', 4013);
        }
    }

    private function buildEnvContent(array $input, string $appKey): string
    {
        $values = [
            'APP_DEBUG' => 'false',
            'APP_ENV' => 'production',
            'APP_KEY' => $appKey,
            'DEFAULT_TIMEZONE' => 'Asia/Shanghai',
            'DB_CONNECTION' => 'mysql',
            'DB_HOST' => (string) $input['db_host'],
            'DB_PORT' => (string) $input['db_port'],
            'DB_DATABASE' => (string) $input['db_name'],
            'DB_USERNAME' => (string) $input['db_user'],
            'DB_PASSWORD' => (string) ($input['db_password'] ?? ''),
            'DB_PREFIX' => (string) ($input['db_prefix'] ?? ''),
        ];

        $lines = [];
        foreach ($values as $key => $value) {
            $lines[] = $key . '=' . $value;
        }

        return implode(PHP_EOL, $lines) . PHP_EOL;
    }

    private function generateAppKey(): string
    {
        return bin2hex(random_bytes(16));
    }
}
