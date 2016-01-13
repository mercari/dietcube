<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;

class ExecuteActionEvent extends DietcubeEventAbstract
{
    protected $executable;
    protected $vars = [];

    protected $result = "";

    public function __construct(Application $app, $executable, array $vars)
    {
        $this->app = $app;
        $this->executable = $executable;
        $this->vars = $vars;
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }

    /**
     * @param callable $executable
     */
    public function setExecutable($executable)
    {
        if (!is_callable($executable)) {
            throw new \InvalidArgumentException("Passed argument for setExecutable is not callable.");
        }
        $this->executable = $executable;

        return $this;
    }

    /**
     * Set executable by valid handler.
     * This is a shortcut method to create controller by shorter name.
     * e.g. User::login
     *
     * @param string $handler
     */
    public function setExecutableByHandler($handler)
    {
        list($controller_name, $action_name) = $this->app->getControllerByHandler($handler);
        $controller = $this->app->createController($controller_name);

        $this->setExecutable([$controller, $action_name]);

        return $this;
    }

    /**
     * @return callable executable
     */
    public function getExecutable()
    {
        return $this->executable;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param string $result
     */
    public function setResult($result)
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
