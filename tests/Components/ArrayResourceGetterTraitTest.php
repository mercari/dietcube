<?php
/**
 *
 */

namespace Dietcube\Components;

use Pimple\Container;

/**
 * @backupGlobals
 */
class ArrayResourceGetterTraitTest extends \PHPUnit_Framework_TestCase
{
    public function testGetterTest()
    {
        $data = [
            'config1' => 1,
            'config_bool' => true,
            'config_string' => 'data',
            'config_array' => [1, 2, 3],
            'db' => [
                'dsn' => 'mysql:123',
                'user' => 'aoi_miyazaki',
            ],
        ];
        $obj = new ConcreteResource($data);

        $this->assertEquals(1, $obj->getResource('config1'));
        $this->assertTrue($obj->getResource('config_bool'));
        $this->assertSame([1, 2, 3], $obj->getResource('config_array'));
        $this->assertNull($obj->getResource('config_string1'), 'non exists key');

        $this->assertSame(2, $obj->getResource('config2', 2), 'non-exiss key and default');

        $this->assertSame($data, $obj->getResource(), 'get all');
        $this->assertSame($data, $obj->getResourceData(), 'get all');

        $this->assertEquals('aoi_miyazaki', $obj->getResource('db.user'));
        $this->assertSame([
                'dsn' => 'mysql:123',
                'user' => 'aoi_miyazaki',
            ], $obj->getResource('db'));
        $this->assertEquals('aoi-no-password', $obj->getResource('db.password', 'aoi-no-password'), 'default');

        $this->assertEquals(null, $obj->getResource('db.user.name'));

        // clear
        $obj->clearResource();
        $this->assertSame([], $obj->getResourceData());
        $this->assertEquals(null, $obj->getResource('config1'));
    }
}

class ConcreteResource
{
    use ArrayResourceGetterTrait;

    public function __construct(array $array = [])
    {
        $this->_array_resource = $array;
    }
}
