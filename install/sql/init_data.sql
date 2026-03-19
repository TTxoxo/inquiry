INSERT INTO `__PREFIX__sites` (`id`, `name`, `code`, `domain`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Default Site', 'default', 'localhost', 1, NOW(), NOW());

INSERT INTO `__PREFIX__forms` (`id`, `site_id`, `name`, `code`, `description`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Default Inquiry Form', 'default_inquiry', 'Default inquiry form created by installer', 1, NOW(), NOW());

INSERT INTO `__PREFIX__form_fields` (`form_id`, `name`, `label`, `type`, `is_required`, `sort`, `settings_json`, `created_at`, `updated_at`) VALUES
(1, 'name', 'Name', 'text', 1, 10, JSON_OBJECT('placeholder', 'Your name'), NOW(), NOW()),
(1, 'email', 'Email', 'email', 1, 20, JSON_OBJECT('placeholder', 'Your email'), NOW(), NOW()),
(1, 'message', 'Message', 'textarea', 1, 30, JSON_OBJECT('placeholder', 'Your message'), NOW(), NOW());

INSERT INTO `__PREFIX__site_notify_emails` (`site_id`, `email`, `status`, `created_at`, `updated_at`) VALUES
(1, 'admin@example.com', 1, NOW(), NOW());

INSERT INTO `__PREFIX__spam_keywords` (`keyword`, `status`, `created_at`, `updated_at`) VALUES
('casino', 1, NOW(), NOW()),
('loan', 1, NOW(), NOW());
