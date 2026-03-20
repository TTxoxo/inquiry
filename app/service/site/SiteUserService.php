<?php

declare(strict_types=1);

namespace app\service\site;

use app\model\User;

final class SiteUserService
{
    public function __construct(private readonly User $userModel = new User())
    {
    }

    public function create(array $data): int
    {
        $now = date('Y-m-d H:i:s');

        return $this->userModel->insert([
            'site_id' => (int) $data['site_id'],
            'username' => trim((string) $data['username']),
            'password' => (string) $data['password'],
            'email' => trim((string) $data['email']),
            'nickname' => trim((string) ($data['nickname'] ?? $data['username'])),
            'status' => (int) ($data['status'] ?? 1),
            'is_super_admin' => (int) ($data['is_super_admin'] ?? 0),
            'last_login_at' => null,
            'last_login_ip' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
