<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Admin;

use Sulu\Bundle\AdminBundle\Navigation\ContentNavigation;
use Sulu\Bundle\AdminBundle\Navigation\ContentNavigationItem;

class SuluProductContentNavigation extends ContentNavigation
{
    public function __construct()
    {
        parent::__construct();

        $details = new ContentNavigationItem('content-navigation.product.general');
        $details->setAction('details');
        $details->setGroups(array('product', 'product-with-variants', 'product-addon', 'product-set'));
        $details->setComponent('products/components/detail-form@suluproduct');
        $this->addNavigationItem($details);

        $variants = new ContentNavigationItem('content-navigation.product.variants');
        $variants->setAction('variants');
        $variants->setGroups(array('product-with-variants'));
        $variants->setComponent('products/components/variants-list@suluproduct');
        $variants->setDisplay(array('edit'));
        $this->addNavigationItem($variants);

        $pricing = new ContentNavigationItem('content-navigation.product.pricing');
        $pricing->setAction('pricing');
        $pricing->setGroups(array('product', 'product-set'));
        $pricing->setComponent('products/components/pricing@suluproduct');
        $pricing->setDisplay(array('edit'));
        $this->addNavigationItem($pricing);
    }
} 