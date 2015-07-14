<?php
/**
 *
 */

namespace Dietcube;

use Dietcube\Components\ArrayResourceGetterTrait;

class Config
{
    use ArrayResourceGetterTrait {
        getResource as public get;
        getResourceData as public getData;
    }

    public function __construct(array $config = [])
    {
        $this->_array_resource = $config;
    }
}
