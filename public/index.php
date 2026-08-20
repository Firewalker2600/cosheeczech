<?php

declare(strict_types=1);

use App\Http\ApplicationFactory;

require dirname(__DIR__) . '/vendor/autoload.php';

$container = require dirname(__DIR__) . '/config/container.php';

$app = ApplicationFactory::create($container);
$app->run();
