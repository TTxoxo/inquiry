<?php

declare(strict_types=1);

namespace app\api\service;

use app\model\EmailSendLog;
use app\model\Inquiry;
use app\service\channel\ChannelDetectService;
use app\service\form\FormFieldService;
use app\service\form\FormService;
use app\service\inquiry\InquiryNoGenerator;
use app\service\ip\ClientIpService;
use app\service\ip\GeoIpService;
use app\service\mail\InquiryMailService;
use app\service\site\SiteService;
use app\service\spam\SpamGuardService;
use app\service\tracking\DataLayerPayloadService;
use app\service\tracking\TrackingConfigService;
use RuntimeException;

final class FormApiService
{
    public function __construct(
        private readonly SiteService $siteService = new SiteService(),
        private readonly FormService $formService = new FormService(),
        private readonly FormFieldService $formFieldService = new FormFieldService(),
        private readonly TrackingConfigService $trackingConfigService = new TrackingConfigService(),
        private readonly ClientIpService $clientIpService = new ClientIpService(),
        private readonly GeoIpService $geoIpService = new GeoIpService(),
        private readonly ChannelDetectService $channelDetectService = new ChannelDetectService(),
        private readonly SpamGuardService $spamGuardService = new SpamGuardService(),
        private readonly InquiryNoGenerator $inquiryNoGenerator = new InquiryNoGenerator(),
        private readonly Inquiry $inquiryModel = new Inquiry(),
        private readonly InquiryMailService $inquiryMailService = new InquiryMailService(),
        private readonly EmailSendLog $emailSendLogModel = new EmailSendLog(),
        private readonly DataLayerPayloadService $dataLayerPayloadService = new DataLayerPayloadService()
    ) {
    }

    public function getConfig(string $siteKey, string $formKey, string $mode): array
    {
        [$site, $form] = $this->loadSiteAndForm($siteKey, $formKey);
        $this->ensureAllowedDomain($site, $this->extractRequestHost($_SERVER));
        $fields = $this->formFieldService->listByFormId((int) $form['id']);
        $trackingConfig = $this->trackingConfigService->getByFormId((int) $form['id']) ?? ['config_json' => []];

        return [
            'site_key' => $siteKey,
            'form_key' => $formKey,
            'mode' => $mode,
            'form' => [
                'id' => (int) $form['id'],
                'name' => (string) $form['name'],
                'description' => (string) $form['description'],
            ],
            'fields' => array_map(static function (array $field): array {
                return [
                    'name' => (string) $field['name'],
                    'label' => (string) $field['label'],
                    'type' => (string) $field['type'],
                    'is_required' => (int) $field['is_required'],
                    'sort' => (int) $field['sort'],
                    'settings' => $field['settings_json'] ?? [],
                ];
            }, $fields),
            'style_config' => [
                'theme' => 'default',
                'layout' => $mode,
                'submit_button_text' => 'Submit',
            ],
            'tracking' => [
                'type' => 'datalayer',
                'config' => $trackingConfig['config_json'] ?? [],
                'channels' => $this->channelDetectService->supportedChannels(),
            ],
            'success_message' => 'Thank you, your inquiry has been submitted.',
            'pre_notice' => [
                'enabled' => false,
                'text' => '',
            ],
        ];
    }

    public function submit(array $payload, array $server): array
    {
        [$site, $form] = $this->loadSiteAndForm((string) $payload['site_key'], (string) $payload['form_key']);
        $originHost = $this->extractRequestHost($server);
        $this->ensureAllowedDomain($site, $originHost);

        if (trim((string) ($payload['honeypot'] ?? '')) !== '') {
            throw new RuntimeException('Spam blocked', 4004);
        }

        $ip = $this->clientIpService->detect($server);
        $this->guardIpRateLimit($ip);

        $fields = $this->normalizeFields((array) $payload['fields']);
        $this->validateRequiredFields((int) $form['id'], $fields);
        $this->guardDuplicateSubmission($fields);

        $pageMeta = $this->normalizeScalarMap((array) $payload['page_meta']);
        $trackingMeta = $this->normalizeScalarMap((array) $payload['tracking_meta']);
        $spamInspection = $this->spamGuardService->inspect([
            'fields' => $fields,
            'page_meta' => $pageMeta,
            'tracking_meta' => $trackingMeta,
        ]);
        $geo = $this->geoIpService->lookup($ip);
        $channel = $this->channelDetectService->detect([
            'utm_source' => $trackingMeta['utm_source'] ?? '',
            'utm_medium' => $trackingMeta['utm_medium'] ?? '',
            'utm_campaign' => $trackingMeta['utm_campaign'] ?? '',
            'gclid' => $trackingMeta['gclid'] ?? '',
            'fbclid' => '',
            'referrer' => $pageMeta['referrer_url'] ?? '',
        ]);

        $inquiryPayload = [
            'inquiry_no' => $this->inquiryNoGenerator->generate((int) $site['id'], (int) $form['id']),
            'fields' => $fields,
            'page_meta' => $pageMeta,
            'tracking_meta' => $trackingMeta,
            'source' => [
                'channel' => $channel,
                'site_domain' => (string) $site['domain'],
                'origin_host' => $originHost,
            ],
            'ip_meta' => [
                'ip' => $ip,
                'geo' => $geo,
            ],
            'spam' => $spamInspection,
        ];

        $now = date('Y-m-d H:i:s');
        $inquiryId = $this->inquiryModel->insert([
            'site_id' => (int) $site['id'],
            'form_id' => (int) $form['id'],
            'source_url' => mb_substr((string) ($pageMeta['page_url'] ?? ''), 0, 255),
            'ip' => $ip,
            'user_agent' => mb_substr((string) ($server['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'payload_json' => json_encode($inquiryPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'status' => $spamInspection['is_spam'] ? 2 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $inquiry = $this->inquiryModel->findById($inquiryId);
        if ($inquiry === null) {
            throw new RuntimeException('Inquiry create failed');
        }

        $trackingConfig = $this->trackingConfigService->getByFormId((int) $form['id']) ?? ['config_json' => []];
        $dataLayerPayload = $this->dataLayerPayloadService->build($trackingConfig, [
            'site_id' => (int) $site['id'],
            'form_id' => (int) $form['id'],
            'channel' => $channel,
            'utm_source' => $trackingMeta['utm_source'] ?? '',
            'utm_medium' => $trackingMeta['utm_medium'] ?? '',
            'utm_campaign' => $trackingMeta['utm_campaign'] ?? '',
            'gclid' => $trackingMeta['gclid'] ?? '',
            'fbclid' => '',
            'referrer' => $pageMeta['referrer_url'] ?? '',
            'source_url' => $pageMeta['page_url'] ?? '',
        ]);

        $mailResult = $this->inquiryMailService->sendInquiryNotification($inquiry, $site, $inquiryPayload);
        $mailLogs = $this->emailSendLogModel->findAllBy(['inquiry_id' => (int) $inquiry['id']], '`id` ASC');

        return [
            'success_message' => 'Thank you, your inquiry has been submitted.',
            'data_layer_payload' => $dataLayerPayload,
            'inquiry_id' => (int) $inquiry['id'],
            'inquiry_no' => $inquiryPayload['inquiry_no'],
            'mail' => [
                'sent' => (int) ($mailResult['sent'] ?? 0),
                'failed' => (int) ($mailResult['failed'] ?? 0),
                'logs' => $mailLogs,
            ],
        ];
    }

    public function resolveAllowedOrigin(string $siteKey, string $formKey, array $server): ?string
    {
        try {
            [$site] = $this->loadSiteAndForm($siteKey, $formKey);
        } catch (RuntimeException) {
            return null;
        }

        $origin = trim((string) ($server['HTTP_ORIGIN'] ?? ''));
        if ($origin === '') {
            return null;
        }

        $host = (string) (parse_url($origin, PHP_URL_HOST) ?: '');

        return $this->isAllowedDomain((string) $site['domain'], $host) ? $origin : null;
    }

    private function loadSiteAndForm(string $siteKey, string $formKey): array
    {
        $site = $this->siteService->findByCode(trim($siteKey));
        if ($site === null) {
            throw new RuntimeException('Site not found', 4001);
        }

        $form = $this->formService->findActiveByCode(trim($formKey));
        if ($form === null || (int) $form['site_id'] !== (int) $site['id']) {
            throw new RuntimeException('Form not found', 4002);
        }

        return [$site, $form];
    }


    private function ensureAllowedDomain(array $site, string $originHost): void
    {
        if (!$this->isAllowedDomain((string) $site['domain'], $originHost)) {
            throw new RuntimeException('Domain not allowed', 4003);
        }
    }

    private function validateRequiredFields(int $formId, array $fields): void
    {
        $errors = [];
        foreach ($this->formFieldService->requiredFieldNames($formId) as $fieldName) {
            if (trim((string) ($fields[$fieldName] ?? '')) === '') {
                $errors[$fieldName] = $fieldName . ' is required';
            }
        }

        if ($errors !== []) {
            throw new RuntimeException(json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'Validation failed', 4220);
        }
    }

    private function guardIpRateLimit(string $ip): void
    {
        $statement = $this->inquiryModel->pdo()->prepare(
            sprintf('SELECT COUNT(*) FROM `%s` WHERE `ip` = :ip AND `created_at` >= DATE_SUB(NOW(), INTERVAL 60 SECOND)', $this->inquiryModel->table())
        );
        $statement->execute(['ip' => $ip]);
        if ((int) $statement->fetchColumn() >= 3) {
            throw new RuntimeException('Too many requests', 4291);
        }
    }

    private function guardDuplicateSubmission(array $fields): void
    {
        $email = strtolower(trim((string) ($fields['email'] ?? '')));
        $message = trim((string) ($fields['message'] ?? ''));
        if ($email === '' || $message === '') {
            return;
        }

        $statement = $this->inquiryModel->pdo()->prepare(
            sprintf('SELECT `payload_json` FROM `%s` WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY `id` DESC', $this->inquiryModel->table())
        );
        $statement->execute();
        $count = 0;
        foreach ($statement->fetchAll() as $row) {
            $payload = json_decode((string) ($row['payload_json'] ?? '{}'), true);
            if (!is_array($payload)) {
                continue;
            }
            $savedFields = is_array($payload['fields'] ?? null) ? $payload['fields'] : [];
            $savedEmail = strtolower(trim((string) ($savedFields['email'] ?? '')));
            $savedMessage = trim((string) ($savedFields['message'] ?? ''));
            if ($savedEmail === $email && $savedMessage === $message) {
                $count++;
            }
        }

        if ($count >= 2) {
            throw new RuntimeException('Duplicate inquiry detected', 4292);
        }
    }

    private function normalizeFields(array $fields): array
    {
        return $this->normalizeScalarMap($fields);
    }

    private function normalizeScalarMap(array $input): array
    {
        $normalized = [];
        foreach ($input as $key => $value) {
            $normalized[(string) $key] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $normalized;
    }

    private function extractRequestHost(array $server): string
    {
        $origin = trim((string) ($server['HTTP_ORIGIN'] ?? ''));
        if ($origin !== '') {
            return (string) (parse_url($origin, PHP_URL_HOST) ?: '');
        }

        $referer = trim((string) ($server['HTTP_REFERER'] ?? ''));
        if ($referer !== '') {
            return (string) (parse_url($referer, PHP_URL_HOST) ?: '');
        }

        return '';
    }

    private function isAllowedDomain(string $allowedDomain, string $requestHost): bool
    {
        $siteHost = strtolower(trim($allowedDomain));
        $requestHost = strtolower(trim($requestHost));
        if ($siteHost === '' || $requestHost === '') {
            return false;
        }

        return $siteHost === $requestHost;
    }
}
