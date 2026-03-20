<?php

declare(strict_types=1);

namespace app\service\form;

final class EmbedCodeService
{
    public function build(array $site, array $form, string $publicBaseUrl): array
    {
        $siteKey = hash('sha256', implode('|', [(string) $site['id'], (string) $site['code'], (string) env('APP_KEY', 'stage4')]));
        $query = http_build_query([
            'site_code' => (string) $site['code'],
            'form_code' => (string) $form['code'],
            'site_key' => $siteKey,
        ]);

        return [
            'site_key' => $siteKey,
            'script_src' => rtrim($publicBaseUrl, '/') . '/assets/embed/embed.js?' . $query,
            'container' => '<div class="inquiry-embed" data-site-code="' . htmlspecialchars((string) $site['code'], ENT_QUOTES) . '" data-form-code="' . htmlspecialchars((string) $form['code'], ENT_QUOTES) . '"></div>',
        ];
    }
}
