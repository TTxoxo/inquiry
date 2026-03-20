<?php

declare(strict_types=1);

use app\api\controller\FormController;

return [
    ['GET', '/api/form/config', FormController::class, 'config', []],
    ['POST', '/api/form/submit', FormController::class, 'submit', []],
    ['OPTIONS', '/api/form/config', FormController::class, 'options', []],
    ['OPTIONS', '/api/form/submit', FormController::class, 'options', []],
];
