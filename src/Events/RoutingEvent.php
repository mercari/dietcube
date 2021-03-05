<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Router;

class RoutingEvent extends DietcubeEventAbstract
{
    /** @var Router  */
    protected $router;

    protected $handler;
    protected $vars = [];

    public function __construct(Application $app, Router $router)
    {
        $this->app = $app;
        $this->router = $router;
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function setRouter(Router $router): self
    {
        $this->router = $router;
        return $this;
    }

    public function setHandler($handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @param mixed $_handler Deprecated.
     * @return mixed
     */
    public function getHandler($_handler = null)
    {
        return $this->handler;
    }

    public function setVars(array $vars): self
    {
        $this->vars = $vars;
        return $this;
    }

    /**
     * @param array $_vars Deprecated.
     * @return array
     */
    public function getVars(array $_vars = []): array
    {
        return $this->vars;
    }

    public function setRouteInfo($handler, array $vars = []): self
    {
        $this->handler = $handler;
        $this->vars = $vars;
        return $this;
    }

    public function getRouteInfo(): array
    {
        return [$this->handler, $this->vars];
    }
}
