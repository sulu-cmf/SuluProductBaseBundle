<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Mapper;

use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

class ProductVariantMapper
{
    /**
     * Maps array of data to a product variant.
     *
     * @param ProductInterface $product
     * @param array $data
     */
    public function bind(ProductInterface $product, array $data)
    {
        $product->setNumber($this->getProperty($data, 'number'));
    }

    /**
     * Checks if array at given key is defined. Otherwise returns a default.
     *
     * @param array $data
     * @param string $key
     * @param mixed $default
     *
     * @return mixed
     */
    public function getProperty(array $data, $key, $default = null)
    {
        if (isset($data[$key])) {
            return $data[$key];
        }

        return $default;
    }
}
