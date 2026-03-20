<?php

declare(strict_types=1);

namespace app\service\export;

use app\model\Inquiry;

final class InquiryExportService
{
    public function __construct(private readonly Inquiry $inquiryModel = new Inquiry())
    {
    }

    public function exportCsvRows(int $siteId): array
    {
        $rows = $this->inquiryModel->findAllBy(['site_id' => $siteId], '`id` ASC');

        return array_map(function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'site_id' => (int) $row['site_id'],
                'form_id' => (int) $row['form_id'],
                'source_url' => (string) $row['source_url'],
                'ip' => (string) $row['ip'],
                'status' => (int) $row['status'],
                'payload_json' => (string) $row['payload_json'],
                'created_at' => (string) $row['created_at'],
            ];
        }, $rows);
    }
}
