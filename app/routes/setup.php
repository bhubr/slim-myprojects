<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;


// One-time use route to create roles
// $app->get('/setup', function (Request $request, Response $response, $args) use ($app) {
// });

$app->get('/', [$container[bhubr\MyProjects\Controller\SetupController::class], 'getSetup']);

$app->get('/db-setup', [$container[bhubr\MyProjects\Controller\SetupController::class], 'getDbSetup']);
$app->post('/db-setup', [$container[bhubr\MyProjects\Controller\SetupController::class], 'postDbSetup']);
$app->get('/app-setup', [$container[bhubr\MyProjects\Controller\SetupController::class], 'getAppSetup']);
$app->post('/app-setup', [$container[bhubr\MyProjects\Controller\SetupController::class], 'postAppSetup']);
