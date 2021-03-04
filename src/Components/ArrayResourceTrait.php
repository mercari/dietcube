<?php

namespace Dietcube\Components;

trait ArrayResourceTrait
{
    use ArrayResourceGetterTrait;

    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function setResource($key, $value): void
    {
        $key_parts = explode('.', $key);
        $ref_value = &$this->_array_resource;
        foreach ($key_parts as $key_part) {
            if (!is_array($ref_value)) {
                $ref_value = [];
            }
            if (!array_key_exists($key_part, $ref_value)) {
                $ref_value[$key_part] = [];
            }
            $ref_value = &$ref_value[$key_part];
        }
        $ref_value = $value;
    }
}
