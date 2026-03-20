<?php

declare(strict_types=1);

namespace app\admin\service;

final class AdminViewService
{
    public function render(string $pageTitle, string $contentTemplate, array $user, string $csrfToken, array $extra = []): string
    {
        $viewData = array_merge($extra, [
            'page_title' => $pageTitle,
            'content_template' => $contentTemplate,
            'user' => $user,
            'csrf_token' => $csrfToken,
            'nav_items' => (new AdminModuleRegistry())->nav(),
        ]);

        ob_start();
        require root_path('app/admin/view/layout/admin.html');

        return (string) ob_get_clean();
    }
}
