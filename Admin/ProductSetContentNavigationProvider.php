<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationProviderInterface;

class ProductSetContentNavigationProvider implements ContentNavigationProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getNavigationItems(array $options = [])
    {
        $details = new ContentNavigationItem('content-navigation.product.general');
        $details->setId('details');
        $details->setAction('details');
        $details->setPosition(10);
        $details->setComponent('products/components/edit/detail-form@suluproduct');
        $details->setResetStore(false);

        $pricing = new ContentNavigationItem('content-navigation.product.pricing');
        $pricing->setId('pricing');
        $pricing->setAction('pricing');
        $pricing->setPosition(20);
        $pricing->setComponent('products/components/edit/pricing@suluproduct');
        $pricing->setDisplay(['edit']);
        $pricing->setResetStore(false);

        $attributes = new ContentNavigationItem('content-navigation.product.attributes');
        $attributes->setAction('attributes');
        $attributes->setAction(30);
        $attributes->setComponent('products/components/edit/attributes@suluproduct');
        $attributes->setDisplay(['edit']);
        $attributes->setResetStore(false);

        return [
            $details,
            $pricing,
            $attributes,
        ];
    }
}
