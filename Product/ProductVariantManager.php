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

use Doctrine\ORM\EntityManagerInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductPrice;
use Sulu\Bundle\ProductBundle\Entity\Type;
use Sulu\Bundle\ProductBundle\Entity\TypeRepository;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Traits\UtilitiesTrait;

class ProductVariantManager implements ProductVariantManagerInterface
{
    use UtilitiesTrait;

    /**
     * @var ProductManagerInterface
     */
    private $productManager;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductFactoryInterface
     */
    private $productFactory;

    /**
     * @var ProductAttributeManager
     */
    private $productAttributeManager;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var TypeRepository
     */
    private $productTypeRepository;

    /**
     * @var array
     */
    private $productTypesMap;

    /**
     * @var ProductPriceManager
     */
    private $productPriceManager;

    /**
     * @param EntityManagerInterface $entityManager
     * @param ProductManagerInterface $productManager
     * @param ProductRepositoryInterface $productRepository
     * @param ProductFactoryInterface $productFactory
     * @param ProductAttributeManager $productAttributeManager
     * @param TypeRepository $typeRepository
     * @param ProductPriceManager $productPriceManager
     * @param array $productTypesMap
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        ProductManagerInterface $productManager,
        ProductRepositoryInterface $productRepository,
        ProductFactoryInterface $productFactory,
        ProductAttributeManager $productAttributeManager,
        TypeRepository $typeRepository,
        ProductPriceManager $productPriceManager,
        array $productTypesMap
    ) {
        $this->entityManager = $entityManager;
        $this->productManager = $productManager;
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->productAttributeManager = $productAttributeManager;
        $this->productTypeRepository = $typeRepository;
        $this->productTypesMap = $productTypesMap;
        $this->productPriceManager = $productPriceManager;
    }

    /**
     * {@inheritDoc}
     */
    public function createVariant($parentId, array $variantData, $locale, $userId)
    {
        // Check if parent product exists.
        $parent = $this->productRepository->findById($parentId);
        if (!$parent) {
            throw new ProductNotFoundException($parentId);
        }

        // Create variant product by setting variant data.
        /** @var ProductInterface $variant */
        $variant = $this->productFactory->createEntity();
        $this->entityManager->persist($variant);

        // Set parent.
        $variant->setParent($parent);
        $variant->setType($this->getTypeVariant());
        $variant->setStatus($parent->getStatus());

        // Set data to variant.
        $this->mapDataToVariant($variant, $variantData, $locale);

        return $variant;
    }

    /**
     * {@inheritDoc}
     */
    public function deleteVariant($variantId)
    {
        $variant = $this->productRepository->findById($variantId);

        if (!$variant) {
            throw new ProductNotFoundException($variantId);
        }

        if ($variant->getType()->getId() !== $this->productTypesMap['PRODUCT_VARIANT']) {
            throw new ProductException('Product is no variant and therefore cannot be deleted');
        }

        $variant->setParent(null);
    }

    /**
     * {@inheritdoc}
     */
    public function updateVariant($variantId, array $variantData, $locale, $userId)
    {
        // TODO: Implement updateVariant() method.
    }

    /**
     * @param ProductInterface $variant
     * @param array $variantData
     * @param string $locale
     */
    private function mapDataToVariant(ProductInterface $variant, array $variantData, $locale)
    {
        $productTranslation = $this->productManager->retrieveOrCreateProductTranslationByLocale($variant, $locale);
        $productTranslation->setName($this->getProperty($variantData, 'name'));

        $variant->setNumber($this->getProperty($variantData, 'number'));

        $this->processAttributes($variant, $variantData, $locale);
        $this->processPrices($variant, $variantData);
    }

    /**
     * Adds variant attributes to variant.
     *
     * @param ProductInterface $variant
     * @param array $variantData
     * @param string $locale
     *
     * @throws ProductException
     */
    private function processAttributes(ProductInterface $variant, array $variantData, $locale)
    {
        $parent = $variant->getParent();

        // Number of attributes in variantData and parents variant-attributes need to match and must not be 0.
        $sizeOfVariantAttributes = sizeof($this->getProperty($variantData, 'attributes'));
        $sizeOfParentAttributes = $parent->getVariantAttributes()->count();
        if (!$sizeOfVariantAttributes
            || !$sizeOfParentAttributes
            || $sizeOfVariantAttributes != $sizeOfParentAttributes
        ) {
            throw new ProductException('Invalid number of attributes for variant provided!');
        }

        $attributesDataCopy = $variantData['attributes'];

        //TODO: Define schema with attributeId and AttributeValueName
        foreach ($parent->getVariantAttributes() as $variantAttribute) {
            foreach ($attributesDataCopy as $index => $attributeData) {
                if ($variantAttribute->getId() === $attributeData['attributeId']) {
                    // TODO: This only works for create context; ALSO HANDLE UPDATE!
                    $attributeValue = $this->productAttributeManager->createAttributeValue(
                        $variantAttribute,
                        $attributeData['attributeValueName'],
                        $locale
                    );
                    $this->productAttributeManager->createProductAttribute(
                        $variantAttribute,
                        $variant,
                        $attributeValue
                    );

                    // Remove from data to speed up things.
                    unset($attributesDataCopy[$index]);
                }
            }
        }

        // Not all necessary variant attributes were defined in data array!
        if (sizeof($attributesDataCopy)) {
            throw new ProductException('Invalid attributes for variant provided!');
        }
    }

    /**
     * Adds or updates price information for a variant.
     *
     * @param ProductInterface $variant
     * @param array $variantData
     */
    private function processPrices(ProductInterface $variant, array $variantData)
    {
        if (!sizeof($variantData['prices'])) {
            return;
        }

        $currentPrices = iterator_to_array($variant->getPrices());
        foreach ($variantData['prices'] as $price) {
            $matchingEntity = null;

            // Try to find existing price.
            /** @var ProductPrice $currentPrice */
            foreach ($currentPrices as $index => $currentPrice) {
                if ($price['currency']['id'] === $currentPrice->getCurrency()->getId()) {
                    $matchingEntity = $currentPrice;
                    unset($currentPrices[$index]);

                    break;
                }
            }

            // Create new price if no match was found.
            if (!$matchingEntity) {
                $this->productPriceManager->createNewProductPriceForCurrency(
                    $variant,
                    $price['price'],
                    0,
                    $price['currency']['id']
                );
            } else {
                $matchingEntity->setPrice($price['price']);
            }
        }

        // The following prices were not matched and therefore have to be deleted.
        foreach ($currentPrices as $price) {
            $this->entityManager->remove($price);
        }
    }

    /**
     * Returns the product type of a variant.
     *
     * @return Type
     */
    private function getTypeVariant()
    {
        return $this->entityManager->getReference(Type::class, $this->productTypesMap['PRODUCT_VARIANT']);
    }
}
