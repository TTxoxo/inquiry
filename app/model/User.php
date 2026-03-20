<?php

declare(strict_types=1);

namespace app\model;

use PDO;
use PDOException;
use RuntimeException;

class User
{
    private static ?PDO $pdo = null;

    public function findByUsername(string $username): ?array
    {
        $statement = $this->pdo()->prepare(
            sprintf('SELECT `id`, `site_id`, `username`, `password`, `email`, `nickname`, `status`, `is_super_admin`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at` FROM `%s` WHERE `username` = :username LIMIT 1', $this->table('users'))
        );
        $statement->execute(['username' => $username]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($user) ? $user : null;
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo()->prepare(
            sprintf('SELECT `id`, `site_id`, `username`, `password`, `email`, `nickname`, `status`, `is_super_admin`, `last_login_at`, `last_login_ip`, `created_at`, `updated_at` FROM `%s` WHERE `id` = :id LIMIT 1', $this->table('users'))
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch(PDO::FETCH_ASSOC);

        return is_array($user) ? $user : null;
    }

    public function updateLoginMeta(int $id, string $ip, string $time): void
    {
        $statement = $this->pdo()->prepare(
            sprintf('UPDATE `%s` SET `last_login_at` = :last_login_at, `last_login_ip` = :last_login_ip, `updated_at` = :updated_at WHERE `id` = :id', $this->table('users'))
        );

        $statement->execute([
            'id' => $id,
            'last_login_at' => $time,
            'last_login_ip' => $ip,
            'updated_at' => $time,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash, string $time): void
    {
        $statement = $this->pdo()->prepare(
            sprintf('UPDATE `%s` SET `password` = :password, `updated_at` = :updated_at WHERE `id` = :id', $this->table('users'))
        );

        $statement->execute([
            'id' => $id,
            'password' => $passwordHash,
            'updated_at' => $time,
        ]);
    }

    public function pdo(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $host = (string) env('DB_HOST', env('DATABASE_HOST', '127.0.0.1'));
        $port = (string) env('DB_PORT', env('DATABASE_PORT', '3306'));
        $database = (string) env('DB_DATABASE', env('DATABASE_NAME', 'inquiry'));
        $username = (string) env('DB_USERNAME', env('DATABASE_USER', 'root'));
        $password = (string) env('DB_PASSWORD', env('DATABASE_PASSWORD', ''));
        $charset = (string) env('DB_CHARSET', env('DATABASE_CHARSET', 'utf8mb4'));

        try {
            self::$pdo = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset),
                $username,
                $password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Database connection failed', 5001, $exception);
        }

        return self::$pdo;
    }

    public function table(string $name): string
    {
        return (string) env('DB_PREFIX', env('DATABASE_PREFIX', '')) . $name;
    }
}
