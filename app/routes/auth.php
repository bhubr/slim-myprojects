<?php
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

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

$app->get('/', [$container[bhubr\MyProjects\Controllers\AuthController::class], 'getSignup']);

$app->post('/', function (Request $request, Response $response, $args) use ($app) {
    // we leave validation for another time
    $data = $request->getParsedBody();

    $role = $app->getContainer()->sentinel->findRoleByName('Admin');

    if ($app->getContainer()->sentinel->findByCredentials([
        'login' => $data['email'],
    ])) {
        echo 'User already exists with this email.';

        return;
    }

    $user = $app->getContainer()->sentinel->create([
        'first_name' => $data['firstname'],
        'last_name' => $data['lastname'],
        'email' => $data['email'],
        'password' => $data['password'],
        'permissions' => [
            'user.delete' => false,
        ],
    ]);

    // attach the user to the admin role
    $role->users()->attach($user);

    // create a new activation for the registered user
    $activation = (new Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

    //mail($data['email'], "Activate your account", "Click on the link below \n <a href='http://vaprobash.dev/user/activate?code={$activation->code}&login={$user->id}'>Activate your account</a>");
    $baseUrl = $request->getUri()->getBaseUrl();
    echo "Please check your email to complete your account registration. (or just use this <a href='{$baseUrl}/user/activate?code={$activation->code}&login={$user->id}'>link</a>)";
});