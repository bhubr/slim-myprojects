<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

$_SESSION['_doing_setup'] = true;

// One-time use route to create roles
// $app->get('/setup', function (Request $request, Response $response, $args) use ($app) {
//     $app->getContainer()->sentinel->getRoleRepository()->createModel()->create(array(
//         'name'          => 'Admin',
//         'slug'          => 'admin',
//         'permissions'   => array(
//             'user.create' => true,
//             'user.update' => true,
//             'user.delete' => true
//         ),
//     ));

//     $app->getContainer()->sentinel->getRoleRepository()->createModel()->create(array(
//         'name'          => 'User',
//         'slug'          => 'user',
//         'permissions'   => array(
//             'user.update' => true
//         ),
//     ));
// });

$app->get('/', [$container[bhubr\MyProjects\Controller\SetupController::class], 'getSetup']);
$app->post('/', [$container[bhubr\MyProjects\Controller\SetupController::class], 'postSetup']);
