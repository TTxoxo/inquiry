<?php

declare(strict_types=1);

namespace app\admin\controller;

final class SiteUsersController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('站点用户管理', 'app/admin/view/site-users/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listSiteUsers($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\SiteUsersValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveSiteUser($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'site-users', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
