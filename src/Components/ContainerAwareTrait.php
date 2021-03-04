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
     * @return self
     */
    public function setContainer(Container $container): self
    {
        $this->container = $container;

        return $this;
    }
}
