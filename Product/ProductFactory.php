<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Product;

use Sulu\Bundle\ContactBundle\Contact\AccountManager;
use Sulu\Bundle\ProductBundle\Entity\Product;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Api\Product as ApiProduct;
use Sulu\Bundle\Sales\CoreBundle\Pricing\PriceFormatter;

class ProductFactory implements ProductFactoryInterface
{
    /**
     * @var AccountManager
     */
    protected $accountManager;

    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @param AccountManager $accountManager
     */
    public function __construct(AccountManager $accountManager = null, PriceFormatter $priceFormatter)
    {
        $this->accountManager = $accountManager;
        $this->priceFormatter = $priceFormatter;
    }

    /**
     * {@inheritdoc}
     */
    public function createEntity()
    {
        return new Product();
    }

    /**
     * {@inheritdoc}
     */
    public function createApiEntity(ProductInterface $product, $locale)
    {
        return new ApiProduct($product, $locale, $this->priceFormatter, $this->accountManager);
    }
}
