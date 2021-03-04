<?php
/**
 *
 */

namespace Dietcube;

use Dietcube\Events\DietcubeEvents;
use Dietcube\Events\BootEvent;
use Dietcube\Events\RoutingEvent;
use Dietcube\Events\ExecuteActionEvent;
use Dietcube\Events\FilterResponseEvent;
use Dietcube\Events\FinishRequestEvent;
use Dietcube\Exception\DCException;
use Dietcube\Exception\HttpNotFoundException;
use Dietcube\Exception\HttpMethodNotAllowedException;
use Dietcube\Twig\DietcubeExtension;
use Pimple\Container;
use FastRoute\Dispatcher as RouteDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\Event;
use Monolog\Logger;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\FilesystemLoader;

class Dispatcher
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var EventDispatcher
     */
    protected $event_dispatcher;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function boot(): void
    {
        $this->app->loadConfig();

        $container = $this->container = new Container();

        $this->container['event_dispatcher'] = $this->event_dispatcher = new EventDispatcher();

        $this->container['app'] = $this->app;
        $this->app->setContainer($container);
        $config = $this->container['app.config'] = $this->app->getConfig();

        $this->container['logger'] = $logger = $this->app->createLogger(
            $config->get('logger.path'),
            $config->get('logger.level', Logger::WARNING)
        );

        $logger->debug('Application booted. env={env}', ['env' => $this->app->getEnv()]);
        $logger->debug('Config file loaded. config_files={files}', ['files' => implode(',', $this->app->getConfigFiles())]);

        $this->bootGlobals();

        $this->app->initHttpRequest($this->container);
        $this->app->init($this->container);

        if (!isset($this->container['router'])) {
            $this->container['router'] = new Router($this->container);
            $this->container['router']->addRoute($this->app->getRoute());
        }

        if (!isset($this->container['app.renderer'])) {
            $this->container['app.renderer'] = function () {
                return $this->createRenderer();
            };
        }

        $this->app->config($this->container);

        $this->event_dispatcher->dispatch(DietcubeEvents::BOOT, new BootEvent($this->app));
    }

    protected function createRenderer(): Environment
    {
        $config = $this->container['app.config'];
        $loader = new FilesystemLoader($this->app->getTemplateDir());
        $twig = new Environment($loader, [
            'debug' => $config->get('debug', false),
            'cache' => $config->get('twig.cache', false),
            'charset' => $config->get('twig.charset', 'utf-8'),
        ]);

        // add built-in template path
        $loader->addPath(__DIR__ . '/template/error');

        // add built-in extension
        $twig->addExtension((new DietcubeExtension())->setContainer($this->container));

        if ($this->app->isDebug()) {
            // add built-in debug template path
            $twig->addExtension(new DebugExtension());
            $loader->addPath(__DIR__ . '/template/debug', 'debug');
        }

        $twig->addGlobal('query', $this->container['global.get']->getData());
        $twig->addGlobal('body', $this->container['global.post']->getData());

        return $twig;
    }

    protected function bootGlobals(): void
    {
        $this->container['global.server'] = new Parameters($_SERVER);
        $this->container['global.get']    = new Parameters($_GET);
        $this->container['global.post']   = new Parameters($_POST);
        $this->container['global.files']  = new Parameters($_FILES);
        $this->container['global.cookie'] = new Parameters($_COOKIE);
    }

    protected function prepareResponse(): Response
    {
        $response = new Response();
        $response->setLogger($this->container['logger']);
        $this->container['response'] = $response;

        return $response;
    }

    public function handleRequest(): Response
    {
        $container = $this->container;

        // prepare handle request
        $response = $this->prepareResponse();

        $method = $container['global.server']->get('REQUEST_METHOD');
        $path = $container['app']->getPath();
        $this->event_dispatcher->addListener(DietcubeEvents::ROUTING, function (Event $event) use ($method, $path) {
            [$handler, $vars] = $this->dispatchRouter($method, $path);

            $event->setRouteInfo($handler, $vars);
        });

        $event = new RoutingEvent($this->app, $container['router']);
        $this->event_dispatcher->dispatch(DietcubeEvents::ROUTING, $event);

        [$handler, $vars] = $event->getRouteInfo();

        $action_result = $this->executeAction($handler, $vars);
        $response = $response->setBody($action_result);

        return $this->filterResponse($response);
    }

    public function handleError(\Exception $errors): Response
    {
        $logger = $this->container['logger'];
        if (!isset($this->container['response'])) {
            $response = $this->prepareResponse();
        } else {
            $response = $this->container['response'];
        }

        $logger->error('Error occurred. ', [
            'error'     => get_class($errors),
            'message'   => $errors->getMessage(),
            'trace'     => $errors->getTraceAsString(),
        ]);
        if ($this->app->isDebug()) {
            $debug_controller = $this->container['app.debug_controller'] ?? (Controller\DebugController::class);
            $controller = $this->app->createController($debug_controller);

            // FIXME: debug controller method name?
            $action_result = $this->executeAction([$controller, 'dumpErrors'], ['errors' => $errors], $fire_events = false);
        } else {
            [$controller_name, $action_name] = $this->detectErrorAction($errors);
            $controller = $this->app->createController($controller_name);

            $action_result = $this->executeAction([$controller, $action_name], ['errors' => $errors], $fire_events = false);
        }

        $response->setBody($action_result);

        return $this->filterResponse($response);
    }

    /**
     * @param mixed $handler
     * @param array $vars
     * @param bool $fire_events
     * @return bool
     */
    public function executeAction($handler, $vars = [], $fire_events = true): bool
    {
        $logger = $this->container['logger'];
        $executable = null;

        if (is_callable($handler)) {
            $executable = $handler;
        } else {
            list($controller_name, $action_name) = $this->app->getControllerByHandler($handler);

            if (!class_exists($controller_name)) {
                throw new DCException("Controller {$controller_name} is not exists.");
            }
            $controller = $this->app->createController($controller_name);
            $executable = [$controller, $action_name];
        }

        if ($fire_events) {
            $event = new ExecuteActionEvent($this->app, $executable, $vars);
            $this->event_dispatcher->dispatch(DietcubeEvents::EXECUTE_ACTION, $event);

            $executable = $event->getExecutable();
            $vars = $event->getVars();
        }

        // Executable may changed by custom event so parse info again.
        if ($executable instanceof \Closure) {
            $controller_name = 'function()';
            $action_name = '-';
        } else {
            $controller_name = get_class($executable[0]);
            $action_name = $executable[1];

            if (!is_callable($executable)) {
                // anon function is always callable so when the handler is anon function it never come here.
                $logger->error('Action not dispatchable.', ['controller' => $controller_name, 'action_name' => $action_name]);
                throw new DCException("'{$controller_name}::{$action_name}' is not a valid action.");
            }
        }

        $logger->debug('Execute action.', ['controller' => $controller_name, 'action' => $action_name, 'vars' => $vars]);
        return call_user_func_array($executable, $vars);
    }

    protected function getErrorController(): string
    {
        return $this->container['app.error_controller'] ?? (Controller\ErrorController::class);
    }

    /**
     * Dispatch router with HTTP request information.
     *
     * @param mixed $method
     * @param mixed $path
     * @return array
     */
    protected function dispatchRouter($method, $path): array
    {
        $router = $this->container['router'];
        $logger = $this->container['logger'];

        $logger->debug('Router dispatch.', ['method' => $method, 'path' => $path]);

        $router->init();
        $route_info = $router->dispatch($method, $path);

        $handler = null;
        $vars = [];

        switch ($route_info[0]) {
        case RouteDispatcher::NOT_FOUND:
            $logger->debug('Routing failed. Not Found.');
            throw new HttpNotFoundException('404 Not Found');

        case RouteDispatcher::METHOD_NOT_ALLOWED:
            $logger->debug('Routing failed. Method Not Allowd.');
            throw new HttpMethodNotAllowedException('405 Method Not Allowed');

        case RouteDispatcher::FOUND:
            $handler = $route_info[1];
            $vars = $route_info[2];
            $logger->debug('Route found.', ['handler' => $handler]);
            break;
        }

        return [$handler, $vars];
    }

    protected function detectErrorAction(\Exception $errors): array
    {
        $error_controller = $this->getErrorController();
        if ($errors instanceof HttpNotFoundException) {
            return [$error_controller, Controller::ACTION_NOT_FOUND];
        } elseif ($errors instanceof HttpMethodNotAllowedException) {
            return [$error_controller, Controller::ACTION_METHOD_NOT_ALLOWED];
        }

        // Do internalError action for any errors.
        return [$error_controller, Controller::ACTION_INTERNAL_ERROR];
    }

    /**
     * Dispatch FILTER_RESPONSE event to filter response.
     *
     * @param Response $response
     * @return Response
     */
    protected function filterResponse(Response $response): Response
    {
        $event = new FilterResponseEvent($this->app, $response);
        $this->event_dispatcher->dispatch(DietcubeEvents::FILTER_RESPONSE, $event);

        return $this->finishRequest($event->getResponse());
    }

    /**
     * Finish request and send response.
     *
     * @param Response $response
     * @return Response
     */
    protected function finishRequest(Response $response): Response
    {
        $event = new FinishRequestEvent($this->app, $response);
        $this->event_dispatcher->dispatch(DietcubeEvents::FINISH_REQUEST, $event);

        $response = $event->getResponse();

        $response->sendHeaders();
        $response->sendBody();

        return $response;
    }

    /**
     * @param string $env
     * @return array|false|mixed|string
     */
    public static function getEnv(string $env = 'production')
    {
        if (isset($_SERVER['DIET_ENV'])) {
            return $_SERVER['DIET_ENV'];
        }
        if (getenv('DIET_ENV')) {
            return getenv('DIET_ENV');
        }
        return $env;
    }

    /**
     * @param mixed $app_class
     * @param mixed $app_root_dir
     * @param mixed $env
     */
    public static function invoke($app_class, $app_root_dir, $env): void
    {
        $app = new $app_class($app_root_dir, $env);
        $dispatcher = new static($app);
        $dispatcher->boot();

        try {
            $response = $dispatcher->handleRequest();
        } catch (\Exception $e) {
            // Please handle errors occurred on executing Dispatcher::handleError with your web server.
            // Dietcube doesn't care these errors.
            $response = $dispatcher->handleError($e);
        }
    }
}
