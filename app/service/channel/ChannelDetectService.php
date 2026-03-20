<?php

declare(strict_types=1);

namespace app\service\channel;

final class ChannelDetectService
{
    private const CHANNELS = [
        'Google Ads',
        'Facebook/Instagram Ads',
        'Email Marketing',
        'Organic Search',
        'Social',
        'Referral',
        'Direct',
        'Other Campaign',
    ];

    public function detect(array $context): string
    {
        $utmSource = strtolower((string) ($context['utm_source'] ?? ''));
        $utmMedium = strtolower((string) ($context['utm_medium'] ?? ''));
        $gclid = (string) ($context['gclid'] ?? '');
        $fbclid = (string) ($context['fbclid'] ?? '');
        $referrer = strtolower((string) ($context['referrer'] ?? ''));

        if ($gclid !== '' || str_contains($utmSource, 'google') || in_array($utmMedium, ['cpc', 'ppc', 'paidsearch'], true)) {
            return 'Google Ads';
        }

        if ($fbclid !== '' || str_contains($utmSource, 'facebook') || str_contains($utmSource, 'instagram') || in_array($utmMedium, ['paid_social', 'social_paid'], true)) {
            return 'Facebook/Instagram Ads';
        }

        if (str_contains($utmMedium, 'email') || str_contains($utmSource, 'newsletter')) {
            return 'Email Marketing';
        }

        if ($utmSource !== '' || $utmMedium !== '') {
            return 'Other Campaign';
        }

        if (preg_match('/google\.|bing\.|yahoo\.|baidu\./', $referrer) === 1) {
            return 'Organic Search';
        }

        if (preg_match('/facebook\.|instagram\.|linkedin\.|twitter\.|x\.com/', $referrer) === 1) {
            return 'Social';
        }

        if ($referrer !== '') {
            return 'Referral';
        }

        return 'Direct';
    }

    public function supportedChannels(): array
    {
        return self::CHANNELS;
    }
}
