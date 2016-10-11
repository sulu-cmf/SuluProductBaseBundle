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

use JMS\Serializer\Annotation\Groups;
use Sulu\Bundle\ProductBundle\Entity\Addon;
use Sulu\Bundle\ProductBundle\Entity\AddonPrice;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductPrice;
use Sulu\Bundle\ProductBundle\Entity\SpecialPrice;
use Sulu\Bundle\ProductBundle\Util\PriceFormatter;

class ProductPriceManager implements ProductPriceManagerInterface
{
    /**
     * @var string
     */
    protected $defaultCurrency;

    /**
     * @var PriceFormatter
     */
    protected $priceFormatter;

    /**
     * @var CurrencyManager
     */
    private $currencyManager;

    /**
     * @param string $defaultCurrency
     * @param PriceFormatter $priceFormatter
     * @param CurrencyManager $currencyManager
     */
    public function __construct(
        $defaultCurrency,
        PriceFormatter $priceFormatter,
        CurrencyManager $currencyManager
    ) {
        $this->defaultCurrency = $defaultCurrency;
        $this->priceFormatter = $priceFormatter;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @param ProductInterface $product
     * @param float $priceValue
     * @param float $minimumQuantity
     * @param string|null $currencyCode
     *
     * @return ProductPrice
     */
    public function createNewProductPriceForCurrency(
        ProductInterface $product,
        $priceValue,
        $minimumQuantity = 0.0,
        $currencyCode = null
    ) {
        // Fallback currency.
        if (!$currencyCode) {
            $currencyCode = $this->defaultCurrency;
        }

        $productPrice = new ProductPrice();
        $productPrice->setMinimumQuantity($minimumQuantity);
        $productPrice->setPrice($priceValue);
        $productPrice->setProduct($product);
        $productPrice->setCurrency($this->currencyManager->findByCode($currencyCode));

        return $productPrice;
    }

    /**
     * Returns the bulk price for a certain quantity of the product by a given currency.
     *
     * @param ProductInterface $product
     * @param float $quantity
     * @param null|string $currency
     *
     * @return null|ProductPrice
     */
    public function getBulkPriceForCurrency(ProductInterface $product, $quantity, $currency = null)
    {
        $currency = $currency ?: $this->defaultCurrency;

        $bulkPrice = null;
        if ($prices = $product->getPrices()) {
            $bestDifference = PHP_INT_MAX;
            foreach ($prices as $price) {
                if ($price->getCurrency()->getCode() == $currency &&
                    $price->getMinimumQuantity() <= $quantity &&
                    ($quantity - $price->getMinimumQuantity()) < $bestDifference
                ) {
                    $bestDifference = $quantity - $price->getMinimumQuantity();
                    $bulkPrice = $price;
                }
            }
        }

        return $bulkPrice;
    }

    /**
     * Returns the base prices for the product by a given currency.
     *
     * @param ProductInterface $product
     * @param null|string $currency
     *
     * @return null|ProductPrice
     */
    public function getBasePriceForCurrency(ProductInterface $product, $currency = null)
    {
        $currency = $currency ?: $this->defaultCurrency;
        if ($prices = $product->getPrices()) {
            foreach ($prices as $price) {
                if ($price->getCurrency()->getCode() == $currency && $price->getMinimumQuantity() == 0) {
                    return $price;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSpecialPriceForCurrency(ProductInterface $product, $currency = null)
    {
        $currency = $currency ?: $this->defaultCurrency;
        $specialPrices = $product->getSpecialPrices();

        // Check if any special prices are set.
        if (!$specialPrices) {
            return null;
        }

        foreach ($specialPrices as $specialPriceEntity) {
            // Find special price with matching currency.
            if ($specialPriceEntity->getCurrency()->getCode() == $currency) {
                // Check if special price is still valid.
                if ($this->isValidSpecialPrice($specialPriceEntity)) {
                    return $specialPriceEntity;
                }

                break;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getAddonPriceForCurrency(Addon $addon, $currency = null)
    {
        $currency = $currency ?: $this->defaultCurrency;

        $addonPrices = $addon->getAddonPrices();

        // Check if any addon prices are set.
        if (!$addonPrices) {
            return null;
        }

        /** @var AddonPrice $addonPrice */
        foreach ($addonPrices as $addonPrice) {
            if ($addonPrice->getCurrency()->getCode() === $currency) {
                return $addonPrice;
            }
        }

        return null;
    }

    /**
     * Helper function to get a formatted price for a given currency and locale.
     *
     * @param int $price
     * @param string $symbol
     * @param string $locale
     *
     * @Groups({"cart"})
     *
     * @return string
     */
    public function getFormattedPrice($price, $symbol = 'EUR', $locale = null)
    {
        $location = PriceFormatter::CURRENCY_LOCATION_NONE;
        if (!empty($symbol)) {
            $location = PriceFormatter::CURRENCY_LOCATION_SUFFIX;
        }

        return $this->priceFormatter->format(
            (float)$price,
            null,
            $locale,
            $symbol,
            $location
        );
    }

    /**
     * Checks if a special price is still valid by today.
     *
     * @param SpecialPrice $specialPrice
     *
     * @return bool
     */
    private function isValidSpecialPrice(SpecialPrice $specialPrice)
    {
        $startDate = $specialPrice->getStartDate();
        $endDate = $specialPrice->getEndDate();
        $now = new \DateTime();

        // Check if special price is stil valid.
        if (($now >= $startDate && $now <= $endDate) ||
            ($now >= $startDate && empty($endDate)) ||
            (empty($startDate) && empty($endDate))
        ) {
            return true;
        }

        return false;
    }
}
