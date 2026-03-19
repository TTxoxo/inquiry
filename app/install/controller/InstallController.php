<?php

declare(strict_types=1);

namespace app\install\controller;

use app\common\controller\BaseController;
use app\install\service\InstallService;
use app\install\validate\InstallValidator;
use Throwable;

final class InstallController extends BaseController
{
    private InstallService $service;

    private InstallValidator $validator;

    public function __construct()
    {
        $this->service = new InstallService();
        $this->validator = new InstallValidator();
    }

    public function index(): \think\Response
    {
        $data = $this->service->getInstallPageData();
        ob_start();
        $viewData = $data;
        require root_path('app/install/view/index.html');
        $content = (string) ob_get_clean();

        return $this->view($content);
    }

    public function checkEnv(array $input): \think\Response
    {
        if ($this->service->isInstalled()) {
            return $this->error(4013, 'Install locked');
        }

        $errors = $this->validator->validateCheckEnv();
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->success($this->service->checkEnvironment());
    }

    public function testDb(array $input): \think\Response
    {
        if ($this->service->isInstalled()) {
            return $this->error(4013, 'Install locked');
        }

        $errors = $this->validator->validateTestDb($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        try {
            return $this->success($this->service->testDatabase($input));
        } catch (Throwable $exception) {
            return $this->mapException($exception);
        }
    }

    public function execute(array $input): \think\Response
    {
        if ($this->service->isInstalled()) {
            return $this->error(4013, 'Install locked');
        }

        $errors = $this->validator->validateExecute($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        try {
            return $this->success($this->service->executeInstall($input));
        } catch (Throwable $exception) {
            return $this->mapException($exception);
        }
    }

    private function mapException(Throwable $exception): \think\Response
    {
        $code = (int) $exception->getCode();
        if ($code === 4012) {
            return $this->error(4012, 'Database connection failed');
        }

        if (in_array($exception->getMessage(), ['Write env failed', 'Install schema failed', 'Create admin failed', 'Write install lock failed', 'Install locked'], true)) {
            return $this->error(4013, $exception->getMessage());
        }

        return $this->error(5000, $exception->getMessage() === '' ? 'Install failed' : $exception->getMessage());
    }
}
