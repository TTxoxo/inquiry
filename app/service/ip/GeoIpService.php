<?php

declare(strict_types=1);

namespace app\service\ip;

final class GeoIpService
{
    public function lookup(string $ip): array
    {
        if ($ip === '' || $ip === '127.0.0.1' || $ip === '::1') {
            return [
                'ip' => $ip,
                'country' => 'Local',
                'region' => '',
                'city' => '',
            ];
        }

        return [
            'ip' => $ip,
            'country' => 'Unknown',
            'region' => '',
            'city' => '',
        ];
    }
}
