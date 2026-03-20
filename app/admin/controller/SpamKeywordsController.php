<?php

declare(strict_types=1);

namespace app\admin\controller;

final class SpamKeywordsController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('垃圾关键词', 'app/admin/view/spam-keywords/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listSpamKeywords($this->currentUser())]);
    }
    public function save(array $input): \think\Response
    {
        $errors = (new \app\admin\validate\SpamKeywordsValidate())->check($input);
        if ($errors !== []) {
            return $this->error(422, 'Validation failed', [], $errors);
        }

        return $this->handleAction(fn (): array => ['item' => $this->crudService->saveSpamKeyword($input)]);
    }
    public function delete(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ((function () use ($input): array {
            $this->crudService->deleteRecord($this->currentUser(), 'spam-keywords', (int) ($input['id'] ?? 0));
            return [];
        })()));
    }
}
