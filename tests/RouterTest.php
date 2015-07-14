<?php

namespace Dietcube;

use Pimple\Container;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testDispatchPageFound()
    {
        $router = static::createRouter();

        $route_info = $router->dispatch('GET', '/about');
        $this->assertSame([\FastRoute\Dispatcher::FOUND, 'Page::about', []], $route_info);

        $route_info = $router->dispatch('GET', '/privacy');
        $this->assertSame([\FastRoute\Dispatcher::FOUND, 'Page::privacy', []], $route_info);
    }

    public function testDispatchWithData()
    {
        $router = static::createRouter();

        $route_info = $router->dispatch('GET', '/user/12345');
        $this->assertSame([\FastRoute\Dispatcher::FOUND, 'User::detail', ['id' => '12345']], $route_info);
    }

    public function testDispatchPageNotFound()
    {
        $router = static::createRouter();

        $route_info = $router->dispatch('GET', '/unknown');
        $this->assertSame([\FastRoute\Dispatcher::NOT_FOUND], $route_info);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDispatchIsNotInitialized()
    {
        $router = static::createRouterWithoutInit();

        $router->dispatch('GET', '/about');
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testNotExistHandler()
    {
        $router = static::createRouter();

        $router->url('Page::notExistsHandler');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNotExistSegumentName()
    {
        $router = static::createRouter();

        $router->url('User::detail', ['invalid_key_name' => 12345]);
    }

    public function testGenerateUrl()
    {
        $router = static::createRouter();

        $this->assertSame('/about', $router->url('Page::about'));
        $this->assertSame('/privacy', $router->url('Page::privacy'));
    }

    public function testGenerateUrlWithData()
    {
        $router = static::createRouter();

        $this->assertSame('/user/12345', $router->url('User::detail', ['id' => 12345]));
    }

    public function testGenerateUrlWithDataAndQueryParams()
    {
        $router = static::createRouter();

        $this->assertSame('/user/12345?from=top', $router->url('User::detail', ['id' => 12345], ['from' => 'top']));
    }

    public static function createRouter()
    {
        $router = static::createRouterWithoutInit();
        $router->init();
        return $router;
    }

    public static function createRouterWithoutInit()
    {
        $router = new Router(new Container);
        $router->addRoute(new RouteFixture);
        return $router;
    }
}


class RouteFixture implements RouteInterface
{
    public function definition(Container $container)
    {
        return [
            ['GET', '/about', 'Page::about'],
            ['GET', '/privacy', 'Page::privacy'],
            ['GET', '/user/{id}', 'User::detail'],
        ];
    }
}
