<?php

declare(strict_types=1);

namespace app\admin\service;

final class AdminModuleRegistry
{
    public function nav(): array
    {
        return [
            ['path' => '/admin/dashboard', 'label' => '控制台'],
            ['path' => '/admin/sites', 'label' => '站点管理'],
            ['path' => '/admin/site-users', 'label' => '站点用户'],
            ['path' => '/admin/forms', 'label' => '表单管理'],
            ['path' => '/admin/fields', 'label' => '字段管理'],
            ['path' => '/admin/embed', 'label' => '嵌入代码'],
            ['path' => '/admin/inquiries', 'label' => '询盘管理'],
            ['path' => '/admin/smtp', 'label' => 'SMTP 配置'],
            ['path' => '/admin/notify-emails', 'label' => '通知邮箱'],
            ['path' => '/admin/email-logs', 'label' => '邮件日志'],
            ['path' => '/admin/tracking', 'label' => 'Tracking 配置'],
            ['path' => '/admin/spam-keywords', 'label' => '垃圾关键词'],
            ['path' => '/admin/operation-logs', 'label' => '操作日志'],
            ['path' => '/admin/login-logs', 'label' => '登录日志'],
            ['path' => '/admin/password', 'label' => '修改密码'],
        ];
    }
}
