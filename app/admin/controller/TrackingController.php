<?php

declare(strict_types=1);

namespace app\admin\controller;

final class TrackingController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('Tracking 配置', 'app/admin/view/tracking/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listTracking($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\TrackingValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveTracking($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'tracking', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
