<?php

namespace Sulu\Bundle\ProductBundle\Product;

use Sulu\Bundle\ProductBundle\Api\Addon;

class ProductAddonManager implements ProductAddonManagerInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductFactoryInterface
     */
    protected $productFactory;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactoryInterface $productFactory
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory
    ) {
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function findAddonsByProductIdAndLocale($id, $locale)
    {
        $product = $this->productRepository->findByIdAndLocale($id, $locale);

        $apiAddons = [];
        foreach ($product->getAddons() as $addon) {
            $apiAddons[] = new Addon($addon, $this->productFactory, $locale);
        }

        return $apiAddons;
    }
}
