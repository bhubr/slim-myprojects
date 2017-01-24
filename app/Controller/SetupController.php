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
     * Set up front page
     */
    public function getSetup(Request $request, Response $response, $args)
    {
        if(! realpath(__DIR__ . '/../bootstrap/db_settings.php') ) {
            return $response->withRedirect('/db-setup');
        }
        else {
            return $response->withRedirect('/app-setup');
        }
        return $response;
    }

    /**
     * Set up page
     */
    public function getDbSetup(Request $request, Response $response, $args)
    {
        // CSRF token name and value
        $name = $request->getAttribute('csrf_name');
        $value = $request->getAttribute('csrf_value');

        $this->view->render($response, 'db_setup.html.twig', [
            'csrfName' => $name,
            'csrfValue' => $value
        ]);
        return $response;
    }

    /**
     * Process set up form
     */
    public function postDbSetup(Request $request, Response $response, $args)
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
            switch($e->getCode()) {
                case 1045:
                    die('Access denied... check grants for this user and db');
                    break;
                case 1049:
                    // http://stackoverflow.com/questions/2583707/can-i-create-a-database-using-pdo-in-php
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

                        $schema = file_get_contents(realpath(__DIR__ . '/../../vendor/cartalyst/sentinel/schema/mysql-5.6+.sql'));
                        $dbh->exec("use $dbname; $schema"); // or die(print_r($dbh->errorInfo(), true));

                        return $response->withRedirect('/');

                    } catch ( \PDOException $e ) {
                        die("DB ERROR: ". $e->getMessage());
                    }
                    break;
                default:
                    die();
            }
        }
    }


    /**
     * Set up page
     */
    public function getAppSetup(Request $request, Response $response, $args)
    {
        // CSRF token name and value
        $name = $request->getAttribute('csrf_name');
        $value = $request->getAttribute('csrf_value');

        $this->view->render($response, 'app_setup.html.twig', [
            'csrfName' => $name,
            'csrfValue' => $value
        ]);
        return $response;
    }


    /**
     * Process set up form
     */
    public function postAppSetup(Request $request, Response $response, $args)
    {
        $this->sentinel->getRoleRepository()->createModel()->create(array(
            'name'          => 'Admin',
            'slug'          => 'admin',
            'permissions'   => array(
                'user.create' => true,
                'user.update' => true,
                'user.delete' => true
            ),
        ));

        $this->sentinel->getRoleRepository()->createModel()->create(array(
            'name'          => 'User',
            'slug'          => 'user',
            'permissions'   => array(
                'user.update' => true
            ),
        ));


        // First get attrs from payload and validate them
        $data = $request->getParsedBody();

        // Now create admin user
        $user = $this->sentinel->create([
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'email' => $data['email'],
            'password' => $data['password'],
            'permissions' => [
                'user.delete' => true,
            ],
        ]);

        $role = $this->sentinel->findRoleByName('Admin');

        // attach the user to the admin role
        $role->users()->attach($user);

        // create a new activation for the registered user
        $activation = (new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository)->create($user);

        $activationRepository = new \Cartalyst\Sentinel\Activations\IlluminateActivationRepository;
        $activationRepository->complete($user, $activation->code);

        unset($_SESSION['_doing_setup']);
        return $response->withRedirect('/');
    }

}