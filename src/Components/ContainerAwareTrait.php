<?php
/**
 *
 */

namespace Dietcube\Components;

use Pimple\Container;

trait ContainerAwareTrait
{
    protected $container = null;

    public function setContainer(Container $container)
    {
        $this->container = $container;
    }
}
