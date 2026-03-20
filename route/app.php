<?php

declare(strict_types=1);

use app\admin\controller\AuthController;
use app\admin\controller\PasswordController;
use app\common\middleware\AdminAuthMiddleware;
use app\common\middleware\OperationLogMiddleware;
use app\common\middleware\RolePermissionMiddleware;
use app\install\controller\InstallController;

$webRoutes = [
    ['GET', '/install', InstallController::class, 'index', []],
    ['POST', '/install/check-env', InstallController::class, 'checkEnv', []],
    ['POST', '/install/test-db', InstallController::class, 'testDb', []],
    ['POST', '/install/execute', InstallController::class, 'execute', []],
    ['GET', '/admin/login', AuthController::class, 'loginPage', []],
    ['POST', '/admin/login', AuthController::class, 'login', [OperationLogMiddleware::class]],
    ['POST', '/admin/logout', AuthController::class, 'logout', [AdminAuthMiddleware::class, RolePermissionMiddleware::class, OperationLogMiddleware::class]],
    ['GET', '/admin/password', PasswordController::class, 'index', [AdminAuthMiddleware::class, RolePermissionMiddleware::class]],
    ['POST', '/admin/password', PasswordController::class, 'update', [AdminAuthMiddleware::class, RolePermissionMiddleware::class, OperationLogMiddleware::class]],
    ['GET', '/admin/dashboard', AuthController::class, 'dashboard', [AdminAuthMiddleware::class, RolePermissionMiddleware::class]],
];

$apiRoutes = file_exists(root_path('route/api.php')) ? require root_path('route/api.php') : [];

return array_merge($webRoutes, is_array($apiRoutes) ? $apiRoutes : []);
