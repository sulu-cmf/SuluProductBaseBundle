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

use Sulu\Component\Rest\ApiWrapper;
use Sulu\Bundle\ProductBundle\Entity\SpecialPrice as SpecialPriceEntity;
use JMS\Serializer\Annotation\VirtualProperty;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\ExclusionPolicy;

/**
 * The Status class which will be exported to the API
 *
 * @package Sulu\Bundle\ProductBundle\Api
 * @ExclusionPolicy("all")
 */
class SpecialPrice extends ApiWrapper
{
    /**
     * @param SpecialPriceEntity $entity
     * @param string $locale
     */
    public function __construct(SpecialPriceEntity $entity, $locale)
    {
        $this->entity = $entity;
        $this->locale = $locale;
    }

    /**
     * Returns the id of the Special price
     *
     * @VirtualProperty
     * @SerializedName("id")
     * @return int
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * Get price
     *
     * @VirtualProperty
     * @SerializedName("price")
     * @return float
     */
    public function getPrice()
    {
        return $this->entity->getPrice();
    }

    /**
     * Get start date
     * @VirtualProperty
     * @SerializedName("start")
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->entity->getStart();
    }

    /**
     * Get end date
     *
     * @VirtualProperty
     * @SerializedName("End")
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->entity->getEnd();
    }

    /**
     * Get currency
     *
     * @VirtualProperty
     * @SerializedName("Currency")
     * @return \Sulu\Bundle\ProductBundle\Entity\Currency
     */
    public function getCurrency()
    {
        return $this->entity->getCurrency();
    }

    /**
     * Get product
     *
     * @return \Sulu\Bundle\ProductBundle\Entity\ProductInterface
     */
    public function getProduct()
    {
        return $this->entity->getProduct();
    }

}