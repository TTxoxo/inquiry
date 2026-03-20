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
        $payloadColumns = $this->collectPayloadColumns($rows);

        return array_map(function (array $row) use ($payloadColumns): array {
            $payload = json_decode((string) $row['payload_json'], true);
            $payload = is_array($payload) ? $payload : [];
            $flattened = $this->flattenPayload($payload);
            $exportRow = [
                'id' => (int) $row['id'],
                'site_id' => (int) $row['site_id'],
                'form_id' => (int) $row['form_id'],
                'source_url' => (string) $row['source_url'],
                'ip' => (string) $row['ip'],
                'user_agent' => (string) $row['user_agent'],
                'status' => (int) $row['status'],
                'payload_json' => (string) $row['payload_json'],
                'created_at' => (string) $row['created_at'],
                'updated_at' => (string) $row['updated_at'],
            ];
            foreach ($payloadColumns as $column) {
                $exportRow['payload.' . $column] = array_key_exists($column, $flattened) ? (string) $flattened[$column] : '';
            }

            return $exportRow;
        }, $rows);
    }

    private function collectPayloadColumns(array $rows): array
    {
        $columns = [];
        foreach ($rows as $row) {
            $payload = json_decode((string) $row['payload_json'], true);
            if (!is_array($payload)) {
                continue;
            }
            foreach (array_keys($this->flattenPayload($payload)) as $column) {
                $columns[$column] = true;
            }
        }
        $names = array_keys($columns);
        sort($names);

        return $names;
    }

    private function flattenPayload(array $payload, string $prefix = ''): array
    {
        $result = [];
        foreach ($payload as $key => $value) {
            $field = $prefix === '' ? (string) $key : $prefix . '.' . $key;
            if (is_array($value)) {
                $result += $this->flattenPayload($value, $field);
                continue;
            }
            $result[$field] = $value === null ? '' : $value;
        }

        return $result;
    }
}
