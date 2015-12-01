<?php
/**
 *
 */

namespace Dietcube\Components;

/**
 */
class LoggerAwareTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testInstanciate()
    {
        $logger = new \Monolog\Logger('testlogger');
        $obj = new ConcreteComponentWithLogger();

        // not yet set
        $this->assertNull($obj->getLogger());

        $obj->setLogger($logger);
        $this->assertInstanceOf('\\Psr\\Log\\LoggerInterface', $obj->getLogger());
    }
}

class ConcreteComponentWithLogger
{
    use LoggerAwareTrait;

    public function getLogger()
    {
        return $this->logger;
    }
}
