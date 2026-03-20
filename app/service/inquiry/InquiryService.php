<?php

declare(strict_types=1);

namespace app\service\inquiry;

use app\model\Inquiry;
use app\service\channel\ChannelDetectService;
use app\service\form\FormFieldService;
use app\service\form\FormService;
use app\service\ip\ClientIpService;
use app\service\ip\GeoIpService;
use app\service\mail\InquiryMailService;
use app\service\site\SiteService;
use app\service\spam\SpamGuardService;
use app\service\tracking\DataLayerPayloadService;
use app\service\tracking\TrackingConfigService;
use RuntimeException;

final class InquiryService
{
    public function __construct(
        private readonly Inquiry $inquiryModel = new Inquiry(),
        private readonly SiteService $siteService = new SiteService(),
        private readonly FormService $formService = new FormService(),
        private readonly FormFieldService $formFieldService = new FormFieldService(),
        private readonly InquiryNoGenerator $inquiryNoGenerator = new InquiryNoGenerator(),
        private readonly SpamGuardService $spamGuardService = new SpamGuardService(),
        private readonly ClientIpService $clientIpService = new ClientIpService(),
        private readonly GeoIpService $geoIpService = new GeoIpService(),
        private readonly ChannelDetectService $channelDetectService = new ChannelDetectService(),
        private readonly InquiryMailService $inquiryMailService = new InquiryMailService(),
        private readonly TrackingConfigService $trackingConfigService = new TrackingConfigService(),
        private readonly DataLayerPayloadService $dataLayerPayloadService = new DataLayerPayloadService()
    ) {
    }

    public function submit(string $siteCode, string $formCode, array $payload, array $server = [], array $query = []): array
    {
        $site = $this->siteService->findByCode($siteCode);
        if ($site === null) {
            throw new RuntimeException('Site not found');
        }

        $form = $this->formService->findActiveByCode($formCode);
        if ($form === null || (int) $form['site_id'] !== (int) $site['id']) {
            throw new RuntimeException('Form not found');
        }

        $requiredFieldNames = $this->formFieldService->requiredFieldNames((int) $form['id']);
        $errors = [];
        foreach ($requiredFieldNames as $fieldName) {
            $value = $payload[$fieldName] ?? null;
            if (!is_scalar($value) || trim((string) $value) === '') {
                $errors[$fieldName] = ucfirst($fieldName) . ' is required';
            }
        }
        if ($errors !== []) {
            throw new RuntimeException('Validation failed: ' . json_encode($errors, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $ip = $this->clientIpService->detect($server);
        $channelContext = [
            'utm_source' => (string) ($query['utm_source'] ?? ''),
            'utm_medium' => (string) ($query['utm_medium'] ?? ''),
            'utm_campaign' => (string) ($query['utm_campaign'] ?? ''),
            'gclid' => (string) ($query['gclid'] ?? ''),
            'fbclid' => (string) ($query['fbclid'] ?? ''),
            'referrer' => (string) ($server['HTTP_REFERER'] ?? ''),
        ];
        $channel = $this->channelDetectService->detect($channelContext);
        $geo = $this->geoIpService->lookup($ip);

        $spamInspection = $this->spamGuardService->inspect($payload);
        $enrichedPayload = [
            'inquiry_no' => $this->inquiryNoGenerator->generate((int) $site['id'], (int) $form['id']),
            'fields' => $this->normalizeFields($payload),
            'meta' => [
                'channel' => $channel,
                'geo' => $geo,
                'utm_source' => $channelContext['utm_source'],
                'utm_medium' => $channelContext['utm_medium'],
                'utm_campaign' => $channelContext['utm_campaign'],
                'gclid' => $channelContext['gclid'],
                'fbclid' => $channelContext['fbclid'],
                'referrer' => $channelContext['referrer'],
            ],
        ];

        $now = date('Y-m-d H:i:s');
        $inquiryId = $this->inquiryModel->insert([
            'site_id' => (int) $site['id'],
            'form_id' => (int) $form['id'],
            'source_url' => mb_substr((string) ($query['source_url'] ?? ($server['REQUEST_URI'] ?? '')), 0, 255),
            'ip' => $ip,
            'user_agent' => mb_substr((string) ($server['HTTP_USER_AGENT'] ?? ''), 0, 500),
            'payload_json' => json_encode($enrichedPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'status' => $spamInspection['is_spam'] ? 2 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $inquiry = $this->inquiryModel->findById($inquiryId);
        if ($inquiry === null) {
            throw new RuntimeException('Inquiry create failed');
        }

        $trackingConfig = $this->trackingConfigService->getByFormId((int) $form['id']) ?? ['config_json' => []];
        $trackingPayload = $this->dataLayerPayloadService->build($trackingConfig, [
            'site_id' => (int) $site['id'],
            'form_id' => (int) $form['id'],
            'channel' => $channel,
            'utm_source' => $channelContext['utm_source'],
            'utm_medium' => $channelContext['utm_medium'],
            'utm_campaign' => $channelContext['utm_campaign'],
            'gclid' => $channelContext['gclid'],
            'fbclid' => $channelContext['fbclid'],
            'referrer' => $channelContext['referrer'],
            'source_url' => $query['source_url'] ?? ($server['REQUEST_URI'] ?? ''),
        ]);
        $mail = $this->inquiryMailService->sendInquiryNotification($inquiry, $site, $enrichedPayload);

        return [
            'inquiry' => $inquiry,
            'tracking_payload' => $trackingPayload,
            'mail' => $mail,
            'spam' => $spamInspection,
            'channel' => $channel,
            'geo' => $geo,
        ];
    }

    private function normalizeFields(array $payload): array
    {
        $fields = [];
        foreach ($payload as $key => $value) {
            $fields[(string) $key] = is_scalar($value) ? trim((string) $value) : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        return $fields;
    }
}
