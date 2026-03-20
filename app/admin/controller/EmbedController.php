<?php

declare(strict_types=1);

namespace app\admin\controller;

final class EmbedController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('嵌入代码', 'app/admin/view/embed/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->embedData($this->currentUser())]);
    }
}
