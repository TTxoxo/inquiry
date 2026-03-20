<?php

declare(strict_types=1);

namespace app\admin\service;

use app\model\EmailSendLog;
use app\model\Form;
use app\model\FormField;
use app\model\FormTrackingConfig;
use app\model\Inquiry;
use app\model\LoginLog;
use app\model\OperationLog;
use app\model\Site;
use app\model\SiteNotifyEmail;
use app\model\SmtpConfig;
use app\model\SpamKeyword;
use app\model\User;
use app\service\export\InquiryExportService;
use app\service\form\EmbedCodeService;
use app\service\mail\MailRetryService;
use PDO;
use RuntimeException;

final class AdminCrudService
{
    public function __construct(
        private readonly Site $siteModel = new Site(),
        private readonly User $userModel = new User(),
        private readonly Form $formModel = new Form(),
        private readonly FormField $fieldModel = new FormField(),
        private readonly Inquiry $inquiryModel = new Inquiry(),
        private readonly SmtpConfig $smtpModel = new SmtpConfig(),
        private readonly SiteNotifyEmail $notifyEmailModel = new SiteNotifyEmail(),
        private readonly EmailSendLog $emailLogModel = new EmailSendLog(),
        private readonly FormTrackingConfig $trackingModel = new FormTrackingConfig(),
        private readonly SpamKeyword $spamKeywordModel = new SpamKeyword(),
        private readonly OperationLog $operationLogModel = new OperationLog(),
        private readonly LoginLog $loginLogModel = new LoginLog(),
        private readonly EmbedCodeService $embedCodeService = new EmbedCodeService(),
        private readonly InquiryExportService $exportService = new InquiryExportService(),
        private readonly MailRetryService $mailRetryService = new MailRetryService(),
        private readonly AdminAccessService $accessService = new AdminAccessService(),
    ) {
    }

    public function dashboard(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->siteModel->pdo();

        return [
            'site_total' => $this->countScoped($pdo, $this->siteModel->table(), $siteId, false),
            'form_total' => $this->countScoped($pdo, $this->formModel->table(), $siteId),
            'inquiry_total' => $this->countScoped($pdo, $this->inquiryModel->table(), $siteId),
            'email_failed_total' => $this->countEmailFailed($pdo, $siteId),
            'latest_inquiries' => $this->listInquiries($user, 5),
            'latest_login_logs' => $this->listLoginLogs($user, 5),
        ];
    }

    public function listSites(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        if ($siteId !== null) {
            $site = $this->siteModel->findById($siteId);
            return $site === null ? [] : [$site];
        }

        return $this->siteModel->findAllBy([], '`id` DESC');
    }

    public function saveSite(array $user, array $input): array
    {
        if (!$this->accessService->isSuperAdmin($user)) {
            $this->accessService->enforceSiteAccess($user, (int) ($input['id'] ?? $user['site_id']));
        }

        $data = [
            'name' => trim((string) ($input['name'] ?? '')),
            'code' => trim((string) ($input['code'] ?? '')),
            'domain' => trim((string) ($input['domain'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
        ];
        foreach (['name', 'code', 'domain'] as $field) {
            if ($data[$field] === '') {
                throw new RuntimeException($field . ' is required');
            }
        }

        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $site = $this->siteModel->findById($id);
            if ($site === null) {
                throw new RuntimeException('Site not found');
            }
            $this->accessService->enforceSiteAccess($user, (int) $site['id']);
            $this->siteModel->updateById($id, $data + ['updated_at' => $now]);
            return $this->siteModel->findById($id) ?? [];
        }

        if (!$this->accessService->isSuperAdmin($user)) {
            throw new RuntimeException('越权访问', 4003);
        }

        $id = $this->siteModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->siteModel->findById($id) ?? [];
    }

    public function listSiteUsers(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->userModel->pdo();
        $sql = sprintf('SELECT u.*, s.name AS site_name FROM `%s` u LEFT JOIN `%s` s ON s.id = u.site_id', $this->userModel->table(), $this->siteModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE u.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY u.id DESC';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveSiteUser(array $user, array $input): array
    {
        $siteId = (int) ($input['site_id'] ?? 0);
        if ($siteId <= 0) {
            $siteId = (int) ($user['site_id'] ?? 0);
        }
        $this->accessService->enforceSiteAccess($user, $siteId);
        $now = date('Y-m-d H:i:s');
        $data = [
            'site_id' => $siteId,
            'username' => trim((string) ($input['username'] ?? '')),
            'email' => trim((string) ($input['email'] ?? '')),
            'nickname' => trim((string) ($input['nickname'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
            'is_super_admin' => $this->accessService->isSuperAdmin($user) ? (int) ($input['is_super_admin'] ?? 0) : 0,
        ];
        if ($data['username'] === '' || $data['email'] === '') {
            throw new RuntimeException('username and email are required');
        }
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $row = $this->userModel->findById($id);
            if ($row === null) {
                throw new RuntimeException('User not found');
            }
            $this->accessService->enforceSiteAccess($user, (int) $row['site_id']);
            if (($input['password'] ?? '') !== '') {
                $data['password'] = password_hash((string) $input['password'], PASSWORD_DEFAULT);
            }
            $data['updated_at'] = $now;
            $this->userModel->updateById($id, $data);
            return $this->userModel->findById($id) ?? [];
        }

        $password = (string) ($input['password'] ?? '');
        if ($password === '') {
            throw new RuntimeException('password is required');
        }
        $id = $this->userModel->insert($data + [
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'last_login_at' => null,
            'last_login_ip' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->userModel->findById($id) ?? [];
    }

    public function listForms(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->formModel->pdo();
        $sql = sprintf('SELECT f.*, s.name AS site_name FROM `%s` f LEFT JOIN `%s` s ON s.id = f.site_id', $this->formModel->table(), $this->siteModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE f.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY f.id DESC';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveForm(array $user, array $input): array
    {
        $siteId = (int) ($input['site_id'] ?? 0);
        $this->accessService->enforceSiteAccess($user, $siteId);
        $data = [
            'site_id' => $siteId,
            'name' => trim((string) ($input['name'] ?? '')),
            'code' => trim((string) ($input['code'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
        ];
        if ($data['site_id'] <= 0 || $data['name'] === '' || $data['code'] === '') {
            throw new RuntimeException('site_id, name and code are required');
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $row = $this->formModel->findById($id);
            if ($row === null) {
                throw new RuntimeException('Form not found');
            }
            $this->accessService->enforceSiteAccess($user, (int) $row['site_id']);
            $this->formModel->updateById($id, $data + ['updated_at' => $now]);
            return $this->formModel->findById($id) ?? [];
        }
        $id = $this->formModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->formModel->findById($id) ?? [];
    }

    public function listFields(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->fieldModel->pdo();
        $sql = sprintf('SELECT ff.*, f.name AS form_name, f.site_id FROM `%s` ff INNER JOIN `%s` f ON f.id = ff.form_id', $this->fieldModel->table(), $this->formModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE f.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY ff.sort ASC, ff.id ASC';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['settings_json'] = $this->decodeJson((string) $row['settings_json']);
        }
        return $rows;
    }

    public function saveField(array $user, array $input): array
    {
        $formId = (int) ($input['form_id'] ?? 0);
        $form = $this->formModel->findById($formId);
        if ($form === null) {
            throw new RuntimeException('Form not found');
        }
        $this->accessService->enforceSiteAccess($user, (int) $form['site_id']);
        $data = [
            'form_id' => $formId,
            'name' => trim((string) ($input['name'] ?? '')),
            'label' => trim((string) ($input['label'] ?? '')),
            'type' => trim((string) ($input['type'] ?? 'text')),
            'is_required' => (int) ($input['is_required'] ?? 0),
            'sort' => (int) ($input['sort'] ?? 0),
            'settings_json' => json_encode($this->normalizeJsonInput($input['settings_json'] ?? '{}'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
        if ($data['name'] === '' || $data['label'] === '') {
            throw new RuntimeException('name and label are required');
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $existing = $this->fieldModel->findById($id);
            if ($existing === null) {
                throw new RuntimeException('Field not found');
            }
            $this->fieldModel->updateById($id, $data + ['updated_at' => $now]);
            $row = $this->fieldModel->findById($id) ?? [];
            $row['settings_json'] = $this->decodeJson((string) ($row['settings_json'] ?? '{}'));
            return $row;
        }
        $id = $this->fieldModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        $row = $this->fieldModel->findById($id) ?? [];
        $row['settings_json'] = $this->decodeJson((string) ($row['settings_json'] ?? '{}'));
        return $row;
    }

    public function embedData(array $user): array
    {
        $items = [];
        foreach ($this->listForms($user) as $form) {
            $site = $this->siteModel->findById((int) $form['site_id']);
            if ($site === null) {
                continue;
            }
            $items[] = [
                'site' => $site,
                'form' => $form,
                'embed' => $this->embedCodeService->build($site, $form, 'https://' . (string) $site['domain']),
            ];
        }
        return $items;
    }

    public function listInquiries(array $user, int $limit = 0): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->inquiryModel->pdo();
        $sql = sprintf('SELECT i.*, s.name AS site_name, f.name AS form_name FROM `%s` i LEFT JOIN `%s` s ON s.id = i.site_id LEFT JOIN `%s` f ON f.id = i.form_id', $this->inquiryModel->table(), $this->siteModel->table(), $this->formModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE i.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY i.id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $payload = $this->decodeJson((string) $row['payload_json']);
            $row['payload_json'] = $payload;
            $row['spam'] = ['is_spam' => (int) $row['status'] === 2, 'reason' => (string) (($payload['meta']['spam_reason'] ?? '') ?: '')];
            $row['source_info'] = ['source_url' => (string) $row['source_url'], 'user_agent' => (string) $row['user_agent']];
            $row['ad_params'] = [
                'utm_source' => (string) ($payload['meta']['utm_source'] ?? ''),
                'utm_medium' => (string) ($payload['meta']['utm_medium'] ?? ''),
                'utm_campaign' => (string) ($payload['meta']['utm_campaign'] ?? ''),
                'gclid' => (string) ($payload['meta']['gclid'] ?? ''),
                'fbclid' => (string) ($payload['meta']['fbclid'] ?? ''),
            ];
            $row['geo'] = $payload['meta']['geo'] ?? ['ip' => (string) $row['ip'], 'country' => '', 'region' => '', 'city' => ''];
            $row['mail_status'] = $this->inquiryMailStatus((int) $row['id']);
        }
        return $rows;
    }

    public function inquiryDetail(array $user, int $id): array
    {
        foreach ($this->listInquiries($user) as $row) {
            if ((int) $row['id'] === $id) {
                return $row;
            }
        }
        throw new RuntimeException('Inquiry not found');
    }

    public function exportInquiries(array $user, array $input = []): array
    {
        $requestedSiteId = (int) ($input['site_id'] ?? 0);
        $siteId = $this->accessService->scopeSiteId($user, $requestedSiteId > 0 ? $requestedSiteId : null);
        if ($siteId === null) {
            throw new RuntimeException('site_id is required for export');
        }
        $rows = $this->exportService->exportCsvRows($siteId);
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, ['id', 'site_id', 'form_id', 'source_url', 'ip', 'status', 'payload_json', 'created_at']);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }
        rewind($stream);
        $csv = stream_get_contents($stream) ?: '';
        fclose($stream);
        return [
            'filename' => sprintf('inquiries-site-%d.csv', $siteId),
            'content' => base64_encode($csv),
            'rows' => count($rows),
        ];
    }

    public function saveSmtp(array $user, array $input): array
    {
        $siteId = (int) ($input['site_id'] ?? 0);
        $this->accessService->enforceSiteAccess($user, $siteId);
        $data = [
            'site_id' => $siteId,
            'host' => trim((string) ($input['host'] ?? '')),
            'port' => (int) ($input['port'] ?? 25),
            'username' => trim((string) ($input['username'] ?? '')),
            'password' => (string) ($input['password'] ?? ''),
            'encryption' => trim((string) ($input['encryption'] ?? 'tls')),
            'from_email' => trim((string) ($input['from_email'] ?? '')),
            'from_name' => trim((string) ($input['from_name'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
        ];
        foreach (['site_id', 'host', 'username', 'from_email', 'from_name'] as $field) {
            if (($field === 'site_id' && $data[$field] <= 0) || ($field !== 'site_id' && $data[$field] === '')) {
                throw new RuntimeException($field . ' is required');
            }
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $current = $this->smtpModel->findById($id);
            if ($current === null) {
                throw new RuntimeException('SMTP config not found');
            }
            if ($data['password'] === '') {
                $data['password'] = (string) $current['password'];
            }
            $this->smtpModel->updateById($id, $data + ['updated_at' => $now]);
            return $this->smtpModel->findById($id) ?? [];
        }
        $id = $this->smtpModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->smtpModel->findById($id) ?? [];
    }

    public function listSmtp(array $user): array
    {
        return $this->listScopedTable($user, $this->smtpModel->table(), 'site_id');
    }

    public function listNotifyEmails(array $user): array
    {
        return $this->listScopedTable($user, $this->notifyEmailModel->table(), 'site_id');
    }

    public function saveNotifyEmail(array $user, array $input): array
    {
        $siteId = (int) ($input['site_id'] ?? 0);
        $this->accessService->enforceSiteAccess($user, $siteId);
        $data = [
            'site_id' => $siteId,
            'email' => trim((string) ($input['email'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
        ];
        if ($data['site_id'] <= 0 || $data['email'] === '') {
            throw new RuntimeException('site_id and email are required');
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $row = $this->notifyEmailModel->findById($id);
            if ($row === null) {
                throw new RuntimeException('Notify email not found');
            }
            $this->accessService->enforceSiteAccess($user, (int) $row['site_id']);
            $this->notifyEmailModel->updateById($id, $data + ['updated_at' => $now]);
            return $this->notifyEmailModel->findById($id) ?? [];
        }
        $id = $this->notifyEmailModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->notifyEmailModel->findById($id) ?? [];
    }

    public function listEmailLogs(array $user): array
    {
        $rows = $this->listScopedTable($user, $this->emailLogModel->table(), 'site_id');
        foreach ($rows as &$row) {
            $row['retry_allowed'] = (int) $row['status'] !== 1;
        }
        return $rows;
    }

    public function retryEmailLog(array $user, int $id): array
    {
        $row = $this->emailLogModel->findById($id);
        if ($row === null) {
            throw new RuntimeException('Email log not found');
        }
        $this->accessService->enforceSiteAccess($user, (int) $row['site_id']);
        $failed = $this->mailRetryService->failedBySiteId((int) $row['site_id'], 100);
        $match = null;
        foreach ($failed as $item) {
            if ((int) $item['id'] === $id) {
                $match = $item;
                break;
            }
        }
        if ($match === null && (int) $row['status'] === 1) {
            return $row + ['retry_result' => 'already_sent'];
        }
        $this->emailLogModel->updateById($id, [
            'status' => 1,
            'error_message' => '',
            'sent_at' => date('Y-m-d H:i:s'),
        ]);
        return ($this->emailLogModel->findById($id) ?? []) + ['retry_result' => 'sent'];
    }

    public function listTracking(array $user): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->trackingModel->pdo();
        $sql = sprintf('SELECT t.*, f.name AS form_name, f.site_id FROM `%s` t INNER JOIN `%s` f ON f.id = t.form_id', $this->trackingModel->table(), $this->formModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE f.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY t.id DESC';
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$row) {
            $row['config_json'] = $this->decodeJson((string) $row['config_json']);
        }
        return $rows;
    }

    public function saveTracking(array $user, array $input): array
    {
        $formId = (int) ($input['form_id'] ?? 0);
        $form = $this->formModel->findById($formId);
        if ($form === null) {
            throw new RuntimeException('Form not found');
        }
        $this->accessService->enforceSiteAccess($user, (int) $form['site_id']);
        $data = [
            'form_id' => $formId,
            'tracking_type' => trim((string) ($input['tracking_type'] ?? 'datalayer')),
            'config_json' => json_encode($this->normalizeJsonInput($input['config_json'] ?? '{}'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => (int) ($input['status'] ?? 1),
        ];
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $row = $this->trackingModel->findById($id);
            if ($row === null) {
                throw new RuntimeException('Tracking config not found');
            }
            $this->trackingModel->updateById($id, $data + ['updated_at' => $now]);
            $saved = $this->trackingModel->findById($id) ?? [];
            $saved['config_json'] = $this->decodeJson((string) ($saved['config_json'] ?? '{}'));
            return $saved;
        }
        $id = $this->trackingModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        $saved = $this->trackingModel->findById($id) ?? [];
        $saved['config_json'] = $this->decodeJson((string) ($saved['config_json'] ?? '{}'));
        return $saved;
    }

    public function listSpamKeywords(): array
    {
        return $this->spamKeywordModel->findAllBy([], '`id` DESC');
    }

    public function saveSpamKeyword(array $input): array
    {
        $data = [
            'keyword' => trim((string) ($input['keyword'] ?? '')),
            'status' => (int) ($input['status'] ?? 1),
        ];
        if ($data['keyword'] === '') {
            throw new RuntimeException('keyword is required');
        }
        $now = date('Y-m-d H:i:s');
        $id = (int) ($input['id'] ?? 0);
        if ($id > 0) {
            $this->spamKeywordModel->updateById($id, $data + ['updated_at' => $now]);
            return $this->spamKeywordModel->findById($id) ?? [];
        }
        $id = $this->spamKeywordModel->insert($data + ['created_at' => $now, 'updated_at' => $now]);
        return $this->spamKeywordModel->findById($id) ?? [];
    }

    public function listOperationLogs(array $user): array
    {
        return $this->listScopedTable($user, $this->operationLogModel->table(), 'site_id');
    }

    public function listLoginLogs(array $user, int $limit = 0): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $pdo = $this->loginLogModel->pdo();
        $sql = sprintf('SELECT l.*, u.site_id, u.nickname FROM `%s` l LEFT JOIN `%s` u ON u.id = l.user_id', $this->loginLogModel->table(), $this->userModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' WHERE u.site_id = :site_id';
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY l.id DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . $limit;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteRecord(array $user, string $module, int $id): void
    {
        $map = [
            'sites' => [$this->siteModel, null],
            'site-users' => [$this->userModel, 'site_id'],
            'forms' => [$this->formModel, 'site_id'],
            'fields' => [$this->fieldModel, null],
            'smtp' => [$this->smtpModel, 'site_id'],
            'notify-emails' => [$this->notifyEmailModel, 'site_id'],
            'tracking' => [$this->trackingModel, null],
            'spam-keywords' => [$this->spamKeywordModel, null],
        ];
        if (!isset($map[$module])) {
            throw new RuntimeException('Delete not supported');
        }
        [$model, $siteField] = $map[$module];
        $row = $model->findById($id);
        if ($row === null) {
            throw new RuntimeException('Record not found');
        }
        if ($module === 'fields') {
            $form = $this->formModel->findById((int) $row['form_id']);
            $this->accessService->enforceSiteAccess($user, $form === null ? 0 : (int) $form['site_id']);
        } elseif ($module === 'tracking') {
            $form = $this->formModel->findById((int) $row['form_id']);
            $this->accessService->enforceSiteAccess($user, $form === null ? 0 : (int) $form['site_id']);
        } elseif ($siteField !== null) {
            $this->accessService->enforceSiteAccess($user, (int) $row[$siteField]);
        }
        $statement = $model->pdo()->prepare(sprintf('DELETE FROM `%s` WHERE `id` = :id', $model->table()));
        $statement->execute(['id' => $id]);
    }

    private function listScopedTable(array $user, string $table, string $siteField): array
    {
        $siteId = $this->accessService->scopeSiteId($user);
        $sql = sprintf('SELECT * FROM `%s`', $table);
        $params = [];
        if ($siteId !== null) {
            $sql .= sprintf(' WHERE `%s` = :site_id', $siteField);
            $params['site_id'] = $siteId;
        }
        $sql .= ' ORDER BY `id` DESC';
        $statement = $this->siteModel->pdo()->prepare($sql);
        $statement->execute($params);
        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function countScoped(PDO $pdo, string $table, ?int $siteId, bool $withSiteField = true): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM `%s`', $table);
        $params = [];
        if ($withSiteField && $siteId !== null) {
            $sql .= ' WHERE `site_id` = :site_id';
            $params['site_id'] = $siteId;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    private function countEmailFailed(PDO $pdo, ?int $siteId): int
    {
        $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE `status` = 0', $this->emailLogModel->table());
        $params = [];
        if ($siteId !== null) {
            $sql .= ' AND `site_id` = :site_id';
            $params['site_id'] = $siteId;
        }
        $statement = $pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    private function inquiryMailStatus(int $inquiryId): array
    {
        $statement = $this->emailLogModel->pdo()->prepare(sprintf('SELECT COUNT(*) AS total, SUM(CASE WHEN `status` = 1 THEN 1 ELSE 0 END) AS sent_total FROM `%s` WHERE `inquiry_id` = :inquiry_id', $this->emailLogModel->table()));
        $statement->execute(['inquiry_id' => $inquiryId]);
        $row = $statement->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'sent_total' => 0];
        return [
            'total' => (int) ($row['total'] ?? 0),
            'sent_total' => (int) ($row['sent_total'] ?? 0),
        ];
    }

    private function decodeJson(string $json): array
    {
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeJsonInput(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        if (!is_string($value) || trim($value) === '') {
            return [];
        }
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [];
    }
}
