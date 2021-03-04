<?php

namespace Dietcube\Components;

use Pimple\Container;

trait ContainerAwareTrait
{
    /**
     * @var Container|null
     */
    protected $container;

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
