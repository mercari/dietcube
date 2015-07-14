<?php
/**
 *
 */

namespace Dietcube;

class ParametersTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $params = new Parameters($_SERVER);
        $this->assertEquals($_SERVER['PATH'], $params->get('PATH'));
        $this->assertEquals($_SERVER, $params->getData());

        $this->assertEquals(null, $params->get('THE_KEY_NOT_EXISTS'));
        $this->assertEquals('default-value', $params->get('THE_KEY_NOT_EXISTS', 'default-value'));
    }
}
