<?php

declare(strict_types=1);

namespace app\install\validate;

final class InstallValidator
{
    public function validateCheckEnv(): array
    {
        return [];
    }

    public function validateTestDb(array $input): array
    {
        $errors = [];
        foreach (['db_host', 'db_port', 'db_name', 'db_user'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        return $errors;
    }

    public function validateExecute(array $input): array
    {
        $errors = $this->validateTestDb($input);

        foreach (['admin_username', 'admin_password'] as $field) {
            if (!isset($input[$field]) || trim((string) $input[$field]) === '') {
                $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
            }
        }

        if (isset($input['admin_username']) && mb_strlen(trim((string) $input['admin_username'])) > 50) {
            $errors['admin_username'] = 'Admin username is invalid';
        }

        if (isset($input['admin_password']) && mb_strlen((string) $input['admin_password']) < 8) {
            $errors['admin_password'] = 'Admin password must be at least 8 characters';
        }

        return $errors;
    }
}
