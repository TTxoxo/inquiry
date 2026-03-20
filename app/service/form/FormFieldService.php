<?php

declare(strict_types=1);

namespace app\service\form;

use app\model\FormField;
use RuntimeException;

final class FormFieldService
{
    public function __construct(private readonly FormField $formFieldModel = new FormField())
    {
    }

    public function createDefaultFields(int $formId): array
    {
        $defaults = [
            ['name' => 'name', 'label' => 'Name', 'type' => 'text', 'is_required' => 1, 'sort' => 10, 'settings_json' => ['placeholder' => 'Your name']],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'is_required' => 1, 'sort' => 20, 'settings_json' => ['placeholder' => 'Your email']],
            ['name' => 'message', 'label' => 'Message', 'type' => 'textarea', 'is_required' => 1, 'sort' => 30, 'settings_json' => ['placeholder' => 'Your message']],
        ];

        $created = [];
        foreach ($defaults as $field) {
            $fieldId = $this->create($formId, $field);
            $created[] = $this->formFieldModel->findById($fieldId);
        }

        return array_values(array_filter($created, static fn ($item): bool => is_array($item)));
    }

    public function create(int $formId, array $data): int
    {
        $name = trim((string) ($data['name'] ?? ''));
        $label = trim((string) ($data['label'] ?? ''));
        $type = trim((string) ($data['type'] ?? 'text'));
        if ($name === '' || $label === '') {
            throw new RuntimeException('Form field name and label are required');
        }

        $now = date('Y-m-d H:i:s');

        return $this->formFieldModel->insert([
            'form_id' => $formId,
            'name' => $name,
            'label' => $label,
            'type' => $type,
            'is_required' => (int) ($data['is_required'] ?? 0),
            'sort' => (int) ($data['sort'] ?? 0),
            'settings_json' => json_encode($data['settings_json'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function listByFormId(int $formId): array
    {
        $rows = $this->formFieldModel->findAllBy(['form_id' => $formId], '`sort` ASC, `id` ASC');

        return array_map(function (array $row): array {
            $row['settings_json'] = $this->decodeJson((string) ($row['settings_json'] ?? 'null'));

            return $row;
        }, $rows);
    }

    public function requiredFieldNames(int $formId): array
    {
        $fields = $this->listByFormId($formId);
        $names = [];
        foreach ($fields as $field) {
            if ((int) $field['is_required'] === 1) {
                $names[] = (string) $field['name'];
            }
        }

        return $names;
    }

    private function decodeJson(string $value): array
    {
        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
