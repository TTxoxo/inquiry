<?php
declare(strict_types=1);

use think\App;

require __DIR__ . '/../vendor/autoload.php';

$app = new App();
$response = $app->http->run();
$response->send();
$app->http->end($response);
