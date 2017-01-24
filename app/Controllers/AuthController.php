<?php
namespace bhubr\MyProjects\Controllers;

use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Psr\Log\LoggerInterface;
// use Illuminate\Database\Query\Builder;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
// use Respect\Validation\Validator as v;
use Sabre\Event\EventEmitter;
use Slim\Flash\Messages;

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
        Messages $flash
    ) {
        $this->csrf = $csrf;
        $this->view = $view;
        $this->logger = $logger;
        $this->emitter = $emitter;
        $this->flash = $flash;
        // $this->service = new Service($emitter);
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
}