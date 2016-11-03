<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Api;

use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ProductBundle\Entity\AddonPrice as AddonPriceEntity;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;

/**
 * @ExclusionPolicy("all")
 */
class AddonProduct extends Product implements ApiAddonProductInterface
{
    /**
     * @var ProductInterface
     */
    protected $entity;

    /**
     * @var AddonPriceEntity[]
     */
    private $addonPrices;

    /**
     * {@inheritdoc}
     */
    public function setAddonPrices(array $addonPrices)
    {
        $this->addonPrices = $addonPrices;

        return $this;
    }

    /**
     * @VirtualProperty
     * @SerializedName("addonPrices")
     *
     * {@inheritdoc}
     */
    public function getAddonPrices()
    {
        $addonPrices = [];
        if ($this->addonPrices) {
            foreach ($this->addonPrices as $addonPrice) {
                $addonPrices[] = new AddonPrice($addonPrice, $this->locale, $this->priceFormatter);
            }
        }

        return $addonPrices;
    }
}
