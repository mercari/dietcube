<?php
/**
 *
 */

namespace Dietcube\Components;

trait ArrayResourceGetterTrait
{
    protected $_array_resource = [];

    public function getResource($key = null, $default = null)
    {
        if ($key === null) {
            return $this->getResourceData();
        }

        $key_parts = explode('.', $key);
        $value = $this->_array_resource;
        foreach ($key_parts as $key) {
            if (!is_array($value)) {
                return $default;
            } elseif (!array_key_exists($key, $value)) {
                return $default;
            }
            $value = $value[$key];
        }
        return $value;
    }

    public function getResourceData()
    {
        return $this->_array_resource;
    }

    public function clearResource()
    {
        $this->_array_resource = [];
    }
}
