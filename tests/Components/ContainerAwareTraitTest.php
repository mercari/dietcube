<?php
/**
 *
 */

namespace Dietcube\Components;

use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 */
class ContainerAwareTraitTest extends TestCase
{
    public function testToGetContainer(): void
    {
        $container = new Container();
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

    public function getContainer(): ?Container
    {
        return $this->container;
    }
}
