<?php

declare(strict_types=1);

namespace app\model;

class User extends BaseModel
{
    public function fields(): array
    {
        return ['id', 'site_id', 'username', 'password', 'email', 'nickname', 'status', 'is_super_admin', 'last_login_at', 'last_login_ip', 'created_at', 'updated_at'];
    }

    protected function tableName(): string
    {
        return 'users';
    }

    public function findByUsername(string $username): ?array
    {
        return $this->findOneBy(['username' => $username]);
    }

    public function updateLoginMeta(int $id, string $ip, string $time): void
    {
        $this->updateById($id, [
            'last_login_at' => $time,
            'last_login_ip' => $ip,
            'updated_at' => $time,
        ]);
    }

    public function updatePassword(int $id, string $passwordHash, string $time): void
    {
        $this->updateById($id, [
            'password' => $passwordHash,
            'updated_at' => $time,
        ]);
    }
}
