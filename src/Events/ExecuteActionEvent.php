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
     * @return executable
     */
    public function getExecutable()
    {
        return $this->executable;
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
    }

    /**
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
}
