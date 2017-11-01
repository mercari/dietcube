<?php
/**
 *
 */

namespace Dietcube\Components;

use PHPUnit\Framework\TestCase;
use Pimple\Container;

/**
 * @backupGlobals
 */
class ArrayResourceTraitTest extends TestCase
{
    public function testGetAndSet()
    {
        $data = [
            'config1' => 1,
            'db' => [
                'dsn' => 'mysql:123',
                'user' => 'aoi_miyazaki',
            ],
        ];
        $obj = new ConcreteResource2($data);

        $this->assertEquals(1, $obj->getResource('config1'));

        $this->assertSame($data, $obj->getResource(), 'get all');
        $this->assertSame($data, $obj->getResourceData(), 'get all');

        $this->assertEquals('aoi_miyazaki', $obj->getResource('db.user'));
        $this->assertSame([
                'dsn' => 'mysql:123',
                'user' => 'aoi_miyazaki',
            ], $obj->getResource('db'));
        $this->assertEquals('aoi-no-password', $obj->getResource('db.password', 'aoi-no-password'), 'default');

        $this->assertEquals(null, $obj->getResource('db.user.name'));

        // set
        $obj->setResource('config1', 2);
        $this->assertEquals(2, $obj->getResource('config1'));

        $obj->setResource('db.user', 'aya_ueto');
        $this->assertEquals('aya_ueto', $obj->getResource('db.user'));

        // new value
        $obj->setResource('hoge.fuga.piyo', 'hogera');
        $this->assertEquals('hogera', $obj->getResource('hoge.fuga.piyo'));
        $this->assertSame(['piyo' => 'hogera'], $obj->getResource('hoge.fuga'));
        $this->assertSame(['fuga' => ['piyo' => 'hogera']], $obj->getResource('hoge'));

        // non array new value
        $obj->setResource('non_array.value', 100);
        $this->assertEquals(100, $obj->getResource('non_array.value'));

        // clear
        $obj->clearResource();
        $this->assertSame([], $obj->getResourceData());
        $this->assertEquals(null, $obj->getResource('config1'));
    }

    public function testSafetyForInvalidUsecase()
    {
        // the object has non array $_array_resource as default
        $obj = new ConcreteResource3();

        $obj->setResource('non_array.value', 100);
        $this->assertEquals(100, $obj->getResource('non_array.value'));
    }
}

class ConcreteResource2
{
    use ArrayResourceTrait;

    public function __construct(array $array = [])
    {
        $this->_array_resource = $array;
    }
}

class ConcreteResource3
{
    use ArrayResourceTrait;

    public function __construct()
    {
        $this->_array_resource = null;
    }
}
