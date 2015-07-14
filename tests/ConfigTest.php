<?php
/**
 *
 */

namespace Dietcube;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testInstantiate()
    {
        $config_array = [
            'diet' => 'cake',
            'database' => [
                'host' => 'localhost',
                'port' => 3306,
            ],
        ];
        $config = new Config($config_array);

        $this->assertEquals('cake', $config->get('diet'));
        $this->assertEquals(12345, $config->get('cake', 12345)); // default

        // array
        $this->assertEquals([
            'host' => 'localhost',
                'port' => 3306,
            ], $config->get('database'));
        $this->assertEquals('localhost', $config->get('database.host'));
        $this->assertEquals(3306, $config->get('database.port'));
        $this->assertEquals($config_array, $config->get());
    }
}
