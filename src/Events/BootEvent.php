<?php
/**
 *
 */

namespace Dietcube\Events;

use Dietcube\Application;
use Dietcube\Response;

class BootEvent extends DietcubeEventAbstract
{
    public function __construct(Application $app)
    {
        $this->app = $app;
    }
}
