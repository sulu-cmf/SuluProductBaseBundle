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

use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Traits\UtilitiesTrait;

class ProductVariantManager implements ProductVariantManagerInterface
{
    use UtilitiesTrait;

    /**
     * @var ProductManagerInterface
     */
    private $productManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactoryInterface
     */
    private $productFactory;

    /**
     * @var ProductAttributeManager
     */
    private $productAttributeManager;

    /**
     * @param ProductManagerInterface $productManager
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactoryInterface $productFactory
     * @param ProductAttributeManager $productAttributeManager
     */
    public function __construct(
        ProductManagerInterface $productManager,
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory,
        ProductAttributeManager $productAttributeManager
    ) {
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->productAttributeManager = $productAttributeManager;
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
        /** @var ProductInterface $variant */
        $variant = $this->productFactory->createEntity();

        // Set parent.
        $variant->setParent($parent);

        // Set data to variant.
        $this->mapDataToVariant($variant, $variantData, $locale);

        return $variant;
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
    }

    /**
     * {@inheritdoc}
     */
    public function updateVariant($variantId, array $variantData, $locale, $userId)
    {
        // TODO: Implement updateVariant() method.
    }

    /**
     * TODO: MOVE TO MAPPER
     *
     * @param ProductInterface $variant
     * @param array $variantData
     * @param string $locale
     */
    private function mapDataToVariant(ProductInterface $variant, array $variantData, $locale)
    {
        $productTranslation = $this->productManager->retrieveOrCreateProductTranslationByLocale($variant, $locale);
        $productTranslation->setName($this->getProperty($variantData, 'name'));

        $variant->setNumber($this->getProperty($variantData, 'name'));

        // TODO: process attributes
        $this->processAttributes($variant, $variantData, $locale);
        // TODO: process prices
    }

    /**
     * Adds variant attributes to variant.
     *
     * @param ProductInterface $variant
     * @param array $variantData
     * @param string $locale
     *
     * @throws \Exception
     */
    private function processAttributes(ProductInterface $variant, array $variantData, $locale)
    {
        $parent = $variant->getParent();

        // Number of attributes in variantData and parents variant-attributes need to match and must not be 0.
        $sizeOfVariantAttributes = sizeof($this->getProperty($variantData, 'attributes'));
        $sizeOfParentAttributes = $parent->getVariantAttributes()->count();
        if (!$sizeOfVariantAttributes
            || !$sizeOfParentAttributes
            || $sizeOfVariantAttributes != $sizeOfParentAttributes
        ) {
            throw new \Exception('TODO: CREATE A CUSTOM EXCEPTION');
        }

        $matchCount = 0;
        foreach ($parent->getVariantAttributes() as $variantAttribute) {
            foreach ($variantData['attributes'] as $attributes) {
                //TODO: Define schema with attributeId and AttributeValueName
                if ($variantAttribute->getId() === $attributes['attributeId']) {
                    $attributeValue = $this->productAttributeManager->createAttributeValue();
                    $this->productAttributeManager->createProductAttribute($variantAttribute, $variant );
                    $matchCount++;
                }
            }
        }

        // Not all necessary variant attributes were defined in data array!
        if ($matchCount !== $sizeOfParentAttributes) {
            throw new \Exception('TODO: CREATE A CUSTOM EXCEPTION');
        }
    }
}
