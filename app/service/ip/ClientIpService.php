<?php

declare(strict_types=1);

namespace app\service\ip;

final class ClientIpService
{
    public function detect(array $server): string
    {
        $candidates = [];
        $cfIp = $this->normalizeIp((string) ($server['HTTP_CF_CONNECTING_IP'] ?? ''));
        if ($cfIp !== null) {
            $candidates[] = $cfIp;
        }

        $realIp = $this->normalizeIp((string) ($server['HTTP_X_REAL_IP'] ?? ''));
        if ($realIp !== null) {
            $candidates[] = $realIp;
        }

        $xff = (string) ($server['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($xff !== '') {
            foreach (explode(',', $xff) as $item) {
                $valid = $this->normalizeIp($item);
                if ($valid !== null) {
                    $candidates[] = $valid;
                    break;
                }
            }
        }

        $remoteAddr = $this->normalizeIp((string) ($server['REMOTE_ADDR'] ?? ''));
        if ($remoteAddr !== null) {
            $candidates[] = $remoteAddr;
        }

        return $candidates[0] ?? '127.0.0.1';
    }

    private function normalizeIp(string $value): ?string
    {
        $ip = trim($value);
        if ($ip === '' || filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }
}
