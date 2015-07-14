<?php
namespace Dietcube;

use Pimple\Container;

interface RouteInterface
{
    /**
    * @return array
    */
    public function definition(Container $container);
}
