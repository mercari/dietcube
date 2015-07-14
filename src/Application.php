<?php
/**
 * Application Context
 */

namespace Dietcube;

use Dietcube\Exception\DCException;
use Pimple\Container;

abstract class Application
{
    protected $app_root;

    protected $app_namespace;

    protected $env;

    protected $debug = false;

    /**
     * @var Config
     */
    protected $config = null;

    protected $dirs = [];

    protected $host;
    protected $protocol;
    protected $port;
    protected $path;
    protected $url;

    public function __construct($app_root, $env)
    {
        $this->app_root = $app_root;
        $this->app_namespace = $this->detectAppNamespace();
        $this->env = $env;

        $this->dirs = $this->getDefaultDirs();
    }

    public function loadConfig()
    {
        $config = [];
        foreach ($this->getConfigFiles() as $config_file) {
            $load_config_file = $this->getConfigDir() . '/' .  $config_file;
            if (!file_exists($load_config_file)) {
                continue;
            }

            $config = array_merge($config, require $load_config_file);
        }

        $this->config = new Config($config);
        $this->bootConfig();
    }

    public function initHttpRequest(Container $container)
    {
        $server = $container['global.server']->get();
        $this->host = $server['HTTP_HOST'];
        $this->port = $server['SERVER_PORT'];
        $this->protocol = (($this->port == '443' || (isset($server['X_FORWARDED_PROTO']) && $server['X_FORWARDED_PROTO'] == 'https')) ? 'https' : 'http');
        $this->path = parse_url($server['REQUEST_URI'])['path'];
        $this->url = $this->protocol . '://' . $this->host;
    }

    public function init(Container $container)
    {
    }

    abstract public function config(Container $container);

    /**
     * @return string
     */
    public function getEnv()
    {
        return $this->env;
    }

    public function getAppRoot()
    {
        return $this->app_root;
    }

    public function getAppNamespace()
    {
        return $this->app_namespace;
    }

    public function getRoute()
    {
        $route_class = $this->getAppNamespace() . '\\Route';
        return new $route_class;
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function setDir($dirname, $path)
    {
        $this->dir[$dirname] = $path;
    }

    public function getHost()
    {
        return $this->host;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getPort()
    {
        return $this->port;
    }

    public function getPath()
    {
        return $this->path;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getWebrootDir()
    {
        return $this->dirs['webroot'];
    }

    public function getResourceDir()
    {
        return $this->dirs['resource'];
    }

    public function getTemplateDir()
    {
        return $this->dirs['template'];
    }

    public function getTemplateExt()
    {
        return '.html.twig';
    }

    public function getConfigDir()
    {
        return $this->dirs['config'];
    }

    public function getTmpDir()
    {
        return $this->dirs['tmp'];
    }

    public function isDebug()
    {
        return $this->debug;
    }

    public function getConfigFiles()
    {
        return [
            'config.php',
            'config_' . $this->getEnv() . '.php',
        ];
    }

    protected function getDefaultDirs()
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

    protected function bootConfig()
    {
        $this->debug = $this->config->get('debug', false);
    }

    protected function detectAppNamespace()
    {
        $current_class = get_class($this);
        $ref = new \ReflectionClass($current_class);
        return $ref->getNamespaceName();
    }
}
