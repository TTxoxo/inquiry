<?php

declare(strict_types=1);

namespace app\admin\controller;

final class InquiriesController extends AdminModuleController
{
    public function index(): \think\Response
    {
        return $this->renderModule('询盘管理', 'app/admin/view/inquiries/index.html');
    }
    public function list(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['items' => $this->crudService->listInquiries($this->currentUser())]);
    }
    public function detail(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => ['item' => $this->crudService->inquiryDetail($this->currentUser(), (int) ($input['id'] ?? 0))]);
    }

    public function export(array $input): \think\Response
    {
        return $this->handleAction(fn (): array => $this->crudService->exportInquiries($this->currentUser(), $input));
    }
}
