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
    )
    {
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
    }

    /**
     * {@inheritDoc}
     */
    public function createVariant($parentId, $variantData, $locale, $userId)
    {
//        $variant = $this->productRepository->findById($variantId);

//        if (!$variant) {
//            throw new ProductNotFoundException($variantId);
//        }
//
//        $variant = $this->productManager->save($variantData, $locale, $userId);
//
        $parent = $this->productRepository->findById($parentId);

        if (!$parent) {
            throw new ProductNotFoundException($parentId);
        }

//        $variant->setParent($parent);
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
    public function updateVariant($variantId, $variantData, $locale)
    {
        // TODO: Implement updateVariant() method.
    }
}
