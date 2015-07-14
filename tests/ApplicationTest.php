<?php
/**
 *
 */

namespace Dietcube;

class ApplicationTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $app = $this->getMockForAbstractClass(
            '\\Dietcube\\Application',
            [__DIR__, 'development']
        );
        $this->assertEquals(__DIR__, $app->getAppRoot());
        $this->assertEquals('development', $app->getEnv());
        $this->assertEquals([
                'config.php',
                'config_development.php',
            ],
            $app->getConfigFiles()
        );
    }
}
