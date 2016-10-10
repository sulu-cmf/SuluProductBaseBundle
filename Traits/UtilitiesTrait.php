<?php

namespace Sulu\Bundle\ProductBundle\Traits;

/**
 * This Trait provides basic convenience helper functions.
 */
trait UtilitiesTrait
{
    /**
     * Returns the value for a given key or if not existent
     * the given default value.
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty(array $data, $key, $default = null)
    {
        if (array_key_exists($key, $data)) {
            return $data[$key];
        }

        return $default;
    }
}