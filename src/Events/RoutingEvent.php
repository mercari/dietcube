<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Router;
use Symfony\Component\EventDispatcher\Event;

class RoutingEvent extends Event
{
    /**
     * @var Application
     */
    protected $app;

    protected $router;

    protected $controller_name;
    protected $action_name;
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

    public function setControllerName($controller_name)
    {
        $this->controller_name = $controller_name;
    }

    public function setActionName($action_name)
    {
        $this->action_name = $action_name;
    }

    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }

    public function setRouteInfo($controller_name, $action_name, array $vars = [])
    {
        $this->controller_name = $controller_name;
        $this->action_name = $action_name;
        $this->vars = $vars;
    }

    public function getRouteInfo()
    {
        return [$this->controller_name, $this->action_name, $this->vars];
    }
}
