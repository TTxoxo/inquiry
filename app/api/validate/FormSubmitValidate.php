<?php

declare(strict_types=1);

namespace app\api\validate;

final class FormSubmitValidate
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

        if (!isset($input['fields']) || !is_array($input['fields'])) {
            $errors['fields'] = 'fields must be an object';
        }

        if (!isset($input['page_meta']) || !is_array($input['page_meta'])) {
            $errors['page_meta'] = 'page_meta must be an object';
        }

        if (!isset($input['tracking_meta']) || !is_array($input['tracking_meta'])) {
            $errors['tracking_meta'] = 'tracking_meta must be an object';
        }

        $honeypot = $input['honeypot'] ?? '';
        if (!is_scalar($honeypot)) {
            $errors['honeypot'] = 'honeypot must be a string';
        }

        return $errors;
    }
}
