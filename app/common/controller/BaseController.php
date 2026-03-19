<?php

declare(strict_types=1);

namespace app\common\controller;

use think\Response;

abstract class BaseController
{
    protected function success(array $data = [], string $message = 'ok', int $statusCode = 200): Response
    {
        return Response::json([
            'code' => 0,
            'message' => $message,
            'data' => $data === [] ? (object) [] : $data,
        ], $statusCode);
    }

    protected function error(int $code, string $message, array $data = [], array $errors = [], int $statusCode = 200): Response
    {
        return Response::json([
            'code' => $code,
            'message' => $message,
            'data' => $data === [] ? (object) [] : $data,
            'errors' => $errors === [] ? (object) [] : $errors,
        ], $statusCode);
    }

    protected function view(string $content, int $statusCode = 200): Response
    {
        return Response::html($content, $statusCode);
    }
}
