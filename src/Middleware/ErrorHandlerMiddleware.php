<?php
namespace App\Middleware;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\InstanceConfigTrait;
use Cake\Error\ExceptionRenderer;
use Cake\Log\Log;
use Cake\Network\Exception\ForbiddenException;
use Exception;

/**
 * Error handling middleware.
 *
 * Traps exceptions and converts them into HTML or content-type appropriate
 * error pages using the CakePHP ExceptionRenderer.
 *
 * Almost exact copy of Cakes ErrorHandlerMiddleware
 * main purpose of this class is to separate 404 errors to a different scope
 *
 * The following criteria is subject to change:
 * Only MissingControllerException should be separated out of the main error log,
 * ie MissingActionException is something we want to monitor for.
 *
 */
class ErrorHandlerMiddleware
{
    use InstanceConfigTrait;

    /**
     * Default configuration values.
     *
     * - `log` Enable logging of exceptions.
     * - `skipLog` List of exceptions to skip logging. Exceptions that
     *   extend one of the listed exceptions will also not be logged. Example:
     *
     *   ```
     *   'skipLog' => ['Cake\Error\NotFoundException', 'Cake\Error\UnauthorizedException']
     *   ```
     *
     * - `trace` Should error logs include stack traces?
     *
     * @var array
     */
    protected $_defaultConfig = [
        'skipLog' => [],
        'log' => true,
        'trace' => false,
    ];

    /**
     * Exception render.
     *
     * @var \Cake\Error\ExceptionRendererInterface|string|null
     */
    protected $exceptionRenderer;

    /**
     * Constructor
     *
     * @param string|callable|null $exceptionRenderer The renderer or class name
     *   to use or a callable factory. If null, Configure::read('Error.exceptionRenderer')
     *   will be used.
     * @param array $config Configuration options to use. If empty, `Configure::read('Error')`
     *   will be used.
     */
    public function __construct($exceptionRenderer = null, array $config = [])
    {
        if ($exceptionRenderer) {
            $this->exceptionRenderer = $exceptionRenderer;
        }

        $config = $config ?: Configure::read('Error');
        $this->setConfig($config);
    }

    /**
     * Wrap the remaining middleware with error handling.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @param callable $next Callback to invoke the next middleware.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function __invoke($request, $response, $next)
    {
        try {
            return $next($request, $response);
        } catch (\Exception $e) {
            return $this->handleException($e, $request, $response);
        }
    }

    /**
     * Handle an exception and generate an error response
     *
     * @param \Exception $exception The exception to handle.
     * @param \Psr\Http\Message\ServerRequestInterface $request The request.
     * @param \Psr\Http\Message\ResponseInterface $response The response.
     * @return \Psr\Http\Message\ResponseInterface A response
     */
    public function handleException($exception, $request, $response)
    {
        // NOTE for resellers that are logging in too early
        if ($exception instanceof ForbiddenException && defined('RESELLER_TOO_EARLY')) {
            return $response->withHeader('Location', '/users/too_early');
        }

        $renderer = $this->getRenderer($exception);
        try {
            $res = $renderer->render();
            $this->logException($request, $exception);

            return $res;
        } catch (\Exception $e) {
            $this->logException($request, $e);

            $body = $response->getBody();
            $body->write('An Internal Server Error Occurred');
            $response = $response->withStatus(500)
                ->withBody($body);
        }

        return $response;
    }

    /**
     * Get a renderer instance
     *
     * @param \Exception $exception The exception being rendered.
     * @return \Cake\Error\ExceptionRendererInterface The exception renderer.
     * @throws \Exception When the renderer class cannot be found.
     */
    protected function getRenderer($exception)
    {
        if (!$this->exceptionRenderer) {
            $this->exceptionRenderer = $this->getConfig('exceptionRenderer') ?: ExceptionRenderer::class;
        }

        if (is_string($this->exceptionRenderer)) {
            $class = App::className($this->exceptionRenderer, 'Error');
            if (!$class) {
                throw new Exception(sprintf(
                    "The '%s' renderer class could not be found.",
                    $this->exceptionRenderer
                ));
            }

            return new $class($exception);
        }
        $factory = $this->exceptionRenderer;

        return $factory($exception);
    }

    /**
     * Log an error for the exception if applicable.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Exception $exception The exception to log a message for.
     * @return void
     */
    protected function logException($request, $exception)
    {
        if (!$this->getConfig('log')) {
            return;
        }

        $skipLog = $this->getConfig('skipLog');
        if ($skipLog) {
            foreach ((array)$skipLog as $class) {
                if ($exception instanceof $class) {
                    return;
                }
            }
        }

        if ($this->isGeneral404Error($request, $exception)) {
            return Log::notice($this->getMessage($request, $exception), 'error404');
        }

        Log::error($this->getMessage($request, $exception));
    }

    private function isGeneral404Error($request, $exception)
    {
        // spaceific rules for which exceptions go to which logs in our app

        // general 404s or bots trying URLs
        $general404 = $exception instanceof \Cake\Routing\Exception\MissingControllerException || $exception instanceof \Cake\Controller\Exception\MissingActionException;

        // we throw these from the AclComponent if a request is not permitted, people snooping around
        $forbiddenRequest = $exception instanceof \Cake\Network\Exception\ForbiddenException;

        // we get these generally from bots looking for strange URLs
        $missingRoute = $exception instanceof \Cake\Routing\Exception\MissingRouteException;

        // if a record cannot be found we log it as a notice
        $recordNotFound = $exception instanceof \Cake\Datasource\Exception\RecordNotFoundException;

        if ($general404 || $forbiddenRequest || $missingRoute || $recordNotFound) {
            return true;
        }

        return false;
    }

    /**
     * Generate the error log message.
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request The current request.
     * @param \Exception $exception The exception to log a message for.
     * @return string Error message
     */
    protected function getMessage($request, $exception)
    {
        $message = sprintf(
            '[%s] %s',
            get_class($exception),
            $exception->getMessage()
        );
        $debug = Configure::read('debug');

        if ($debug && method_exists($exception, 'getAttributes')) {
            $attributes = $exception->getAttributes();
            if ($attributes) {
                $message .= "\nException Attributes: " . json_encode($exception->getAttributes());
            }
        }
        $message .= "\nRequest URL: " . $request->getRequestTarget();
        $referer = $request->getHeaderLine('Referer');
        if ($referer) {
            $message .= "\nReferer URL: " . $referer;
        }
        if ($this->getConfig('trace')) {
            $message .= "\nStack Trace:\n" . $exception->getTraceAsString() . "\n\n";
        }

        return $message;
    }
}
