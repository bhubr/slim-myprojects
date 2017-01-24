<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__.'/../vendor/autoload.php';

$app = new \Slim\App([
    'settings' => [
        'debug'         => true,
        'whoops.editor' => 'sublime' // Support click to open editor
    ]
]);

// Put this 1st: https://github.com/zeuxisoo/php-slim-whoops/issues/12
$app->add(new \Zeuxisoo\Whoops\Provider\Slim\WhoopsMiddleware);

// Register bindings
include_once __DIR__.'/../app/bootstrap/container.php';

$container = $app->getContainer();

$container[bhubr\MyProjects\Controllers\AuthController::class] = function ($c) {
    $csrf = $c->get('csrf');
    $view = $c->get('view');
    $logger = $c->get('logger');
    $emitter = $c->get('emitter');
    $flash = $c->get('flash');
    return new bhubr\MyProjects\Controllers\AuthController($csrf, $view, $logger, $emitter, $flash);
};

require '../app/routes/auth.php';

$app->run();
