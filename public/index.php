<?php
ini_set("display_errors", 1);
error_reporting(E_ALL);

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

// Register routes
// include_once __DIR__.'/../app/routes_auth.php';
// include_once __DIR__.'/../app/routes_setup.php';
// include_once __DIR__.'/../app/routes_admin.php';
// require_once __DIR__.'/../app/routes_utils.php';
// require_once __DIR__.'/../app/routes_files.php';

// Register middlewares
// $app->add(new \Slim\Csrf\Guard);

$app->get('/', function ($request, $response) {
    return $response->write('Yo');
});

$app->run();
