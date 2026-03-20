<?php

declare(strict_types=1);

namespace app\api\controller;

use app\api\service\FormApiService;
use app\api\validate\FormConfigValidate;
use app\api\validate\FormSubmitValidate;
use app\common\controller\BaseController;
use RuntimeException;
use think\Response;

final class FormController extends BaseController
{
    public function __construct(
        private readonly FormApiService $formApiService = new FormApiService(),
        private readonly FormConfigValidate $formConfigValidate = new FormConfigValidate(),
        private readonly FormSubmitValidate $formSubmitValidate = new FormSubmitValidate()
    ) {
    }

    public function config(array $input): Response
    {
        return $this->handle('GET', $input, function (): Response {
            $data = [
                'site_key' => (string) ($_GET['site_key'] ?? ''),
                'form_key' => (string) ($_GET['form_key'] ?? ''),
                'mode' => (string) ($_GET['mode'] ?? ''),
            ];
            $errors = $this->formConfigValidate->check($data);
            if ($errors !== []) {
                return $this->corsJson($this->error(422, 'Validation failed', [], $errors), $data['site_key'], $data['form_key']);
            }

            $config = $this->formApiService->getConfig($data['site_key'], $data['form_key'], $data['mode']);

            return $this->corsJson($this->success($config), $data['site_key'], $data['form_key']);
        });
    }

    public function submit(array $input): Response
    {
        return $this->handle('POST', $input, function (array $payload): Response {
            $errors = $this->formSubmitValidate->check($payload);
            if ($errors !== []) {
                return $this->corsJson($this->error(422, 'Validation failed', [], $errors), (string) ($payload['site_key'] ?? ''), (string) ($payload['form_key'] ?? ''));
            }

            $result = $this->formApiService->submit($payload, $_SERVER);

            return $this->corsJson($this->success($result), (string) $payload['site_key'], (string) $payload['form_key']);
        });
    }

    public function options(array $input): Response
    {
        $siteKey = (string) ($input['site_key'] ?? ($_GET['site_key'] ?? ''));
        $formKey = (string) ($input['form_key'] ?? ($_GET['form_key'] ?? ''));

        return $this->corsJson(Response::json([
            'code' => 0,
            'message' => 'ok',
            'data' => (object) [],
        ]), $siteKey, $formKey);
    }

    private function handle(string $method, array $input, callable $callback): Response
    {
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET')) !== $method) {
            return $this->corsJson($this->error(405, 'Method not allowed'), (string) ($input['site_key'] ?? ''), (string) ($input['form_key'] ?? ''));
        }

        try {
            return $callback($input);
        } catch (RuntimeException $exception) {
            $code = $exception->getCode();
            $payload = [];
            $message = $exception->getMessage();
            if ($code === 4220) {
                $decoded = json_decode($message, true);
                $payload = is_array($decoded) ? $decoded : [];
                $message = 'Validation failed';
                $code = 422;
            } elseif ($code <= 0) {
                $code = 500;
                $message = 'Server error';
            }

            return $this->corsJson(
                $this->error($code, $message, [], $payload),
                (string) ($input['site_key'] ?? ($_GET['site_key'] ?? '')),
                (string) ($input['form_key'] ?? ($_GET['form_key'] ?? ''))
            );
        }
    }

    private function corsJson(Response $response, string $siteKey, string $formKey): Response
    {
        $headers = [
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, X-Requested-With',
            'Vary' => 'Origin',
        ];
        $origin = $this->formApiService->resolveAllowedOrigin($siteKey, $formKey, $_SERVER);
        if ($origin !== null) {
            $headers['Access-Control-Allow-Origin'] = $origin;
        }

        $reflection = new \ReflectionClass($response);
        $content = $reflection->getProperty('content');
        $content->setAccessible(true);
        $statusCode = $reflection->getProperty('statusCode');
        $statusCode->setAccessible(true);
        $contentType = $reflection->getProperty('contentType');
        $contentType->setAccessible(true);
        $existingHeaders = $reflection->getProperty('headers');
        $existingHeaders->setAccessible(true);

        return new Response(
            $content->getValue($response),
            $statusCode->getValue($response),
            $contentType->getValue($response),
            array_merge($existingHeaders->getValue($response), $headers)
        );
    }
}
