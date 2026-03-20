<?php

declare(strict_types=1);

namespace app\service\tracking;

final class DataLayerPayloadService
{
    public function build(array $trackingConfig, array $context): array
    {
        $config = $trackingConfig['config_json'] ?? [];
        $payload = [
            'event' => 'inquiry_submit',
            'site_id' => (int) ($context['site_id'] ?? 0),
            'form_id' => (int) ($context['form_id'] ?? 0),
            'channel' => (string) ($context['channel'] ?? ''),
        ];

        if (($config['capture_utm'] ?? false) === true) {
            $payload['utm'] = [
                'source' => (string) ($context['utm_source'] ?? ''),
                'medium' => (string) ($context['utm_medium'] ?? ''),
                'campaign' => (string) ($context['utm_campaign'] ?? ''),
            ];
        }

        if (($config['capture_referrer'] ?? false) === true) {
            $payload['referrer'] = (string) ($context['referrer'] ?? '');
        }

        if (($config['capture_landing_page'] ?? false) === true) {
            $payload['landing_page'] = (string) ($context['source_url'] ?? '');
        }

        if (($config['capture_gclid'] ?? false) === true) {
            $payload['gclid'] = (string) ($context['gclid'] ?? '');
        }

        if (($config['capture_fbclid'] ?? false) === true) {
            $payload['fbclid'] = (string) ($context['fbclid'] ?? '');
        }

        return $payload;
    }
}
