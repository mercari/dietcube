<?php
/**
 *
 */

namespace Dietcube;

use Dietcube\Exception\DCException;
use Dietcube\Exception\HttpNotFoundException;
use Dietcube\Exception\HttpMethodNotAllowedException;
use Dietcube\Exception\HttpErrorException;
use Pimple\Container;
use FastRoute\Dispatcher as RouteDispatcher;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

class Dispatcher
{
    protected $app;
    protected $container;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function boot()
    {
        $this->app->loadConfig();

        $container = $this->container = new Container();
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

    protected function prepareReponse()
    {
        $response = new Response();
        $this->container['response'] = $response;

        return $response;
    }

    public function handleRequest()
    {
        $container = $this->container;
        $logger = $container['logger'];
        $router = $container['router'];
        $debug = $container['app.config']->get('debug');

        // prepare handle request
        $response = $this->prepareReponse();

        $method = $container['global.server']->get('REQUEST_METHOD');
        $path = $container['app']->getPath();
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

            list($controller_name, $action_name) = $this->detectAction($handler);
            $logger->debug('Route found.', ['handler' => $handler]);
            break;
        }

        $action_result = $this->executeAction($controller_name, $action_name, $vars);
        $response = $response->setBody($action_result);

        return $response;
    }

    public function handleError(\Exception $errors)
    {
        $logger = $this->container['logger'];
        if (!isset($this->container['response'])) {
            $response = $this->prepareReponse();
        } else {
            $response = $this->container['response'];
        }

        $action_result = "";
        if ($this->app->isDebug()) {
            $logger->error('Error occurred. (This log is debug mode only) ', ['error' => get_class($errors), 'message' => $errors->getMessage()]);

            $debug_controller = isset($this->container['app.debug_controller'])
                ? $this->container['app.debug_controller']
                : __NAMESPACE__ . '\\Controller\\DebugController';
            $action_result = $this->executeAction($debug_controller, 'dumpErrors', ['errors' => $errors]);
        } else {
            $logger->info('Error occurred. ', ['error' => get_class($errors), 'message' => $errors->getMessage()]);
            list($controller_name, $action_name) = $this->detectErrorAction($errors);

            $action_result = $this->executeAction($controller_name, $action_name, ['errors' => $errors]);
        }

        $response->setBody($action_result);
        return $response;
    }

    public function executeAction($controller_name_or_callable, $action_name, $vars = [])
    {
        $logger = $this->container['logger'];
        $executable = null;
        if (is_callable($controller_name_or_callable)) {
            $executable = $controller_name_or_callable;
            $controller_name = 'function()';
            $action_name = '-';
        } else {
            $controller_name = $controller_name_or_callable;
            $controller = new $controller_name($this->container);
            $controller->setVars('env', $this->container['app']->getEnv());
            $controller->setVars('config', $this->container['app.config']->getData());
            $executable = [$controller, $action_name];
        }

        if (!is_callable($executable)) {
            $logger->error('Action not dispatchable.', ['controller' => $controller_name, 'action' => $action_name]);
            throw new DCException("'{$controller_name}' doesn't have such an action '{$action_name}'");
        }

        $logger->debug(
            'Exceute action.',
            ['controller' => $controller_name, 'action' => $action_name, 'vars' => $vars]);

        $action_result = call_user_func_array($executable, $vars);
        return $action_result;
    }

    protected function getErrorController()
    {
        $error_controller = isset($this->container['app.error_controller'])
            ? $this->container['app.error_controller']
            : __NAMESPACE__ . '\\Controller\\ErrorController';
        return $error_controller;
    }

    protected function detectAction($handler)
    {
        if (is_callable($handler)) {
            return [$handler, null];
        }
        $logger = $this->container['logger'];

        // @TODO check
        list($controller, $action_name) = explode('::', $handler);
        if (!$controller || !$action_name) {
            throw new DCException('Error: handler error');
        }

        $controller_name = $this->container['app']->getAppNamespace()
            . '\\Controller\\'
            . str_replace('/', '\\', $controller)
            . 'Controller';

        return [$controller_name, $action_name];
    }

    protected function detectErrorAction(\Exception $errors)
    {
        $error_controller = $this->getErrorController();
        if ($errors instanceof HttpNotFoundException) {
            return [$error_controller, Controller::ACTION_NOT_FOUND];
        } elseif ($errors instanceof HttpMethodNotAllowedException) {
            return [$error_controller, Controller::ACTION_METHOD_NOT_ALLOWED];
        }

        // Do internalError acition for any errors.
        return [$error_controller, Controller::ACTION_INTERNAL_ERROR];
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
        } catch (\Exception $e) { //とりあえず
            $response = $dispatcher->handleError($e);
        }

        if ($response instanceof Response) {
            $response->sendHeaders();
            $response->sendBody();
        } else {
        }
    }
}
