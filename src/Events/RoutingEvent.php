<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Router;

class RoutingEvent extends DietcubeEventAbstract
{
    protected $router;

    protected $handler;
    protected $vars = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    public function getHandler($handler)
    {
        return $this->handler;
    }

    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    public function getVars(array $vars)
    {
        return $this->vars;
    }

    public function setRouteInfo($handler, array $vars = [])
    {
        $this->handler = $handler;
        $this->vars = $vars;
    }

    public function getRouteInfo()
    {
        return [$this->handler, $this->vars];
    }
}
