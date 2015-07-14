<?php
/**
 *
 */

namespace Dietcube\Components;

trait ArrayResourceTrait
{
    use ArrayResourceGetterTrait;

    /**
     * @param
     * @param
     */
    public function setResource($key, $value)
    {
        $key_parts = explode('.', $key);
        $ref_value = &$this->_array_resource;
        foreach ($key_parts as $key) {
            if (!is_array($ref_value)) {
                $ref_value = [];
            }
            if (!array_key_exists($key, $ref_value)) {
                $ref_value[$key] = [];
            }
            $ref_value = &$ref_value[$key];
        }
        $ref_value = $value;
    }
}
