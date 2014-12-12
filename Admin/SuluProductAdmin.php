<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Admin;

use Sulu\Bundle\AdminBundle\Admin\Admin;
use Sulu\Bundle\AdminBundle\Navigation\Navigation;
use Sulu\Bundle\AdminBundle\Navigation\NavigationItem;
use Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface;

class SuluProductAdmin extends Admin
{
    /**
     * @var SecurityCheckerInterface
     */
    private $securityChecker;

    public function __construct(SecurityCheckerInterface $securityChecker, $title)
    {
        $this->securityChecker = $securityChecker;
        $rootNavigationItem = new NavigationItem($title);
        $section = new NavigationItem('');

        $pim = new NavigationItem('navigation.pim');
        $pim->setIcon('asterisk');

        if ($this->securityChecker->hasPermission('sulu.product.products', 'view')) {
            $products = new NavigationItem('navigation.pim.products', $pim);
            $products->setAction('pim/products');
        }

        if ($this->securityChecker->hasPermission('sulu.product.attributes', 'view')) {
            $attributes = new NavigationItem('navigation.pim.attributes', $pim);
            $attributes->setAction('pim/attributes');
        }

        if ($pim->hasChildren()) {
            $section->addChild($pim);
            $rootNavigationItem->addChild($section);
        }

        $this->setNavigation(new Navigation($rootNavigationItem));
    }

    /**
     * {@inheritdoc}
     */
    public function getCommands()
    {
        return array();
    }

    /**
     * {@inheritdoc}
     */
    public function getJsBundleName()
    {
        return 'suluproduct';
    }

    public function getSecurityContexts()
    {
        return array(
            'Sulu' => array(
                'Product' => array(
                    'sulu.product.attributes',
                    'sulu.product.products',
                )
            )
        );
    }
}
