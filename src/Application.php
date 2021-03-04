<?php
/**
 * Application Context
 */

namespace Dietcube;

use Dietcube\Components\ContainerAwareTrait;
use Dietcube\Exception\DCException;
use Pimple\Container;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Processor\PsrLogMessageProcessor;

abstract class Application
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    protected $app_root;

    /**
     * @var string
     */
    protected $app_namespace;

    /**
     * @var string
     */
    protected $env;

    /**
     * @var bool
     */
    protected $debug = false;

    /**
     * @var Config|null
     */
    protected $config = null;

    /** @var array|string[]  */
    protected $dirs = [];

    protected $host;
    protected $protocol;
    protected $port;
    protected $path;
    protected $url;

    /**
     * Application constructor.
     * @param string $app_root
     * @param string $env
     */
    public function __construct(string $app_root, string $env)
    {
        $this->app_root = $app_root;
        $this->app_namespace = $this->detectAppNamespace();
        $this->env = $env;

        $this->dirs = $this->getDefaultDirs();
    }

    public function getContainer(): ?Container
    {
        return $this->container;
    }

    public function loadConfig(): void
    {
        $config = [];
        foreach ($this->getConfigFiles() as $config_file) {
            $load_config_file = $this->getConfigDir() . '/' .  $config_file;
            if (!file_exists($load_config_file)) {
                continue;
            }

            $config[] = array_merge($config, require $load_config_file);
        }

        $this->config = new Config($config);
        $this->bootConfig();
    }

    public function initHttpRequest(Container $container): void
    {
        $server = $container['global.server']->get();
        $this->host = $server['HTTP_HOST'];
        $this->port = $server['SERVER_PORT'];
        $this->protocol = (($this->isHTTPS() || (isset($server['X_FORWARDED_PROTO']) && $server['X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http');
        $this->path = parse_url($server['REQUEST_URI'])['path'];
        $this->url = $this->protocol . '://' . $this->host;
    }

    protected function isHTTPS(): bool
    {
        return (int)$this->getPort() === 443;
    }

    public function init(Container $container)
    {
    }

    abstract public function config(Container $container);

    public function getEnv(): string
    {
        return $this->env;
    }

    public function getAppRoot(): string
    {
        return $this->app_root;
    }

    public function getAppNamespace(): string
    {
        return $this->app_namespace;
    }

    public function getRoute()
    {
        $route_class = $this->getAppNamespace() . '\\Route';
        return new $route_class;
    }

    public function getConfig(): ?Config
    {
        return $this->config;
    }

    public function setDir($dirname, $path): void
    {
        $this->dirs[$dirname] = $path;
    }

    /**
     * @return mixed
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return mixed
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return mixed
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return mixed
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return mixed
     */
    public function getUrl()
    {
        return $this->url;
    }

    public function getWebrootDir(): string
    {
        return $this->dirs['webroot'];
    }

    public function getResourceDir(): string
    {
        return $this->dirs['resource'];
    }

    public function getTemplateDir(): string
    {
        return $this->dirs['template'];
    }

    public function getTemplateExt(): string
    {
        return '.html.twig';
    }

    public function getConfigDir(): string
    {
        return $this->dirs['config'];
    }

    public function getTmpDir(): string
    {
        return $this->dirs['tmp'];
    }

    public function getVendorDir(): string
    {
        return $this->dirs['vendor'];
    }

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getConfigFiles(): array
    {
        return [
            'config.php',
            'config_' . $this->getEnv() . '.php',
        ];
    }

    /**
     * @param string $handler
     * @return array
     */
    public function getControllerByHandler(string $handler): array
    {
        [$controller, $action_name] = explode('::', $handler, 2);
        if (!$controller || !$action_name) {
            throw new DCException('Error: handler error');
        }

        $controller_name = $this->getAppNamespace()
            . '\\Controller\\'
            . str_replace('/', '\\', $controller)
            . 'Controller';

        return [$controller_name, $action_name];
    }

    /**
     * @param string $controller_name
     * @return mixed
     */
    public function createController(string $controller_name)
    {
        $controller = new $controller_name($this->container);
        $controller->setVars('env', $this->getEnv());
        $controller->setVars('config', $this->container['app.config']->getData());

        return $controller;
    }

    /**
     * @return string[]
     */
    protected function getDefaultDirs(): array
    {
        return [
            'controller' => $this->app_root . '/Controller',
            'config'     => $this->app_root . '/config',
            'template'   => $this->app_root . '/template',
            'resource'   => $this->app_root . '/resource',
            'webroot'    => dirname($this->app_root) . '/webroot',
            'tests'      => dirname($this->app_root) . '/tests',
            'vendor'     => dirname($this->app_root) . '/vendor',
            'tmp'        => dirname($this->app_root) . '/tmp',
        ];
    }

    protected function bootConfig(): void
    {
        $this->debug = $this->config->get('debug', false);
    }

    protected function detectAppNamespace(): string
    {
        $ref = new \ReflectionObject($this);
        return $ref->getNamespaceName();
    }

    public function createLogger($path, $level = Logger::WARNING): Logger
    {
        $logger = new Logger('app');
        $logger->pushProcessor(new PsrLogMessageProcessor);

        if (is_writable($path) || is_writable(dirname($path))) {
            $logger->pushHandler(new StreamHandler($path, $level));
        } else {
            if ($this->isDebug()) {
                throw new DCException("Log path '{$path}' is not writable. Make sure your logger.path of config.");
            }
            $logger->pushHandler(new ErrorLogHandler(ErrorLogHandler::OPERATING_SYSTEM, $level));
            $logger->warning("Log path '{$path}' is not writable. Make sure your logger.path of config.");
            $logger->warning("error_log() is used for application logger instead at this time.");
        }

        return $logger;
    }
}
