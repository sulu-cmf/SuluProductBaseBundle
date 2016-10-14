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

use Sulu\Bundle\ProductBundle\Entity\Attribute;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Product\Exception\AttributeNotFoundException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Traits\UtilitiesTrait;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

/**
 * Manager responsible for handling product attributes.
 */
class ProductVariantAttributeManager
{
    use UtilitiesTrait;

    private static $attributeEntityName = 'SuluProductBundle:Attribute';
    private static $attributeTranslationEntityName = 'SuluProductBundle:AttributeTranslation';
    private static $productEntityName = 'SuluProductBundle:Product';

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param AttributeRepositoryInterface $attributeRepository
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * Returns all field-descriptors for variant attributes.
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function retrieveFieldDescriptors($locale)
    {
        $fieldDescriptors = [];

        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            self::$attributeEntityName,
            null,
            [
                self::$attributeEntityName => new DoctrineJoinDescriptor(
                    self::$attributeEntityName,
                    static::$productEntityName . '.variantAttributes'
                ),
            ],
            true,
            false,
            'integer'
        );

        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$attributeTranslationEntityName,
            null,
            [
                self::$attributeEntityName => new DoctrineJoinDescriptor(
                    self::$attributeEntityName,
                    static::$productEntityName . '.variantAttributes'
                ),
                self::$attributeTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$attributeTranslationEntityName,
                    static::$attributeEntityName . '.translations',
                    self::$attributeTranslationEntityName . '.locale = \'' . $locale . '\''
                ),
            ],
            false,
            true,
            'string'
        );

        return $fieldDescriptors;
    }

    /**
     * Creates a new relation between variant and attribute.
     *
     * @param int $productId
     * @param array $requestData
     *
     * @throws AttributeNotFoundException
     * @throws ProductNotFoundException
     *
     * @return ProductInterface
     */
    public function createVariantAttributeRelation($productId, array $requestData)
    {
        $variant = $this->retrieveProductById($productId);
        $attribute = $this->retrieveAttributeById($this->getProperty($requestData, 'attributeId'));

        $variant->addVariantAttribute($attribute);

        return $variant;
    }

    /**
     * Removes relation between variant and attribute.
     *
     * @param int $productId
     * @param int $attributeId
     *
     * @throws ProductException
     *
     * @return ProductInterface
     */
    public function removeVariantAttributeRelation($productId, $attributeId)
    {
        $variant = $this->retrieveProductById($productId);
        $attribute = $this->retrieveAttributeById($attributeId);

        if (!$variant->getVariantAttributes()->contains($attribute)) {
            throw new ProductException('Variant does not have relation to attribute');
        }

        $variant->removeVariantAttribute($attribute);

        return $variant;
    }

    /**
     * Fetches attribute from db. If not found an exception is thrown.
     *
     * @param int $attributeId
     *
     * @throws AttributeNotFoundException
     *
     * @return Attribute
     */
    private function retrieveAttributeById($attributeId)
    {
        $attribute = $this->attributeRepository->find($attributeId);
        if (!$attribute) {
            throw new AttributeNotFoundException($attributeId);
        }

        return $attribute;
    }

    /**
     * Fetches product from db. If not found an exception is thrown.
     *
     * @param int $productId
     *
     * @throws ProductNotFoundException
     *
     * @return ProductInterface
     */
    private function retrieveProductById($productId)
    {
        // Fetch product.
        $product = $this->productRepository->find($productId);
        if (!$product) {
            throw new ProductNotFoundException($productId);
        }

        return $product;
    }
}
