<?php
require 'db_settings.php';

$container = $app->getContainer();

// Register Flash messages
$container['flash'] = function () {
    $session =new \RKA\Session();
    return new \Slim\Flash\Messages();
};

// Register Twig
$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig( __DIR__.'/../views', [
        'cache' => false, //__DIR__.'/../cache',
    ]);

    // Instantiate and add Slim specific extension
    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    return $view;
};

// Register Eloquent configuration
// See Slim3 cookbook: https://www.slimframework.com/docs/cookbook/database-eloquent.html
$container['db'] = function ($container) {
    $capsule = new \Illuminate\Database\Capsule\Manager;
    $capsule->addConnection([
        'driver' => 'mysql',
        'host' => DB_HOST,
        'database' => DB_NAME,
        'username' => DB_USER,
        'password' => DB_PASS,
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ]);
    $capsule->setAsGlobal();
    $capsule->bootEloquent();
    return $capsule;
};

$container['sentinel'] = function($container) {
    return (new \Cartalyst\Sentinel\Native\Facades\Sentinel())->getSentinel();
};

// Register CSRF. https://github.com/slimphp/Slim-Csrf
$container['csrf'] = function ($c) {
    return new \Slim\Csrf\Guard;
};

// Register middleware for all routes
// If you are implementing per-route checks you must not add this
$app->add($container->get('csrf'));

// $container['sessionMiddleware'] = function ($c) {
//     return new \RKA\SessionMiddleware(['name' => 'MyProjectsSession']);
// };
// $app->add($container->get('sessionMiddleware'));

// Adding logger to Slim container
$container['logger'] = function($c) {
    return new Monolog\Logger('logger');
};

// Adding Event Emitter to Slim container
$container['emitter'] = function($c) {
    return new \Sabre\Event\EventEmitter;
};

// Middleware that forces Eloquent to be loaded
$app->add( function($request, $response, $next) use($app) {
    $db = $app->getContainer()->get('db');
    return $next($request, $response);
});
