<?php

declare(strict_types=1);

namespace app\admin\controller;

final class FormsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('表单管理', 'app/admin/view/forms/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listForms($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\FormsValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveForm($this->currentUser(), $input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'forms', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
