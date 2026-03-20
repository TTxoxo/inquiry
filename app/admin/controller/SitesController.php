<?php

declare(strict_types=1);

namespace app\admin\controller;

final class SitesController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('站点管理', 'app/admin/view/sites/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listSites($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\SitesValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveSite($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'sites', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
