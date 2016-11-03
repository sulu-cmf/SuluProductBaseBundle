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

use Sulu\Bundle\ProductBundle\Entity\AddonPrice as AddonPriceEntity;

/**
 * Interface for Addon-Product Api Objects.
 */
interface ApiAddonProductInterface
{
    /**
     * @param AddonPriceEntity[] $addonPrices
     *
     * @return $this
     */
    public function setAddonPrices(array $addonPrices);

    /**
     * @return AddonPrice[]
     */
    public function getAddonPrices();
}
