<?php

declare(strict_types=1);

namespace app\admin\validate;

final class SpamKeywordsValidate
{
    public function check(array $input): array
    {
        $errors = [];
        foreach ($input as $key => $value) {
            if (is_string($value) && trim($value) === '' && in_array($key, ['name','code','domain','username','email','host','from_email','from_name','keyword'], true)) {
                $errors[$key] = $key . ' is required';
            }
        }

        return $errors;
    }
}
