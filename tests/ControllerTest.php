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

    public function testSetVars()
    {
        $app = $this->getMockBuilder('\Dietcube\Application')->disableOriginalConstructor()->getMockForAbstractClass();
        $renderer = $this->getMock('Twig_Environment');
        $renderer->expects($this->any())->method('render')->will($this->returnArgument(1));

        $container = self::getContainerAsFixture(['app' => $app, 'app.renderer' => $renderer]);
        $controller = new Controller($container);

        $controller->setVars('foo', 'bar');
        $render = $this->getInvokableMethod('render');

        $this->assertEquals(['foo' => 'bar'], $render->invokeArgs($controller, ['template']));

        $controller->setVars(['foo' => 'baz']);
        $this->assertEquals(['foo' => 'baz'], $render->invokeArgs($controller, ['template']));
    }

    public function testRenderVars()
    {
        $app = $this->getMockBuilder('\Dietcube\Application')->disableOriginalConstructor()->getMockForAbstractClass();

        $renderer = $this->getMock('Twig_Environment');
        $renderer->expects($this->any())->method('render')->will($this->returnArgument(1));

        $container = self::getContainerAsFixture(['app' => $app, 'app.renderer' => $renderer]);
        $controller = new Controller($container);

        $controller->setVars('key', 'value');
        $render = $this->getInvokableMethod('render');

        $this->assertEquals(['key' => 'value'], $render->invokeArgs($controller, ['template']));
        $this->assertEquals(['key' => 'value2'], $render->invokeArgs($controller, ['template', ['key' => 'value2']]));
    }

    public function testFindTemplate()
    {
        $app = $this->getMockBuilder('Dietcube\Application')->disableOriginalConstructor()->getMock();
        $app->expects($this->atLeastOnce())->method('getTemplateExt')->will($this->returnValue('.html.jinja2'));

        $container = self::getContainerAsFixture(['app' => $app]);
        $controller = new Controller($container);
        $findTemplate = $this->getInvokableMethod('findTemplate');

        $this->assertEquals('template.html.jinja2', $findTemplate->invokeArgs($controller, ['template']));
        $this->assertEquals('index.html.jinja2', $findTemplate->invokeArgs($controller, ['index']));
    }
}
