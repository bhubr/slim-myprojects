<?php
namespace bhubr\MyProjects\Controller;

use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
// use Illuminate\Database\Query\Builder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
// use Respect\Validation\Validator as v;
use Sabre\Event\EventEmitter;
use Slim\Flash\Messages;
use bhubr\MyProjects\Service\AuthService;
use Cartalyst\Sentinel\Sentinel;

/**
 * Controller for handling user-related stuff:
 *   - registration
 *   - authentication
 *   - password loss/reset
 *
 * Todo:
 *   - add middleware for session checking
 *   - add flash messages
 *      - registration ok => confirm email
 *      - registration errors
 *   - password reset
 *   - email confirmation
 *   - also handle AJAX signup/signin/signout
 */
class AuthController
{
    /**
     * Twig view instance
     */
    private $view;

    /**
     * Logger instance
     */
    private $logger;

    /**
     * Csrf Guard instance
     */
    private $csrf;

    /**
     * Sabre library's EventEmitter instance
     */
    private $emitter;

    /**
     * Service instance
     */
    private $service;

    /**
     * Constructor
     */
    public function __construct(
        Guard $csrf,
        Twig $view,
        LoggerInterface $logger,
        EventEmitter $emitter,
        Messages $flash,
        Sentinel $sentinel
    ) {
        $this->csrf = $csrf;
        $this->view = $view;
        $this->logger = $logger;
        $this->emitter = $emitter;
        $this->flash = $flash;
        $this->sentinel = $sentinel;
        $this->service = new AuthService($emitter);
    }

    /**
     * Return template path to add it to Twig View's paths
     */
    public static function getTemplatePath() {
        return realpath(__DIR__ . '/../templates');
    }

    /**
     * Sign up page
     */
    public function getSignup(Request $request, Response $response, $args)
    {
        // CSRF token name and value
        $name = $request->getAttribute('csrf_name');
        $value = $request->getAttribute('csrf_value');

        $this->view->render($response, 'home.html.twig', [
            'csrfName' => $name,
            'csrfValue' => $value
        ]);
        return $response;
    }
    /**
     * Process sign up form
     */
    public function postSignup(Request $request, Response $response, $args)
    {
        // First get attrs from payload and validate them
        $attributes = $request->getParsedBody();
        $errors = $this->service->validateUser( $attributes );

        // On error redirect and display errors
        if( !empty( $errors ) ) {
            foreach( $errors as $error ) {
                $this->flash->addMessage( 'error', $error );
            }
            $uri = $request->getUri()->withPath('/');
            return $response->withRedirect($uri, 400);
        }


        $role = $this->sentinel->findRoleByName('Admin');

        if ($this->sentinel->findByCredentials([
            'login' => $attributes['email'],
        ])) {
            echo 'User already exists with this email.';

            return;
        }

        $user = $this->sentinel->create([
            'first_name' => $attributes['firstname'],
            'last_name' => $attributes['lastname'],
            'email' => $attributes['email'],
            'password' => $attributes['password'],
            'permissions' => [
                'user.delete' => false,
            ],
        ]);

        // attach the user to the admin role
        $role->users()->attach($user);

        // create a new activation for the registered user
        $activation = (new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

        //mail($attributes['email'], "Activate your account", "Click on the link below \n <a href='http://vaprobash.dev/user/activate?code={$activation->code}&login={$user->id}'>Activate your account</a>");
        $baseUrl = $request->getUri()->getBaseUrl();
        $message = "Welcome, {$user->first_name}! Please check your email to complete your account registration. (or just use this <a href='{$baseUrl}/user/activate?code={$activation->code}&login={$user->id}'>link</a>)";



        // Otherwise emit signal, sign user in, set success message and redirect
        $this->emitter->emit('user:signin', [$user]);
        $_SESSION['user'] = $user;

        $this->flash->addMessage('success', $message);
        $uri = $request->getUri()->withPath('/');
        return $response = $response->withRedirect($uri);
    }

    public function activate(Request $request, Response $response, $args) {
        $code = $request->getParam('code');

        $activationRepository = new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
        $activation = \Cartalyst\Sentinel\Activations\EloquentActivation::where("code", $code)->first();

        if (!$activation)
        {
            echo "Activation error!";
            
            return;
        }

        $user = $this->sentinel->findById($activation->user_id);

        if (!$user)
        {
            echo "User not found!";
            
            return;
        }


        if (!$activationRepository->complete($user, $code))
        {
            if ($activationRepository->completed($user))
            {
                echo 'User is already activated. Try to log in.';

                return;
            }

            echo "Activation error!";
            
            return;
        }

        echo 'Your account has been activated. Log in to your account.';

        return;
    }

}