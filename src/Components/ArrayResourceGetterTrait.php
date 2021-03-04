<?php
/**
 *
 */

namespace Dietcube\Components;

trait ArrayResourceGetterTrait
{
    protected $_array_resource = [];

    /**
     * @param null $key
     * @param null $default
     * @return array|mixed|null
     */
    public function getResource($key = null, $default = null)
    {
        if ($key === null) {
            return $this->getResourceData();
        }

        $key_parts = explode('.', $key);
        $value = $this->_array_resource;
        foreach ($key_parts as $key_part) {
            if (!is_array($value)) {
                return $default;
            }

            if (!array_key_exists($key_part, $value)) {
                return $default;
            }
            $value = $value[$key_part];
        }
        return $value;
    }

    public function getResourceData(): array
    {
        return $this->_array_resource;
    }

    public function clearResource(): void
    {
        $this->_array_resource = [];
    }
}
