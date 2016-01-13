<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;

class BootEvent extends DietcubeEventAbstract
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
