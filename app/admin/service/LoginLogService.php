<?php

declare(strict_types=1);

namespace app\admin\service;

use app\model\User;
use PDO;

class LoginLogService
{
    public function __construct(private readonly User $userModel = new User())
    {
    }

    public function record(?int $userId, string $username, int $status, string $ip, string $userAgent): void
    {
        $statement = $this->userModel->pdo()->prepare(
            sprintf('INSERT INTO `%s` (`user_id`, `username`, `status`, `ip`, `user_agent`, `created_at`) VALUES (:user_id, :username, :status, :ip, :user_agent, :created_at)', $this->userModel->table('login_logs'))
        );
        $statement->execute([
            'user_id' => $userId,
            'username' => $username,
            'status' => $status,
            'ip' => $ip,
            'user_agent' => mb_substr($userAgent, 0, 500),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function tooManyFailures(string $ip, string $username): bool
    {
        return $this->countRecentFailuresByIp($ip) >= 10 || $this->countRecentFailuresByUsername($username) >= 10;
    }

    public function countRecentFailuresByIp(string $ip): int
    {
        return $this->countRecentFailures('`ip` = :value', $ip);
    }

    public function countRecentFailuresByUsername(string $username): int
    {
        return $this->countRecentFailures('`username` = :value', $username);
    }

    private function countRecentFailures(string $condition, string $value): int
    {
        $statement = $this->userModel->pdo()->prepare(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE `status` = 0 AND %s AND `created_at` >= :created_at', $this->userModel->table('login_logs'), $condition)
        );
        $statement->execute([
            'value' => $value,
            'created_at' => date('Y-m-d H:i:s', time() - 600),
        ]);

        return (int) $statement->fetchColumn();
    }

    public function latestByUsername(string $username, int $limit = 10): array
    {
        $statement = $this->userModel->pdo()->prepare(
            sprintf('SELECT `username`, `status`, `ip`, `created_at` FROM `%s` WHERE `username` = :username ORDER BY `id` DESC LIMIT %d', $this->userModel->table('login_logs'), $limit)
        );
        $statement->execute(['username' => $username]);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
