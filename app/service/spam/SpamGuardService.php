<?php

declare(strict_types=1);

namespace app\service\spam;

use app\model\SpamKeyword;

final class SpamGuardService
{
    public function __construct(private readonly SpamKeyword $spamKeywordModel = new SpamKeyword())
    {
    }

    public function inspect(array $payload): array
    {
        $flat = strtolower(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '');
        foreach ($this->activeKeywords() as $keyword) {
            if ($keyword !== '' && str_contains($flat, strtolower($keyword))) {
                return [
                    'is_spam' => true,
                    'reason' => 'Matched spam keyword: ' . $keyword,
                ];
            }
        }

        return [
            'is_spam' => false,
            'reason' => '',
        ];
    }

    public function activeKeywords(): array
    {
        $rows = $this->spamKeywordModel->findAllBy(['status' => 1], '`id` ASC');

        return array_map(static fn (array $row): string => (string) $row['keyword'], $rows);
    }
}
