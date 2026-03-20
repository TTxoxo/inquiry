<?php

declare(strict_types=1);

namespace app\admin\validate;

final class SmtpValidate
{
    public function check(array $input): array
    {
        $errors = [];
        foreach (['site_id', 'host', 'port', 'username', 'from_email', 'from_name'] as $field) {
            $value = $input[$field] ?? null;
            if (($field === 'site_id' || $field === 'port') && (int) $value <= 0) {
                $errors[$field] = $field . ' is required';
                continue;
            }
            if ($field !== 'site_id' && $field !== 'port' && trim((string) $value) === '') {
                $errors[$field] = $field . ' is required';
            }
        }

        return $errors;
    }

    public function checkTest(array $input): array
    {
        $errors = $this->check($input);
        if (trim((string) ($input['test_email'] ?? '')) === '') {
            $errors['test_email'] = 'test_email is required';
        }

        return $errors;
    }
}
