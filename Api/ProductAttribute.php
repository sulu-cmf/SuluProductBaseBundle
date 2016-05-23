<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Api;

use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;
use Sulu\Bundle\ProductBundle\Entity\ProductAttribute as ProductAttributeEntity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * @ExclusionPolicy("all")
 */
class ProductAttribute extends ApiWrapper
{
    /**
     * @param ProductAttributeEntity $entity
     * @param string $locale
     */
    public function __construct(ProductAttributeEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * @VirtualProperty
     * @SerializedName("id")
     *
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * @VirtualProperty
     * @SerializedName("value")
     *
     * @return AttributeValue
     */
    public function getAttributeValue()
    {
        return new AttributeValue($this->entity->getAttributeValue(), $this->locale);
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return new Attribute($this->entity->getAttribute(), $this->locale);
    }

    /**
     * @VirtualProperty
     * @SerializedName("attributeName")
     *
     * @return sting
     */
    public function getAttributeName()
    {
        return $this->getAttribute()->getName();
    }

    /**
     * @return AttributeType
     */
    public function getAttributeType()
    {
        return $this->getAttribute()->getType();
    }

    /**
     * @VirtualProperty
     * @SerializedName("attributeTypeName")
     *
     * @return string
     */
    public function getAttributeTypeName()
    {
        return $this->getAttributeType()->getName();
    }

    /**
     * @VirtualProperty
     * @SerializedName("attributeId")
     *
     * @return int
     */
    public function getAttributeId()
    {
        return $this->getAttribute()->getId();
    }
}
