<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;
use Symfony\Component\EventDispatcher\Event;

class BootEvent extends Event
{
    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
