<?php
/**
 *
 */

namespace Dietcube\Components;

use PHPUnit\Framework\TestCase;

/**
 */
class ContainerAwareTraitTest extends TestCase
{
    public function testToGetContainer()
    {
        $container = new \Pimple\Container();
        $obj = new ConcreteComponentWithContainer();

        // not yet set
        $this->assertNull($obj->getContainer());

        $obj->setContainer($container);
        $this->assertInstanceOf('\\Pimple\Container', $obj->getContainer());
    }
}

class ConcreteComponentWithContainer
{
    use ContainerAwareTrait;

    public function getContainer()
    {
        return $this->container;
    }
}
