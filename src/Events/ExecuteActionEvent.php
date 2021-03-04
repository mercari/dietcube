<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;

class ExecuteActionEvent extends DietcubeEventAbstract
{
    /** @var callable  */
    protected $executable;
    /** @var array */
    protected $vars = [];

    /** @var string */
    protected $result = "";

    public function __construct(Application $app, callable $executable, array $vars)
    {
        $this->app = $app;
        $this->executable = $executable;
        $this->vars = $vars;
    }

    /**
     * @param callable $executable
     * @return ExecuteActionEvent
     */
    public function setExecutable(callable $executable): self
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
     * @return ExecuteActionEvent
     */
    public function setExecutableByHandler(string $handler): self
    {
        [$controller_name, $action_name] = $this->app->getControllerByHandler($handler);
        $controller = $this->app->createController($controller_name);

        $this->setExecutable([$controller, $action_name]);

        return $this;
    }

    /**
     * @return callable executable
     */
    public function getExecutable(): callable
    {
        return $this->executable;
    }

    /**
     * @param array $vars
     * @return ExecuteActionEvent
     */
    public function setVars(array $vars): self
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * @return array
     */
    public function getVars(): array
    {
        return $this->vars;
    }

    /**
     * @param string $result
     * @return ExecuteActionEvent
     */
    public function setResult(string $result): self
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }
}
