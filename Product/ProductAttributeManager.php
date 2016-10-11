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

use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ProductBundle\Entity\Attribute;
use Sulu\Bundle\ProductBundle\Entity\AttributeValue;
use Sulu\Bundle\ProductBundle\Entity\AttributeValueTranslation;
use Sulu\Bundle\ProductBundle\Entity\ProductAttribute;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

/**
 * Manager responsible for handling product attributes.
 */
class ProductAttributeManager
{
    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @param ObjectManager $entityManager
     */
    public function __construct(
        ObjectManager $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * Creates a new ProductAttribute relation.
     *
     * @param Attribute $attribute
     * @param ProductInterface $product
     * @param AttributeValue $attributeValue
     *
     * @return ProductAttribute
     */
    public function createProductAttribute(
        Attribute $attribute,
        ProductInterface $product,
        AttributeValue $attributeValue
    ) {
        $productAttribute = new ProductAttribute();
        $productAttribute->setAttribute($attribute);
        $productAttribute->setProduct($product);
        $productAttribute->setAttributeValue($attributeValue);
        $this->entityManager->persist($productAttribute);

        $product->addProductAttribute($productAttribute);

        return $productAttribute;
    }

    /**
     * Creates a new attribute value and its translation in the specified locale.
     *
     * @param Attribute $attribute
     * @param string $value
     * @param string $locale
     *
     * @return AttributeValue
     */
    public function createAttributeValue(Attribute $attribute, $value, $locale)
    {
        $attributeValue = new AttributeValue();
        $attributeValue->setAttribute($attribute);
        $this->entityManager->persist($attributeValue);
        $attribute->addValue($attributeValue);
        $this->setOrCreateAttributeValueTranslation($attributeValue, $value, $locale);

        return $attributeValue;
    }

    /**
     * Checks if AttributeValue already contains a translation in given locale or creates a new one.
     *
     * @param AttributeValue $attributeValue
     * @param string $value
     * @param string $locale
     *
     * @return AttributeValueTranslation
     */
    public function setOrCreateAttributeValueTranslation(AttributeValue $attributeValue, $value, $locale)
    {
        // Check if translation already exists for given locale.
        $attributeValueTranslation = null;
        /** @var AttributeValueTranslation $translation */
        foreach ($attributeValue->getTranslations() as $translation) {
            if ($translation->getLocale() === $locale) {
                $attributeValueTranslation = $translation;
            }
        }
        if (!$attributeValueTranslation) {
            // Create a new attribute value translation.
            $attributeValueTranslation = new AttributeValueTranslation();
            $this->entityManager->persist($attributeValueTranslation);
            $attributeValueTranslation->setLocale($locale);
            $attributeValueTranslation->setAttributeValue($attributeValue);
            $attributeValue->addTranslation($attributeValueTranslation);
        }
        $attributeValueTranslation->setName($value);

        return $attributeValueTranslation;
    }

    /**
     * Removes attribute value translation in given locale from given attribute.
     *
     * @param AttributeValue $attributeValue
     * @param string $locale
     */
    public function removeAttributeValueTranslation(AttributeValue $attributeValue, $locale)
    {
        // Check if translation already exists for given locale.
        /** @var AttributeValueTranslation $attributeValueTranslation */
        foreach ($attributeValue->getTranslations() as $attributeValueTranslation) {
            if ($attributeValueTranslation->getLocale() === $locale) {
                $attributeValue->removeTranslation($attributeValueTranslation);
                $this->entityManager->remove($attributeValueTranslation);
            }
        }
    }

    /**
     * Removes all attribute value translations from given attribute.
     *
     * @param AttributeValue $attributeValue
     */
    public function removeAllAttributeValueTranslations(AttributeValue $attributeValue)
    {
        // Check if translation already exists for given locale.
        /** @var AttributeValueTranslation $attributeValueTranslation */
        foreach ($attributeValue->getTranslations() as $attributeValueTranslation) {
            $attributeValue->removeTranslation($attributeValueTranslation);
            $this->entityManager->remove($attributeValueTranslation);
        }
    }
}
