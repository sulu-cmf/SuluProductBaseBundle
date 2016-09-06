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

use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;

class ProductVariantManager
{

    /**
     * @param ProductManagerInterface $productManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductManagerInterface $productManager,
        ProductRepositoryInterface $productRepository
    )
    {
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function addVariant($parentId, $variantId, $locale)
    {
        $variant = $this->productRepository->findById($variantId);

        if (!$variant) {
            throw new ProductNotFoundException($variantId);
        }

        $parent = $this->productRepository->findById($parentId);

        if (!$parent) {
            throw new ProductNotFoundException($parentId);
        }

        $variant->setParent($parent);

        $this->em->flush();

        return $this->productFactory->createApiEntity($variant, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function removeVariant($parentId, $variantId)
    {
        $variant = $this->productRepository->findById($variantId);

        if (!$variant || $variant->getParent()->getId() != $parentId) {
            throw new ProductNotFoundException($variantId);
        }

        $variant->setParent(null);

        $this->em->flush();
    }
}
