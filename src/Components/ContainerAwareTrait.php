<?php
/**
 *
 */

namespace Dietcube\Components;

use Pimple\Container;

trait ContainerAwareTrait
{
    /**
     * @var Container
     */
    protected $container = null;

    /**
     * @param Container $container
     */
    public function setContainer(Container $container)
    {
        $this->container = $container;

        return $this;
    }
}
