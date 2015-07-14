<?php
/**
 *
 */

namespace Dietcube;

use Dietcube\Components\ArrayResourceGetterTrait;

class Parameters
{
    use ArrayResourceGetterTrait {
        getResource as public get;
        getResourceData as public getData;
    }

    public function __construct(array $params = [])
    {
        $this->_array_resource = $params;
    }
}
