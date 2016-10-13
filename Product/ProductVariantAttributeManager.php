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
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;

/**
 * Manager responsible for handling product attributes.
 */
class ProductVariantAttributeManager
{
    private static $attributeEntityName = 'SuluProductBundle:Attribute';
    private static $attributeTranslationEntityName = 'SuluProductBundle:AttributeTranslation';
    private static $productEntityName = 'SuluProductBundle:Product';
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
     * Returns all fielddescriptors for variant attributes.
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
}
