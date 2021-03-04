<?php
/**
 *
 */

namespace Dietcube;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Pimple\Container;
use Twig\Environment;

/**
 * @backupGlobals
 */
class ControllerTest extends TestCase
{
    public function testGetContainerValue(): void
    {
        $hoge = new \StdClass();
        $container = self::getContainerAsFixture(['hoge' => $hoge]);
        $controller = new Controller($container);

        $method = self::getInvokableMethod('get');
        self::assertSame($hoge, $method->invokeArgs($controller, ['hoge']));
    }

    public function testIsPostOnPost(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'post';
        $container = self::getContainerAsFixture(['global.server' => new Parameters($_SERVER)]);
        $controller = new Controller($container);

        $method = $this->getInvokableMethod('isPost');
        $this->assertTrue($method->invoke($controller));
    }

    public function testIsPostOnGet(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'get';
        $container = self::getContainerAsFixture(['global.server' => new Parameters($_SERVER)]);
        $controller = new Controller($container);

        $method = self::getInvokableMethod('isPost');
        self::assertFalse($method->invoke($controller));
    }

    public static function getInvokableMethod($method): \ReflectionMethod
    {
        $class = new \ReflectionClass(Controller::class);
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method;
    }

    public static function getContainerAsFixture(array $fixture = []): Container
    {
        $container = new Container();

        foreach ($fixture as $key => $value) {
            $container[$key] = $value;
        }
        return $container;
    }

    public function testSetVars(): void
    {
        $dummy_template_name = 'dummy';
        $expected_var_key = 'foo';
        $expected_var_value = 'bar';
        $expected_argument_for_render = [$expected_var_key => $expected_var_value];

        $app = new DummyApplication(__DIR__, 'development');
        $renderer = $this->createMock(Environment::class);
        // Expectation for arguments on rendered
        $renderer
            ->method('render')
            ->with(
                self::identicalTo($dummy_template_name . '.html.twig'),
                self::identicalTo($expected_argument_for_render)
            )
            ->willReturn('rendered_dummy');

        $container = self::getContainerAsFixture(['app' => $app, 'app.renderer' => $renderer]);
        $controller = new DummyController($container);

        $controller->setVars($expected_var_key, $expected_var_value);
        self::assertEquals('rendered_dummy', $controller->doRender($dummy_template_name, []));
    }

    public function testRenderVars(): void
    {
        $dummy_template_name = 'dummy';
        $expected_var_key = 'key';
        $expected_var_value = 'value';
        $expected_argument_for_render = [$expected_var_key => $expected_var_value];

        $app = new DummyApplication(__DIR__, 'development');
        $renderer = $this->createMock(Environment::class);
        // Expectation for arguments on renderer
        $renderer
            ->method('render')
            ->with(
                self::identicalTo($dummy_template_name . '.html.twig'),
                self::identicalTo($expected_argument_for_render)
            )
            ->willReturn('rendered_dummy');

        $container = self::getContainerAsFixture(['app' => $app, 'app.renderer' => $renderer]);
        $controller = new DummyController($container);
        // Didn't call to `setVars` before render
        self::assertEquals('rendered_dummy', $controller->doRender($dummy_template_name, $expected_argument_for_render));
    }

    public function testFindTemplate(): void
    {
        $app = new DummyApplication(__DIR__, 'development');

        $container = self::getContainerAsFixture(['app' => $app]);
        $controller = new DummyController($container);

        self::assertEquals('template.html.twig', $controller->doFindTemplate('template'));
        self::assertEquals('index.html.twig', $controller->doFindTemplate('index'));
    }
}
