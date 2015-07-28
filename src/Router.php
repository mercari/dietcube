<?php

namespace Dietcube;

use FastRoute\RouteCollector;
use FastRoute\DataGenerator\GroupCountBased as GroupCountBasedDataGenerator;
use FastRoute\Dispatcher\GroupCountBased as GroupCountBasedDispatcher;
use FastRoute\RouteParser\Std as StdRouteParser;
use Pimple\Container;

class Router
{
    /**
     * @var GroupCountBasedDispatcher
     */
    protected $dispatcher;

    protected $container;

    protected $route_info = null;

    protected $dispatched_http_method = null;

    protected $dispatched_url = null;

    /**
     * @var RouteInterface[]
     */
    protected $routes;

    protected $named_routes;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function addRoute(RouteInterface $route)
    {
        $this->routes[] = $route;
        return $this;
    }

    public function init()
    {
        $collector = new RouteCollector(
            new StdRouteParser(),
            new GroupCountBasedDataGenerator()
        );

        foreach ($this->routes as $route) {
            foreach ($route->definition($this->container) as list($method, $route_name, $handler_name)) {
                $collector->addRoute($method, $route_name, $handler_name);
            }
        }

        $this->dispatcher = new GroupCountBasedDispatcher($collector->getData());
    }

    /**
     * URL からディスパッチ対象を取得する
     *
     * @param string $http_method
     * @param string $url
     * @return array
     */
    public function dispatch($http_method, $url)
    {
        if ($this->dispatcher === null) {
            throw new \RuntimeException('Route dispatcher is not initialized');
        }

        $this->dispatched_http_method = $http_method;
        $this->dispatched_url = $url;
        $this->route_info = $this->dispatcher->dispatch($http_method, $url);

        return $this->route_info;
    }

    /**
     * ルート名から URL を生成する
     *
     * Slim3 の Router を参考にしています
     * @see https://github.com/slimphp/Slim/blob/3494b3625ec51c2de90d9d893767d97f876e49ff/Slim/Router.php#L162
     *
     * @param  string $handler      Route handler name
     * @param  array  $data         Route URI segments replacement data
     * @param  array  $query_params Optional query string parameters
     * @return string
     * @throws \RuntimeException         If named route does not exist
     * @throws \InvalidArgumentException If required data not provided
     */
    public function url($handler, array $data = [], array $query_params = [])
    {
        if ($this->named_routes === null) {
            $this->buildNameIndex();
        }

        if (!isset($this->named_routes[$handler])) {
            throw new \RuntimeException('Named route does not exist for name: ' . $handler);
        }

        $route = $this->named_routes[$handler];
        $url = preg_replace_callback('/{([^}]+)}/', function ($match) use ($data) {
            $segment_name = explode(':', $match[1])[0];
            if (!isset($data[$segment_name])) {
                throw new \InvalidArgumentException('Missing data for URL segment: ' . $segment_name);
            }
            return $data[$segment_name];
        }, $route);

        if ($query_params) {
            $url .= '?' . http_build_query($query_params);
        }

        return $url;
    }

    public function getRouteInfo()
    {
        return $this->route_info;
    }

    public function getDispatchedMethod()
    {
        return $this->dispatched_http_method;
    }

    public function getDispatchedUrl()
    {
        return $this->dispatched_url;
    }

    protected function buildNameIndex()
    {
        $this->named_routes = [];
        foreach ($this->routes as $route) {
            foreach ($route->definition($this->container) as list($method, $route_name, $handler_name)) {
                if ($handler_name) {
                    $this->named_routes[$handler_name] = $route_name;
                }
            }
        }
    }
}
