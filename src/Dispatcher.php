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
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

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

    public function boot()
    {
        $this->app->loadConfig();

        $container = $this->container = new Container();

        $this->container['event_dispatcher'] = $this->event_dispatcher = new EventDispatcher();

        $this->container['app'] = $this->app;
        $this->app->setContainer($container);
        $config = $this->container['app.config'] = $this->app->getConfig();

        $this->container['logger'] = $logger = $this->createLogger(
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
            $this->container['app.renderer'] = $this->createRenderer();
        }

        $this->app->config($this->container);

        $this->event_dispatcher->dispatch(DietcubeEvents::BOOT, new BootEvent($this->app));
    }

    protected function createLogger($path, $level = Logger::WARNING)
    {
        $logger = new Logger('app');
        $logger->pushProcessor(new PsrLogMessageProcessor);

        if (is_writable($path) || is_writable(dirname($path))) {
            $logger->pushHandler(new StreamHandler($path, $level));
        } else {
            if ($this->app->isDebug()) {
                throw new DCException("Log path '{$path}' is not writable. Make sure your logger.path of config.");
            }
            $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level));
            $logger->warning("Log path '{$path}' is not writable. Make sure your logger.path of config.");
            $logger->warning("error_log() is used for application logger instead at this time.");
        }

        return $logger;
    }

    protected function createRenderer()
    {
        $config = $this->container['app.config'];
        $loader = new \Twig_Loader_Filesystem($this->app->getTemplateDir());
        $twig = new \Twig_Environment($loader, [
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
            $twig->addExtension(new \Twig_Extension_Debug());
            $loader->addPath(__DIR__ . '/template/debug', 'debug');
        }

        $twig->addGlobal('query', $this->container['global.get']->getData());
        $twig->addGlobal('body', $this->container['global.post']->getData());

        return $twig;
    }

    protected function bootGlobals()
    {
        $this->container['global.server'] = new Parameters($_SERVER);
        $this->container['global.get']    = new Parameters($_GET);
        $this->container['global.post']   = new Parameters($_POST);
        $this->container['global.files']  = new Parameters($_FILES);
        $this->container['global.cookie'] = new Parameters($_COOKIE);
    }

    /**
     * @return Response
     */
    protected function prepareResponse()
    {
        $response = new Response();
        $response->setLogger($this->container['logger']);
        $this->container['response'] = $response;

        return $response;
    }

    /**
     * @return Response
     */
    public function handleRequest()
    {
        $container = $this->container;

        // prepare handle request
        $response = $this->prepareResponse();

        $method = $container['global.server']->get('REQUEST_METHOD');
        $path = $container['app']->getPath();
        $this->event_dispatcher->addListener(DietcubeEvents::ROUTING, function (Event $event) use ($method, $path) {
            list($handler, $vars) = $this->dispatchRouter($method, $path);

            $event->setRouteInfo($handler, $vars);
        });

        $event = new RoutingEvent($this->app, $container['router']);
        $this->event_dispatcher->dispatch(DietcubeEvents::ROUTING, $event);

        list($handler, $vars) = $event->getRouteInfo();

        $action_result = $this->executeAction($handler, $vars);
        $response = $response->setBody($action_result);

        return $this->filterResponse($response);
    }

    /**
     * @param \Exception $errors
     * @return Response
     */
    public function handleError(\Exception $errors)
    {
        $logger = $this->container['logger'];
        if (!isset($this->container['response'])) {
            $response = $this->prepareResponse();
        } else {
            $response = $this->container['response'];
        }

        $action_result = "";
        if ($this->app->isDebug()) {
            $logger->error('Error occurred. (This log is debug mode only) ', ['error' => get_class($errors), 'message' => $errors->getMessage()]);

            $debug_controller = isset($this->container['app.debug_controller'])
                ? $this->container['app.debug_controller']
                : __NAMESPACE__ . '\\Controller\\DebugController';
            $controller = $this->app->createController($debug_controller);

            // FIXME: debug controller method name?
            $action_result = $this->executeAction([$controller, 'dumpErrors'], ['errors' => $errors], $fire_events = false);
        } else {
            $logger->info('Error occurred. ', ['error' => get_class($errors), 'message' => $errors->getMessage()]);
            list($controller_name, $action_name) = $this->detectErrorAction($errors);
            $controller = $this->app->createController($controller_name);

            $action_result = $this->executeAction([$controller, $action_name], ['errors' => $errors], $fire_events = false);
        }

        $response->setBody($action_result);

        return $this->filterResponse($response);
    }

    public function executeAction($handler, $vars = [], $fire_events = true)
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

    protected function getErrorController()
    {
        $error_controller = isset($this->container['app.error_controller'])
            ? $this->container['app.error_controller']
            : __NAMESPACE__ . '\\Controller\\ErrorController';
        return $error_controller;
    }

    /**
     * Dispatch router with HTTP request information.
     *
     * @param $method
     * @param $path
     * @return array
     */
    protected function dispatchRouter($method, $path)
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
            break;
        case RouteDispatcher::METHOD_NOT_ALLOWED:
            $logger->debug('Routing failed. Method Not Allowd.');
            throw new HttpMethodNotAllowedException('405 Method Not Allowed');
            break;
        case RouteDispatcher::FOUND:
            $handler = $route_info[1];
            $vars = $route_info[2];
            $logger->debug('Route found.', ['handler' => $handler]);
            break;
        }

        return [$handler, $vars];
    }

    protected function detectErrorAction(\Exception $errors)
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
    protected function filterResponse(Response $response)
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
    protected function finishRequest(Response $response)
    {
        $event = new FinishRequestEvent($this->app, $response);
        $this->event_dispatcher->dispatch(DietcubeEvents::FINISH_REQUEST, $event);

        $response = $event->getResponse();

        $response->sendHeaders();
        $response->sendBody();

        return $response;
    }

    public static function getEnv($env = 'production')
    {
        if (isset($_SERVER['DIET_ENV'])) {
            $env = $_SERVER['DIET_ENV'];
        } elseif (getenv('DIET_ENV')) {
            $env = getenv('DIET_ENV');
        }
        return $env;
    }

    public static function invoke($app_class, $app_root_dir, $env)
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
