<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Product;

use Sulu\Bundle\ProductBundle\Api\Product;

interface ProductVariantManagerInterface
{
    /**
     * Adds a variant to a specific product.
     *
     * @param int $parentId The id of the product, to which the variant is added
     * @param int $variantId The id of the product, which is added to the other as a variant
     * @param string $locale The locale to load
     *
     * @return Product The new variant
     */
    public function addVariant($parentId, $variantId, $locale);

    /**
     * Removes a variant from a specific product.
     *
     * @param int $parentId The id of the product, from which the variant is removed
     * @param int $variantId The id of the product, which is removed from the other
     */
    public function removeVariant($parentId, $variantId);
}
