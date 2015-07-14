<?php
/**
 *
 */

namespace Dietcube;

use Pimple\Container;

/**
 * @backupGlobals
 */
class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $_SERVER['HTTP_HOST'] = 'www.dietcube.org';
        $_SERVER['SERVER_PORT'] = '80';
        $_SERVER['REQUEST_URI'] = '/documentation/setup';
        $container = new Container();
        $container['global.server'] = new Parameters($_SERVER);

        $app = new MockApplication(__DIR__, 'development');

        $this->assertEquals(__DIR__, $app->getAppRoot());
        $this->assertEquals('development', $app->getEnv());
        $this->assertEquals([
                'config.php',
                'config_development.php',
            ],
            $app->getConfigFiles()
        );
        $this->assertEquals('Dietcube', $app->getAppNamespace());
        $this->assertEquals(false, $app->isDebug());

        $app->initHttpRequest($container);
        $this->assertEquals('www.dietcube.org', $app->getHost());
        $this->assertEquals('80', $app->getPort());
        $this->assertEquals('/documentation/setup', $app->getPath());
        $this->assertEquals('http', $app->getProtocol());
        $this->assertEquals('http://www.dietcube.org', $app->getUrl());

        $this->assertEquals(dirname(__DIR__) . '/webroot', $app->getWebrootDir());
        $this->assertEquals(__DIR__ . '/resource', $app->getResourceDir());
        $this->assertEquals(__DIR__ . '/template', $app->getTemplateDir());
        $this->assertEquals('.html.twig', $app->getTemplateExt());
        $this->assertEquals(__DIR__ . '/config', $app->getConfigDir());
        $this->assertEquals(dirname(__DIR__) . '/tmp', $app->getTmpDir());
    }
}

class MockApplication extends Application
{
    public function config(Container $container)
    {
    }
}
