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

class ProductVariantManager implements ProductVariantManagerInterface
{
    /**
     * @var ProductManagerInterface
     */
    private $productManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param ProductManagerInterface $productManager
     * @param ProductRepositoryInterface $productRepository
     */
    public function __construct(
        ProductManagerInterface $productManager,
        ProductRepositoryInterface $productRepository
    ) {
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function createVariant($parentId, array $variantData, $locale, $userId)
    {
        // Check if parent product exists.
        $parent = $this->productRepository->findById($parentId);
        if (!$parent) {
            throw new ProductNotFoundException($parentId);
        }

        // Create variant product by setting variant data.
        $variant = $this->productRepository->createNew();

        // Set parent.
        $variant->setParent($parent);
//
//        $this->em->flush();
//
//        return $this->productFactory->createApiEntity($variant, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function deleteVariant($variantId)
    {
        $variant = $this->productRepository->findById($variantId);

        // TODO: Check type of variant.
        if (!$variant) {
            throw new ProductNotFoundException($variantId);
        }

        $variant->setParent(null);

        $this->em->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function updateVariant($variantId, array $variantData, $locale, $userId)
    {
        // TODO: Implement updateVariant() method.
    }

    /**
     * @param ProductInterface $variant
     * @param array $variantData
     */
    private function mapDataToVariant(ProductInterface $variant, array $variantData)
    {

    }
}
