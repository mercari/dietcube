<?php
/**
 *
 */

namespace Dietcube;

use Pimple\Container;

/**
 * @backupGlobals
 */
class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetContainerValue()
    {
        $hoge = new \StdClass();
        $container = self::getContainerAsFixture(['hoge' => $hoge]);
        $controller = new Controller($container);

        $method = $this->getInvokableMethod('get');
        $this->assertSame($hoge, $method->invokeArgs($controller, ['hoge']));
    }

    public function testIsPostOnPost()
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $container = self::getContainerAsFixture(['global.server' => new Parameters($_SERVER)]);
        $controller = new Controller($container);

        $method = $this->getInvokableMethod('isPost');
        $this->assertTrue($method->invoke($controller));

    }

    public function testIsPostOnGet()
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $container = self::getContainerAsFixture(['global.server' => new Parameters($_SERVER)]);
        $controller = new Controller($container);

        $method = $this->getInvokableMethod('isPost');
        $this->assertFalse($method->invoke($controller));
    }

    public static function getInvokableMethod($method)
    {
        $class = new \ReflectionClass('\\Dietcube\\Controller');
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }

    public static function getContainerAsFixture(array $fixture = [])
    {
        $container = new Container();

        foreach ($fixture as $key => $value) {
            $container[$key] = $value;
        }
        return $container;
    }
}
