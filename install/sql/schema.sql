CREATE TABLE IF NOT EXISTS `__PREFIX__sites` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `domain` varchar(255) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_sites_code` (`code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL DEFAULT 1,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(120) NOT NULL,
  `nickname` varchar(100) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `is_super_admin` tinyint NOT NULL DEFAULT 0,
  `last_login_at` datetime DEFAULT NULL,
  `last_login_ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_users_username` (`username`),
  KEY `idx_users_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__forms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `name` varchar(100) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_forms_code` (`code`),
  KEY `idx_forms_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__form_fields` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `name` varchar(50) NOT NULL,
  `label` varchar(100) NOT NULL,
  `type` varchar(30) NOT NULL,
  `is_required` tinyint NOT NULL DEFAULT 0,
  `sort` int NOT NULL DEFAULT 0,
  `settings_json` json DEFAULT NULL,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_fields_form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__inquiries` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `form_id` bigint unsigned NOT NULL,
  `source_url` varchar(255) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `payload_json` json NOT NULL,
  `status` tinyint NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_inquiries_site_form` (`site_id`,`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__site_notify_emails` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `email` varchar(120) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_site_notify_emails_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__smtp_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `host` varchar(190) NOT NULL,
  `port` int NOT NULL,
  `username` varchar(190) NOT NULL,
  `password` varchar(255) NOT NULL,
  `encryption` varchar(20) NOT NULL DEFAULT 'tls',
  `from_email` varchar(120) NOT NULL,
  `from_name` varchar(120) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_smtp_configs_site_id` (`site_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__form_tracking_configs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `form_id` bigint unsigned NOT NULL,
  `tracking_type` varchar(30) NOT NULL,
  `config_json` json NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_form_tracking_configs_form_id` (`form_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__email_send_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `site_id` bigint unsigned NOT NULL,
  `inquiry_id` bigint unsigned DEFAULT NULL,
  `to_email` varchar(120) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body_html` mediumtext NOT NULL,
  `body_text` text NOT NULL,
  `status` tinyint NOT NULL DEFAULT 0,
  `send_status` tinyint NOT NULL DEFAULT 2,
  `retry_count` tinyint NOT NULL DEFAULT 0,
  `next_retry_at` datetime DEFAULT NULL,
  `error_message` varchar(500) NOT NULL DEFAULT '',
  `sent_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_email_send_logs_site_id` (`site_id`),
  KEY `idx_email_send_logs_retry` (`send_status`,`next_retry_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__operation_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `site_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` bigint unsigned DEFAULT NULL,
  `content` varchar(500) NOT NULL DEFAULT '',
  `ip` varchar(45) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_operation_logs_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__login_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 0,
  `ip` varchar(45) NOT NULL DEFAULT '',
  `user_agent` varchar(500) NOT NULL DEFAULT '',
  `created_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login_logs_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `__PREFIX__spam_keywords` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `keyword` varchar(100) NOT NULL,
  `status` tinyint NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL,
  `updated_at` datetime NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_spam_keywords_keyword` (`keyword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
