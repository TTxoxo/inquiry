<?php

declare(strict_types=1);

namespace app\service\tracking;

use app\model\FormTrackingConfig;

final class TrackingConfigService
{
    public function __construct(private readonly FormTrackingConfig $trackingConfigModel = new FormTrackingConfig())
    {
    }

    public function createDefaultConfig(int $formId): array
    {
        $config = [
            'capture_utm' => true,
            'capture_gclid' => true,
            'capture_fbclid' => true,
            'capture_referrer' => true,
            'capture_landing_page' => true,
        ];
        $now = date('Y-m-d H:i:s');
        $id = $this->trackingConfigModel->insert([
            'form_id' => $formId,
            'tracking_type' => 'datalayer',
            'config_json' => json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->getById($id) ?? [];
    }

    public function getByFormId(int $formId, string $trackingType = 'datalayer'): ?array
    {
        $row = $this->trackingConfigModel->findOneBy(['form_id' => $formId, 'tracking_type' => $trackingType, 'status' => 1]);
        if ($row === null) {
            return null;
        }

        $row['config_json'] = $this->decode((string) $row['config_json']);

        return $row;
    }

    public function getById(int $id): ?array
    {
        $row = $this->trackingConfigModel->findById($id);
        if ($row === null) {
            return null;
        }

        $row['config_json'] = $this->decode((string) $row['config_json']);

        return $row;
    }

    private function decode(string $json): array
    {
        $decoded = json_decode($json, true);

        return is_array($decoded) ? $decoded : [];
    }
}
