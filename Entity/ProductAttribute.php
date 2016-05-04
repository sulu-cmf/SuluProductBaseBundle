<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Entity;

class ProductAttribute
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var BaseProduct
     */
    private $product;

    /**
     * @var Attribute
     */
    private $attribute;

    /**
     * @var AttributeValue
     */
    private $attributeValue;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param ProductInterface $product
     *
     * @return self
     */
    public function setProduct(ProductInterface $product)
    {
        $this->product = $product;
    
        return $this;
    }

    /**
     * @return ProductInterface
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * @param Attribute $attribute
     *
     * @return self
     */
    public function setAttribute(Attribute $attribute)
    {
        $this->attribute = $attribute;
    
        return $this;
    }

    /**
     * @return Attribute
     */
    public function getAttribute()
    {
        return $this->attribute;
    }

    /**
     * @param AttributeValue $attributeValue
     *
     * @return self
     */
    public function setAttributeValue(AttributeValue $attributeValue)
    {
        $this->attributeValue = $attributeValue;

        return $this;
    }

    /**
     * @return AttributeValue
     */
    public function getAttributeValue()
    {
        return $this->getAttributeValue();
    }
}
