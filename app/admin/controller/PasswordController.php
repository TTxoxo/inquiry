<?php

declare(strict_types=1);

namespace app\admin\controller;

use app\admin\service\AdminModuleRegistry;
use app\admin\service\AuthSessionService;
use app\common\controller\BaseController;
use app\model\User;
use RuntimeException;
use Throwable;

final class PasswordController extends BaseController
{
    public function __construct(
        private readonly AuthSessionService $sessionService = new AuthSessionService(),
        private readonly User $userModel = new User()
    ) {
    }

    public function index(): \think\Response
    {
        $user = $this->sessionService->user();
        if ($user === null) {
            return \think\Response::redirect('/admin/login');
        }

        $viewData = [
            'page_title' => 'Change Password',
            'content_template' => root_path('app/admin/view/password/index.html'),
            'user' => $user,
            'csrf_token' => $this->sessionService->csrfToken(),
            'nav_items' => (new AdminModuleRegistry())->nav(),
        ];
        ob_start();
        require root_path('app/admin/view/layout/admin.html');
        $content = (string) ob_get_clean();

        return $this->view($content);
    }

    public function update(array $input): \think\Response
    {
        $sessionUser = $this->sessionService->user();
        if ($sessionUser === null) {
            return $this->error(4010, 'Unauthenticated');
        }

        $currentPassword = (string) ($input['current_password'] ?? '');
        $newPassword = (string) ($input['new_password'] ?? '');
        $confirmPassword = (string) ($input['confirm_password'] ?? '');

        $errors = [];
        if ($currentPassword === '') {
            $errors['current_password'] = 'Current password is required';
        }
        if ($newPassword === '') {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'New password must be at least 8 characters';
        }
        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'Confirm password is required';
        } elseif ($confirmPassword !== $newPassword) {
            $errors['confirm_password'] = 'Confirm password does not match';
        }
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        try {
            $user = $this->userModel->findById((int) $sessionUser['user_id']);
            if ($user === null || !password_verify($currentPassword, (string) $user['password'])) {
                return $this->error(4012, 'Current password incorrect');
            }

            $this->userModel->updatePassword((int) $user['id'], password_hash($newPassword, PASSWORD_DEFAULT), date('Y-m-d H:i:s'));

            return $this->success();
        } catch (Throwable $exception) {
            return $this->mapException($exception);
        }
    }

    private function mapException(Throwable $exception): \think\Response
    {
        if ($exception instanceof RuntimeException && $exception->getCode() === 5001) {
            return $this->error(5001, 'Database connection failed');
        }

        return $this->error(5000, $exception->getMessage() === '' ? 'Password update failed' : $exception->getMessage());
    }
}
