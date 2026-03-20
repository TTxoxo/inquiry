<?php

declare(strict_types=1);

namespace app\service\site;

use app\model\Site;
use app\service\form\EmbedCodeService;
use app\service\form\FormService;
use app\service\tracking\TrackingConfigService;
use RuntimeException;

final class SiteService
{
    public function __construct(
        private readonly Site $siteModel = new Site(),
        private readonly FormService $formService = new FormService(),
        private readonly TrackingConfigService $trackingConfigService = new TrackingConfigService(),
        private readonly EmbedCodeService $embedCodeService = new EmbedCodeService()
    ) {
    }

    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $code = trim((string) ($data['code'] ?? ''));
        $domain = trim((string) ($data['domain'] ?? ''));
        if ($name === '' || $code === '' || $domain === '') {
            throw new RuntimeException('Site name, code and domain are required');
        }

        $now = date('Y-m-d H:i:s');
        $siteId = $this->siteModel->insert([
            'name' => $name,
            'code' => $code,
            'domain' => $domain,
            'status' => (int) ($data['status'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $site = $this->siteModel->findById($siteId);
        if ($site === null) {
            throw new RuntimeException('Site create failed');
        }

        $defaultForm = $this->formService->createDefaultForm($siteId, $code);
        $tracking = $this->trackingConfigService->createDefaultConfig((int) $defaultForm['form']['id']);
        $embed = $this->embedCodeService->build($site, $defaultForm['form'], 'https://' . $domain);

        return [
            'site' => $site,
            'site_key' => $embed['site_key'],
            'default_form' => $defaultForm['form'],
            'default_fields' => $defaultForm['fields'],
            'tracking_config' => $tracking,
            'embed' => $embed,
        ];
    }

    public function findByCode(string $code): ?array
    {
        return $this->siteModel->findOneBy(['code' => $code, 'status' => 1]);
    }
}
