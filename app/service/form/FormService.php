<?php

declare(strict_types=1);

namespace app\service\form;

use app\model\Form;
use RuntimeException;

final class FormService
{
    public function __construct(
        private readonly Form $formModel = new Form(),
        private readonly FormFieldService $formFieldService = new FormFieldService()
    ) {
    }

    public function createDefaultForm(int $siteId, string $siteCode): array
    {
        $code = $siteCode === 'default' ? 'default_inquiry' : $siteCode . '_inquiry';
        $formId = $this->create([
            'site_id' => $siteId,
            'name' => 'Default Inquiry Form',
            'code' => $code,
            'description' => 'Default inquiry form created by service',
            'status' => 1,
        ]);
        $fields = $this->formFieldService->createDefaultFields($formId);

        return [
            'form' => $this->getById($formId),
            'fields' => $fields,
        ];
    }

    public function create(array $data): int
    {
        $siteId = (int) ($data['site_id'] ?? 0);
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        if ($siteId <= 0 || $name === '' || $code === '') {
            throw new RuntimeException('Site, form name and form code are required');
        }

        $now = date('Y-m-d H:i:s');

        return $this->formModel->insert([
            'site_id' => $siteId,
            'name' => $name,
            'code' => $code,
            'description' => trim((string) ($data['description'] ?? '')),
            'status' => (int) ($data['status'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function getById(int $formId): ?array
    {
        return $this->formModel->findById($formId);
    }

    public function findActiveByCode(string $code): ?array
    {
        $form = $this->formModel->findOneBy(['code' => $code, 'status' => 1]);
        if ($form === null) {
            return null;
        }

        return $form;
    }
}
