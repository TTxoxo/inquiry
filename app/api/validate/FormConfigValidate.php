<?php

declare(strict_types=1);

namespace app\api\validate;

final class FormConfigValidate
{
    public function check(array $input): array
    {
        $errors = [];

        $siteKey = trim((string) ($input['site_key'] ?? ''));
        if ($siteKey === '') {
            $errors['site_key'] = 'site_key is required';
        }

        $formKey = trim((string) ($input['form_key'] ?? ''));
        if ($formKey === '') {
            $errors['form_key'] = 'form_key is required';
        }

        $mode = trim((string) ($input['mode'] ?? ''));
        if ($mode === '') {
            $errors['mode'] = 'mode is required';
        }

        return $errors;
    }
}
