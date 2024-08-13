<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create();

$handler = new \App\ConvertAudioFileHandler(
    __DIR__ . '/../resources/upload/audio',
    __DIR__ . '/data/audio',
);
$app->post('/convert/{to_format}', $handler);

$app->run();