<?php
/**
 *
 */

namespace Dietcube;

use Dietcube\Exception\DCException;
use Pimple\Container;
use FastRoute\Dispatcher as RouteDispatcher;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
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
        if (!is_file($path)) {
            if (!touch($path)) {
                throw new DCException("Cannot create log file: '{$path}'");
            }
        } elseif (!is_writable($path)) {
            throw new DCException("Log path '{$path}' is not writable.");
        }

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler($path, $level));
        $logger->pushProcessor(new PsrLogMessageProcessor);

        return $logger;
    }

    protected function createRenderer()
    {
        $config = $this->container['app.config'];
        $loader = new \Twig_Loader_Filesystem($this->app->getTemplateDir());
        $twig = new \Twig_Environment($loader, [
            'debug' => $config->get('debug', false),
            'cache' => $config->get('twig.cache', null),
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

    public function handleRequest()
    {
        $container = $this->container;
        $logger = $container['logger'];
        $router = $container['router'];
        $debug = $container['app.config']->get('debug');

        $method = $container['global.server']->get('REQUEST_METHOD');
        $path = $container['app']->getPath();
        $logger->debug('Router dispatch.', ['method' => $method, 'path' => $path]);

        $router->init();
        $route_info = $router->dispatch($method, $path);

        $handler = null;
        $vars = [];

        switch ($route_info[0]) {
        case RouteDispatcher::NOT_FOUND:
            if ($debug) {
                $logger->debug('Routing failed. Not Found.');
                throw new DCException('404 Not Found');
            }

            $controller_name = $this->getErrorController();
            $action_name = Controller::ACTION_NOT_FOUND;
            break;
        case RouteDispatcher::METHOD_NOT_ALLOWED:
            if ($debug) {
                $logger->debug('Routing failed. Method Not Allowd.');
                $allowed_methods = $route_info[1];
                throw new DCException('405 Method Not Allowed');
            }

            $controller_name = $this->getErrorController();
            $action_name = Controller::ACTION_METHOD_NOT_ALLOWED;
            break;
        case RouteDispatcher::FOUND:
            $handler = $route_info[1];
            $vars = $route_info[2];

            list($controller_name, $action_name) = $this->detectAction($handler);
            $logger->debug('Route found.', ['handler' => $handler]);
            break;
        }

        $action_result = $this->executeAction($controller_name, $action_name, $vars);

        return $action_result;
    }

    public function executeAction($controller_name, $action_name, $vars = [])
    {
        $logger = $this->container['logger'];

        $controller = new $controller_name($this->container);
        $controller->setVars('env', $this->container['app']->getEnv());
        $controller->setVars('config', $this->container['app.config']->getData());

        if (!is_callable([$controller, $action_name])) {
            $logger->error('Action not dispatchable.', ['controller' => $controller_name, 'action' => $action_name]);
            throw new DCException("'{$controller_name}' doesn't have such an action '{$action_name}'");
        }

        $logger->debug(
            'Dispatch action. controller={controller}::{action}({vars})',
            ['controller' => $controller_name, 'action' => $action_name, 'vars' => $vars]);

        $action_result = call_user_func_array([$controller, $action_name], $vars);
        return $action_result;
    }


    public function renderError($errors)
    {
        $logger = $this->container['logger'];

        if (!$this->app->isDebug()) {
            $logger->error('Error occurred. ', ['error' => get_class($errors), 'message' => $errors->getMessage()]);
            echo $this->executeAction(
                $this->getErrorController(),
                Controller::ACTION_INTERNAL_ERROR,
                [$errors]
            );
            return null;
        }

        if ($errors instanceof \Exception) {
            echo<<<EOT
<html>
    <head>
        <style>
        h1 {
            font-size: 2em;
            margin: 2em 0 1.4em;
        }
        h2 {
            font-size: 1.6em;
            margin: 1.8em 0 1.4em;
        }
        pre {
            padding: 20px;
            background: #fff;
            border: solid 1px #ccc;
            border-radius: 10px;
            font-family: menlo, monospace;
            word-wrap: break-word;
        }
        </style>
    </head>
    <body style="padding: 20px; font-family: 'Hiragino Kaku Gothic', Helvetica; font-size: 16px; background: #fcfcfc; ">
    <div style="margin: 0 auto;">
        <h1>Dietcake Critical Error</h1>
        <p style="font-size: 1.4em;">
            {$errors->getMessage()}
        </p>

        <div>
            <h2>Stack Trace</h2>
            <pre>{$errors->getTraceAsString()}</pre>
        </div>
    </div>
    </body>
</html>
EOT;
        }
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
            if ($response !== null) {
                echo $response;
            }
        } catch (\Exception $e) { //とりあえず
            $dispatcher->renderError($e);
        }
    }
}
