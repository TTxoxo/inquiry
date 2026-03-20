<?php

declare(strict_types=1);

namespace app\install\service;

use PDO;

final class InstallDefaultsService
{
    public function ensureDefaultTrackingConfig(PDO $pdo, string $prefix, int $formId): void
    {
        $table = $prefix . 'form_tracking_configs';
        $check = $pdo->prepare(sprintf('SELECT `id` FROM `%s` WHERE `form_id` = :form_id AND `tracking_type` = :tracking_type LIMIT 1', $table));
        $check->execute([
            'form_id' => $formId,
            'tracking_type' => 'datalayer',
        ]);
        if ($check->fetchColumn() !== false) {
            return;
        }

        $statement = $pdo->prepare(
            sprintf('INSERT INTO `%s` (`form_id`, `tracking_type`, `config_json`, `status`, `created_at`, `updated_at`) VALUES (:form_id, :tracking_type, :config_json, :status, :created_at, :updated_at)', $table)
        );
        $now = date('Y-m-d H:i:s');
        $statement->execute([
            'form_id' => $formId,
            'tracking_type' => 'datalayer',
            'config_json' => json_encode([
                'capture_utm' => true,
                'capture_gclid' => true,
                'capture_fbclid' => true,
                'capture_referrer' => true,
                'capture_landing_page' => true,
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'status' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }
}
