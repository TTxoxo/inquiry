<?php

declare(strict_types=1);

use app\admin\controller\AuthController;
use app\admin\controller\EmailLogsController;
use app\admin\controller\EmbedController;
use app\admin\controller\FieldsController;
use app\admin\controller\FormsController;
use app\admin\controller\InquiriesController;
use app\admin\controller\LoginLogsController;
use app\admin\controller\NotifyEmailsController;
use app\admin\controller\OperationLogsController;
use app\admin\controller\PasswordController;
use app\admin\controller\SitesController;
use app\admin\controller\SiteUsersController;
use app\admin\controller\SmtpController;
use app\admin\controller\SpamKeywordsController;
use app\admin\controller\TrackingController;
use app\common\middleware\AdminAuthMiddleware;
use app\common\middleware\OperationLogMiddleware;
use app\common\middleware\RolePermissionMiddleware;
use app\install\controller\InstallController;

$admin = [AdminAuthMiddleware::class, RolePermissionMiddleware::class];
$postAdmin = [AdminAuthMiddleware::class, RolePermissionMiddleware::class, OperationLogMiddleware::class];

$webRoutes = [
    ['GET', '/install', InstallController::class, 'index', []],
    ['POST', '/install/check-env', InstallController::class, 'checkEnv', []],
    ['POST', '/install/test-db', InstallController::class, 'testDb', []],
    ['POST', '/install/execute', InstallController::class, 'execute', []],
    ['GET', '/admin/login', AuthController::class, 'loginPage', []],
    ['POST', '/admin/login', AuthController::class, 'login', [OperationLogMiddleware::class]],
    ['POST', '/admin/logout', AuthController::class, 'logout', $postAdmin],
    ['GET', '/admin/dashboard', AuthController::class, 'dashboard', $admin],
    ['POST', '/admin/dashboard/stats', AuthController::class, 'dashboardStats', $postAdmin],
    ['GET', '/admin/password', PasswordController::class, 'index', $admin],
    ['POST', '/admin/password', PasswordController::class, 'update', $postAdmin],
    ['GET', '/admin/sites', SitesController::class, 'index', $admin],
    ['POST', '/admin/sites/list', SitesController::class, 'list', $postAdmin],
    ['POST', '/admin/sites/save', SitesController::class, 'save', $postAdmin],
    ['POST', '/admin/sites/delete', SitesController::class, 'delete', $postAdmin],
    ['GET', '/admin/site-users', SiteUsersController::class, 'index', $admin],
    ['POST', '/admin/site-users/list', SiteUsersController::class, 'list', $postAdmin],
    ['POST', '/admin/site-users/save', SiteUsersController::class, 'save', $postAdmin],
    ['POST', '/admin/site-users/delete', SiteUsersController::class, 'delete', $postAdmin],
    ['GET', '/admin/forms', FormsController::class, 'index', $admin],
    ['POST', '/admin/forms/list', FormsController::class, 'list', $postAdmin],
    ['POST', '/admin/forms/save', FormsController::class, 'save', $postAdmin],
    ['POST', '/admin/forms/delete', FormsController::class, 'delete', $postAdmin],
    ['GET', '/admin/fields', FieldsController::class, 'index', $admin],
    ['POST', '/admin/fields/list', FieldsController::class, 'list', $postAdmin],
    ['POST', '/admin/fields/save', FieldsController::class, 'save', $postAdmin],
    ['POST', '/admin/fields/delete', FieldsController::class, 'delete', $postAdmin],
    ['GET', '/admin/embed', EmbedController::class, 'index', $admin],
    ['POST', '/admin/embed/list', EmbedController::class, 'list', $postAdmin],
    ['GET', '/admin/inquiries', InquiriesController::class, 'index', $admin],
    ['POST', '/admin/inquiries/list', InquiriesController::class, 'list', $postAdmin],
    ['POST', '/admin/inquiries/detail', InquiriesController::class, 'detail', $postAdmin],
    ['POST', '/admin/inquiries/export', InquiriesController::class, 'export', $postAdmin],
    ['GET', '/admin/smtp', SmtpController::class, 'index', $admin],
    ['POST', '/admin/smtp/list', SmtpController::class, 'list', $postAdmin],
    ['POST', '/admin/smtp/save', SmtpController::class, 'save', $postAdmin],
    ['POST', '/admin/smtp/delete', SmtpController::class, 'delete', $postAdmin],
    ['GET', '/admin/notify-emails', NotifyEmailsController::class, 'index', $admin],
    ['POST', '/admin/notify-emails/list', NotifyEmailsController::class, 'list', $postAdmin],
    ['POST', '/admin/notify-emails/save', NotifyEmailsController::class, 'save', $postAdmin],
    ['POST', '/admin/notify-emails/delete', NotifyEmailsController::class, 'delete', $postAdmin],
    ['GET', '/admin/email-logs', EmailLogsController::class, 'index', $admin],
    ['POST', '/admin/email-logs/list', EmailLogsController::class, 'list', $postAdmin],
    ['POST', '/admin/email-logs/retry', EmailLogsController::class, 'retry', $postAdmin],
    ['GET', '/admin/tracking', TrackingController::class, 'index', $admin],
    ['POST', '/admin/tracking/list', TrackingController::class, 'list', $postAdmin],
    ['POST', '/admin/tracking/save', TrackingController::class, 'save', $postAdmin],
    ['POST', '/admin/tracking/delete', TrackingController::class, 'delete', $postAdmin],
    ['GET', '/admin/spam-keywords', SpamKeywordsController::class, 'index', $admin],
    ['POST', '/admin/spam-keywords/list', SpamKeywordsController::class, 'list', $postAdmin],
    ['POST', '/admin/spam-keywords/save', SpamKeywordsController::class, 'save', $postAdmin],
    ['POST', '/admin/spam-keywords/delete', SpamKeywordsController::class, 'delete', $postAdmin],
    ['GET', '/admin/operation-logs', OperationLogsController::class, 'index', $admin],
    ['POST', '/admin/operation-logs/list', OperationLogsController::class, 'list', $postAdmin],
    ['GET', '/admin/login-logs', LoginLogsController::class, 'index', $admin],
    ['POST', '/admin/login-logs/list', LoginLogsController::class, 'list', $postAdmin],
];

$apiRoutes = file_exists(root_path('route/api.php')) ? require root_path('route/api.php') : [];

return array_merge($webRoutes, is_array($apiRoutes) ? $apiRoutes : []);
