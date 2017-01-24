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
use Cartalyst\Sentinel\Users\EloquentUser;

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
class SetupController
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
     * Sentinel instance
     */
    private $sentinel;

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
    }

    /**
     * Set up page
     */
    public function getSetup(Request $request, Response $response, $args)
    {
        // CSRF token name and value
        $name = $request->getAttribute('csrf_name');
        $value = $request->getAttribute('csrf_value');

        $this->view->render($response, 'setup.html.twig', [
            'csrfName' => $name,
            'csrfValue' => $value
        ]);
        return $response;
    }
    /**
     * Process set up form
     */
    public function postSetup(Request $request, Response $response, $args)
    {
        // First get attrs from payload and validate them
        $data = $request->getParsedBody();
        $dbname = $data['db']['name'];
        $dbuser = $data['db']['user'];
        $dbpass = $data['db']['pass'];

        // Try connecting to db with given params
        $dsn = "mysql:host=localhost;dbname=$dbname";
        try {
            $pdo = new \PDO($dsn, $data['db']['user'], $data['db']['pass']);    
        }
        catch( \Exception $e ) {
            // die($e->getCode() . ' ' . $e->getMessage());
            switch($e->getCode()) {
                case 1045:
                    die('Access denied... check grants for this user and db');
                    break;
                case 1049:
                    // die('DB does not exist...');
                    $rootpass = $data['mysqlrootpass'];
                    try {
                        $dbh = new \PDO("mysql:host=localhost", 'root', $rootpass);

                        $dbh->exec("CREATE DATABASE `$dbname`;
                                CREATE USER IF NOT EXISTS '$dbuser'@'localhost' IDENTIFIED BY '$dbpass';
                                GRANT ALL ON `$dbname`.* TO '$dbuser'@'localhost';
                                FLUSH PRIVILEGES;") 
                        or die(print_r($dbh->errorInfo(), true));

                        // Replace data in template
                        $template = file_get_contents(realpath(__DIR__ . '/../bootstrap/db_settings.sample.php'));
                        $output = str_replace(['dbname', 'dbuser', 'dbpass'], $data['db'], $template);
                        file_put_contents(realpath(__DIR__ . '/../bootstrap') . '/db_settings.php', $output);


                        // // Now create admin user
                        // $user = $this->sentinel->create([
                        //     'first_name' => 'Super',
                        //     'last_name' => 'Admin',
                        //     'email' => $data['app']['email'],
                        //     'password' => $data['app']['password'],
                        //     'permissions' => [
                        //         'user.delete' => true,
                        //     ],
                        // ]);

                        // // attach the user to the admin role
                        // $role->users()->attach($user);

                        // // create a new activation for the registered user
                        // $activation = (new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

                        // // $activationRepository = new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
                        // // $activation = \Cartalyst\Sentinel\Activations\EloquentActivation::where("code", $code)->first();

                        // $activationRepository->complete($user, $activation->code);


                        return $response->withRedirect('/');

                    } catch ( \PDOException $e ) {
                        die("DB ERROR: ". $e->getMessage());
                    }
                    break;
                default:
                    die('pouet');
            }
        }


    }


}