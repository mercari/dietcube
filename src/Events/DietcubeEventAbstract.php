<?php

namespace Dietcube\Events;

use Dietcube\Application;
use Symfony\Component\EventDispatcher\Event;

abstract class DietcubeEventAbstract extends Event
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @return Application
     */
    public function getApplication(): Application
    {
        return $this->app;
    }
}
