<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Symfony\Component\EventDispatcher\Event;

class DietcubeEventAbstract extends Event
{
    /**
     * @var Application
     */
    protected $app;

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
