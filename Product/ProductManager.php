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

use DateTime;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ProductBundle\Entity\AttributeValue;
use Sulu\Bundle\ProductBundle\Entity\AttributeValueTranslation;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;
use Sulu\Bundle\ContactBundle\Entity\Account;
use Sulu\Bundle\CategoryBundle\Api\Category;
use Sulu\Bundle\CategoryBundle\Entity\CategoryRepository;
use Sulu\Bundle\ProductBundle\Api\ProductPrice;
use Sulu\Bundle\ProductBundle\Api\Status;
use Sulu\Bundle\ProductBundle\Entity\Status as StatusEntity;
use Sulu\Bundle\ProductBundle\Entity\AttributeSetRepository;
use Sulu\Bundle\ProductBundle\Entity\CurrencyRepository;
use Sulu\Bundle\ProductBundle\Entity\ProductInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductPrice as ProductPriceEntity;
use Sulu\Bundle\ProductBundle\Entity\SpecialPrice;
use Sulu\Bundle\ProductBundle\Entity\StatusRepository;
use Sulu\Bundle\ProductBundle\Entity\TaxClass;
use Sulu\Bundle\ProductBundle\Entity\TaxClassRepository;
use Sulu\Bundle\ProductBundle\Entity\Type;
use Sulu\Bundle\ProductBundle\Entity\TypeRepository;
use Sulu\Bundle\ProductBundle\Entity\Unit;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductException;
use Sulu\Bundle\ProductBundle\Product\Exception\MissingProductAttributeException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductChildrenExistException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductDependencyNotFoundException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Product\Exception\InvalidProductAttributeException;
use Sulu\Component\Persistence\RelationTrait;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineGroupConcatFieldDescriptor;
use Sulu\Component\Rest\ListBuilder\Doctrine\FieldDescriptor\DoctrineJoinDescriptor;
use Sulu\Component\Security\Authentication\UserRepositoryInterface;
use Sulu\Bundle\ProductBundle\Entity\ProductAttribute;
use Sulu\Bundle\ProductBundle\Entity\SpecialPriceRepository;
use Sulu\Bundle\ProductBundle\Entity\DeliveryStatusRepository;
use Sulu\Bundle\ProductBundle\Entity\AttributeRepository;
use Sulu\Bundle\ProductBundle\Entity\ProductAttributeRepository;
use Sulu\Bundle\ProductBundle\Entity\UnitRepository;
use Sulu\Bundle\ProductBundle\Entity\DeliveryStatus;
use Sulu\Bundle\ProductBundle\Api\Product;
use Sulu\Bundle\MediaBundle\Media\Manager\MediaManager;
use Sulu\Bundle\ContactBundle\Entity\AccountRepository;

class ProductManager implements ProductManagerInterface
{
    use RelationTrait;

    const MAX_BATCH_DELETE = 20;
    const MAX_SEARCH_TERMS_LENGTH = 500;

    const SUPPLIER_PREFIX = 'S';
    const USER_PREFIX = 'U';

    protected static $productEntityName = 'SuluProductBundle:Product';
    protected static $productTypeEntityName = 'SuluProductBundle:Type';
    protected static $productTypeTranslationEntityName = 'SuluProductBundle:TypeTranslation';
    protected static $unitEntityName = 'SuluProductBundle:Unit';
    protected static $unitTranslationEntityName = 'SuluProductBundle:UnitTranslation';
    protected static $productStatusEntityName = 'SuluProductBundle:Status';
    protected static $accountsSupplierEntityName = 'SuluAccountBundle:Account';
    protected static $productStatusTranslationEntityName = 'SuluProductBundle:StatusTranslation';
    protected static $attributeSetEntityName = 'SuluProductBundle:AttributeSet';
    protected static $attributeEntityName = 'SuluProductBundle:Attribute';
    protected static $productTranslationEntityName = 'SuluProductBundle:ProductTranslation';
    protected static $productTaxClassEntityName = 'SuluProductBundle:TaxClass';
    protected static $productDeliveryStatusEntityName = 'SuluProductBundle:DeliveryStatus';
    protected static $productDeliveryStatusTranslationEntityName = 'SuluProductBundle:DeliveryStatusTranslation';
    protected static $productPriceEntityName = 'SuluProductBundle:ProductPrice';
    protected static $currencyEntityName = 'SuluProductBundle:Currency';
    protected static $categoryEntityName = 'SuluCategoryBundle:Category';

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var SpecialPriceRepository
     */
    protected $specialPriceRepository;

    /**
     * @var AttributeRepository
     */
    protected $attributeRepository;

    /**
     * @var ProductAttributeRepository
     */
    protected $productAttributeRepository;

    /**
     * @var AttributeSetRepository
     */
    private $attributeSetRepository;

    /**
     * @var StatusRepository
     */
    private $statusRepository;

    /**
     * @var DeliveryStatusRepository
     */
    private $deliveryStatusRepository;

    /**
     * @var TypeRepository
     */
    private $typeRepository;

    /**
     * @var TaxClassRepository
     */
    private $taxClassRepository;

    /**
     * @var CurrencyRepository
     */
    private $currencyRepository;

    /**
     * @var UnitRepository
     */
    private $unitRepository;

    /**
     * @var CategoryRepository
     */
    private $categoryRepository;

    /**
     * @var UserRepositoryInterface
     */
    protected $userRepository;

    /**
     * @var AccountRepository
     */
    protected $accountRepository;

    /**
     * @var MediaManager
     */
    protected $mediaManager;

    /**
     * @var ObjectManager
     */
    protected $em;

    /**
     * @var string
     */
    protected $defaultCurrency;

    /**
     * @var ProductFactoryInterface
     */
    protected $productFactory;

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param SpecialPriceRepository $specialPriceRepository
     * @param AttributeSetRepository $attributeSetRepository
     * @param AttributeRepository $attributeRepository
     * @param ProductAttributeRepository $productAttributeRepository
     * @param StatusRepository $statusRepository
     * @param DeliveryStatusRepository $deliveryStatusRepository
     * @param TypeRepository $typeRepository
     * @param TaxClassRepository $taxClassRepository
     * @param CurrencyRepository $currencyRepository
     * @param UnitRepository $unitRepository
     * @param ProductFactoryInterface $productFactory
     * @param CategoryRepository $categoryRepository
     * @param UserRepositoryInterface $userRepository
     * @param MediaManager $mediaManager
     * @param ObjectManager $em
     * @param AccountRepository $accountRepository
     * @param string $defaultCurrency
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        SpecialPriceRepository $specialPriceRepository,
        AttributeSetRepository $attributeSetRepository,
        AttributeRepository $attributeRepository,
        ProductAttributeRepository $productAttributeRepository,
        StatusRepository $statusRepository,
        DeliveryStatusRepository $deliveryStatusRepository,
        TypeRepository $typeRepository,
        TaxClassRepository $taxClassRepository,
        CurrencyRepository $currencyRepository,
        UnitRepository $unitRepository,
        ProductFactoryInterface $productFactory,
        CategoryRepository $categoryRepository,
        UserRepositoryInterface $userRepository,
        MediaManager $mediaManager,
        ObjectManager $em,
        AccountRepository $accountRepository,
        $defaultCurrency
    ) {
        $this->productRepository = $productRepository;
        $this->specialPriceRepository = $specialPriceRepository;
        $this->attributeSetRepository = $attributeSetRepository;
        $this->attributeRepository = $attributeRepository;
        $this->productAttributeRepository = $productAttributeRepository;
        $this->statusRepository = $statusRepository;
        $this->deliveryStatusRepository = $deliveryStatusRepository;
        $this->typeRepository = $typeRepository;
        $this->taxClassRepository = $taxClassRepository;
        $this->currencyRepository = $currencyRepository;
        $this->unitRepository = $unitRepository;
        $this->categoryRepository = $categoryRepository;
        $this->userRepository = $userRepository;
        $this->mediaManager = $mediaManager;
        $this->em = $em;
        $this->productFactory = $productFactory;
        $this->accountRepository = $accountRepository;
        $this->defaultCurrency = $defaultCurrency;
    }

    /**
     * Returns a list of fieldDescriptors just used for filtering
     *
     * @return DoctrineFieldDescriptor[]
     */
    public function getFilterFieldDescriptors()
    {
        $fieldDescriptors = array();

        $fieldDescriptors['type_id'] = new DoctrineFieldDescriptor(
            'id',
            'type_id',
            self::$productTypeEntityName,
            null,
            array(
                self::$productTypeEntityName => new DoctrineJoinDescriptor(
                    self::$productTypeEntityName,
                    static::$productEntityName . '.type'
                )
            )
        );

        $fieldDescriptors['status_id'] = new DoctrineFieldDescriptor(
            'id',
            'status_id',
            self::$productStatusEntityName,
            null,
            array(
                self::$productStatusEntityName => new DoctrineJoinDescriptor(
                    self::$productStatusEntityName,
                    static::$productEntityName . '.status'
                )
            )
        );

        $fieldDescriptors['accounts_supplier_id'] = new DoctrineFieldDescriptor(
            'id',
            'supplier_id',
            self::$accountsSupplierEntityName,
            null,
            array(
                self::$accountsSupplierEntityName => new DoctrineJoinDescriptor(
                    self::$accountsSupplierEntityName,
                    static::$productEntityName . '.supplier'
                )
            )
        );

        $fieldDescriptors['is_deprecated'] = new DoctrineFieldDescriptor(
            'isDeprecated',
            'is_deprecated',
            static::$productEntityName,
            null,
            array()
        );

        $fieldDescriptors['parent'] = new DoctrineFieldDescriptor(
            'id',
            'parent',
            static::$productEntityName . 'Parent',
            'product.parent',
            array(
                static::$productEntityName . 'Parent' => new DoctrineJoinDescriptor(
                    static::$productEntityName,
                    static::$productEntityName . '.parent'
                )
            ),
            true
        );

        $fieldDescriptors['categories'] = new DoctrineFieldDescriptor(
            'id',
            'categories',
            static::$productEntityName . 'Categories',
            'products.categories',
            array(
                static::$productEntityName . 'Categories' => new DoctrineJoinDescriptor(
                    static::$productEntityName,
                    static::$productEntityName . '.categories'
                )
            ),
            true
        );

        $fieldDescriptors['attributes'] = new DoctrineFieldDescriptor(
            'id',
            'attributes',
            static::$productEntityName . 'ProductAttributes',
            'products.product-attributes',
            array(
                static::$productEntityName . 'ProductAttributes' => new DoctrineJoinDescriptor(
                    static::$productEntityName,
                    static::$productEntityName . '.productAttributes'
                )
            ),
            true
        );

        return $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function getFieldDescriptors($locale)
    {
        $fieldDescriptors = array();

        $fieldDescriptors['id'] = new DoctrineFieldDescriptor(
            'id',
            'id',
            static::$productEntityName,
            'public.id',
            array(),
            true,
            false,
            'integer'
        );

        $fieldDescriptors['name'] = new DoctrineFieldDescriptor(
            'name',
            'name',
            self::$productTranslationEntityName,
            'product.name',
            array(
                self::$productTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$productTranslationEntityName,
                    static::$productEntityName . '.translations',
                    self::$productTranslationEntityName . '.locale = \'' . $locale . '\''
                )
            ),
            false,
            false,
            'string'
        );
        // TODO: currency should be dynamically set
        $currency = $this->defaultCurrency;
        $fieldDescriptors['price'] = new DoctrineFieldDescriptor(
            'price',
            'price',
            self::$productPriceEntityName,
            'product.price.' . $currency,
            array(
                self::$productPriceEntityName => new DoctrineJoinDescriptor(
                    self::$productPriceEntityName,
                    static::$productEntityName . '.prices',
                    self::$productPriceEntityName . '.minimumQuantity = 0'
                ),
                // TODO: sort by minimum quantity
                self::$currencyEntityName => new DoctrineJoinDescriptor(
                    self::$currencyEntityName,
                    static::$productPriceEntityName . '.currency',
                    self::$currencyEntityName . '.code = \'' . $currency . '\''
                )
            ),
            false,
            false,
            'number',
            '',
            '',
            true,
            false,
            'align-right'
        );

        $fieldDescriptors['number'] = new DoctrineFieldDescriptor(
            'number',
            'number',
            static::$productEntityName,
            'product.number',
            array(),
            true,
            false,
            'string'
        );

        $fieldDescriptors['internalItemNumber'] = new DoctrineFieldDescriptor(
            'internalItemNumber',
            'internalItemNumber',
            static::$productEntityName,
            'product.internal-item-number',
            array(),
            true,
            false,
            'string'
        );

        $fieldDescriptors['globalTradeItemNumber'] = new DoctrineFieldDescriptor(
            'globalTradeItemNumber',
            'globalTradeItemNumber',
            static::$productEntityName,
            'product.global-trade-item-number',
            array(),
            false,
            false,
            'string'
        );

        $fieldDescriptors['parent'] = new DoctrineFieldDescriptor(
            'id',
            'parent',
            static::$productEntityName . 'Parent',
            'product.parent',
            array(
                static::$productEntityName . 'Parent' => new DoctrineJoinDescriptor(
                    static::$productEntityName,
                    static::$productEntityName . '.parent'
                )
            ),
            true,
            false,
            'string'
        );

        $fieldDescriptors['categories'] = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor(
                'translation',
                'categoryTranslation',
                self::$categoryEntityName . 'Translation',
                'products.categories',
                array(
                    static::$productEntityName . 'Categories' => new DoctrineJoinDescriptor(
                        static::$productEntityName,
                        static::$productEntityName . '.categories'
                    ),
                    self::$categoryEntityName . 'Translation' => new DoctrineJoinDescriptor(
                        static::$categoryEntityName . 'Translation',
                        static::$productEntityName . 'Categories.translations',
                        self::$categoryEntityName . 'Translation.locale = \'' . $locale . '\''
                    ),
                )
            ),
            'categories',
            'products.categories',
            ', ',
            true,
            true,
            'string'
        );

        $fieldDescriptors['categoryIds'] = new DoctrineGroupConcatFieldDescriptor(
            new DoctrineFieldDescriptor(
                'id',
                'categoryIds',
                self::$categoryEntityName . 'Translation',
                'products.categories',
                array(
                    static::$productEntityName . 'Categories' => new DoctrineJoinDescriptor(
                        static::$productEntityName,
                        static::$productEntityName . '.categories'
                    )
                )
            ),
            'categoryIds',
            'products.categories',
            ', ',
            null,
            true,
            ''
        );

        $fieldDescriptors['manufacturer'] = new DoctrineFieldDescriptor(
            'manufacturer',
            'manufacturer',
            static::$productEntityName,
            'product.manufacturer',
            array(),
            true,
            false,
            'string'
        );

        $fieldDescriptors['supplier'] = new DoctrineFieldDescriptor(
            'name',
            'supplier',
            self::$accountsSupplierEntityName,
            'product.supplier',
            array(
                self::$accountsSupplierEntityName => new DoctrineJoinDescriptor(
                    self::$accountsSupplierEntityName,
                    static::$productEntityName . '.supplier'
                )
            ),
            false,
            false,
            'string'
        );

        $fieldDescriptors['cost'] = new DoctrineFieldDescriptor(
            'cost',
            'cost',
            static::$productEntityName,
            'product.cost',
            array(),
            true,
            false,
            'float'
        );

        $fieldDescriptors['priceInfo'] = new DoctrineFieldDescriptor(
            'priceInfo',
            'priceInfo',
            static::$productEntityName,
            'product.price-info',
            array(),
            true,
            false,
            'string'
        );

        $fieldDescriptors['type'] = new DoctrineFieldDescriptor(
            'name',
            'type',
            self::$productTypeTranslationEntityName,
            'product.type',
            array(
                self::$productTypeEntityName => new DoctrineJoinDescriptor(
                    self::$productTypeEntityName,
                    static::$productEntityName . '.type'
                ),
                self::$productTypeTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$productTypeTranslationEntityName,
                    self::$productTypeEntityName . '.translations',
                    self::$productTypeTranslationEntityName . '.locale = \'' . $locale . '\''
                ),
            ),
            true,
            false,
            'string'
        );

        $fieldDescriptors['orderUnit'] = new DoctrineFieldDescriptor(
            'name',
            'orderUnit',
            self::$unitTranslationEntityName,
            'product.order-unit',
            array(
                self::$unitEntityName => new DoctrineJoinDescriptor(
                    self::$unitEntityName,
                    static::$productEntityName . '.orderUnit'
                ),
                self::$unitTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$unitTranslationEntityName,
                    self::$unitEntityName . '.translations',
                    self::$unitTranslationEntityName . '.locale = \'' . $locale . '\''
                ),
            ),
            true,
            false,
            'string'
        );

        $fieldDescriptors['status'] = new DoctrineFieldDescriptor(
            'name',
            'status',
            self::$productStatusTranslationEntityName,
            'product.status',
            array(
                self::$productStatusEntityName => new DoctrineJoinDescriptor(
                    self::$productStatusEntityName,
                    static::$productEntityName . '.status'
                ),
                self::$productStatusTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$productStatusTranslationEntityName,
                    self::$productStatusEntityName . '.translations',
                    self::$productStatusTranslationEntityName . '.locale = \'' . $locale . '\''
                ),
            ),
            true,
            false,
            'string'
        );

        $fieldDescriptors['statusId'] = new DoctrineFieldDescriptor(
            'id',
            'statusId',
            self::$productStatusEntityName,
            null,
            array(
                self::$productStatusEntityName => new DoctrineJoinDescriptor(
                    self::$productStatusEntityName,
                    static::$productEntityName . '.status'
                )
            ),
            false,
            false,
            ''
        );

        $fieldDescriptors['deliveryStatus'] = new DoctrineFieldDescriptor(
            'name',
            'deliveryStatus',
            self::$productDeliveryStatusTranslationEntityName,
            'product.deliveryStatus',
            array(
                self::$productDeliveryStatusEntityName => new DoctrineJoinDescriptor(
                    self::$productDeliveryStatusEntityName,
                    static::$productEntityName . '.deliveryStatus'
                ),
                self::$productDeliveryStatusTranslationEntityName => new DoctrineJoinDescriptor(
                    self::$productDeliveryStatusTranslationEntityName,
                    self::$productDeliveryStatusEntityName . '.translations',
                    self::$productDeliveryStatusTranslationEntityName . '.locale = \'' . $locale . '\''
                ),
            ),
            true,
            false,
            'string'
        );

        $fieldDescriptors['created'] = new DoctrineFieldDescriptor(
            'created',
            'created',
            static::$productEntityName,
            'public.created',
            array(),
            false,
            false,
            'date'
        );

        $fieldDescriptors['changed'] = new DoctrineFieldDescriptor(
            'changed',
            'changed',
            static::$productEntityName,
            'public.changed',
            array(),
            false,
            false,
            'date'
        );

        return $fieldDescriptors;
    }

    /**
     * {@inheritDoc}
     */
    public function findByIdAndLocale($id, $locale, $loadCurrencies = true)
    {
        $product = $this->productRepository->findByIdAndLocale($id, $locale);

        if ($product) {
            // TODO: remove this, when absolutely sure, it is not needed anymore?
            if ($loadCurrencies) {
                $this->addAllCurrencies($product);
            }

            $product = $this->productFactory->createApiEntity($product, $locale);
            $this->createProductMedia($product, $locale);
            return $product;
        } else {
            return null;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function findAllByLocale($locale, $filter = array())
    {
        if (empty($filter)) {
            $products = $this->productRepository->findAllByLocale($locale);
        } else {
            $products = $this->productRepository->findByLocaleAndFilter($locale, $filter);
        }

        if ($products) {
            array_walk(
                $products,
                function (&$product) use ($locale) {
                    $product = $this->productFactory->createApiEntity($product, $locale);
                }
            );
        }

        return $products;
    }

    /**
     * Sets product media for api-product
     * Otherwise api-media will not contain additional info like url,..
     *
     * @param Product $product
     * @param string $locale
     */
    public function createProductMedia(Product $product, $locale)
    {
        $media = [];
        // We have to replace the media with a media obtained from the mediaManager since the urls and the
        // dimensions are added by the mediaManager.
        // TODO: implement proxy object who is responsible for generating the urls
        foreach ($product->getEntity()->getMedia() as $medium) {
            $media[] = $this->mediaManager->getById($medium->getId(), $locale);
        }
        $product->setMedia($media);
    }

    /**
     * Finds all elements with one of the ids.
     *
     * @param string $locale
     * @param string $ids
     *
     * @return \Sulu\Bundle\ProductBundle\Api\Product[]
     */
    public function findAllByIdsAndLocale($locale, $ids = '')
    {
        $products = $this->productRepository->findByLocaleAndIds($locale, explode(',', $ids));

        if ($products) {
            array_walk(
                $products,
                function (&$product) use ($locale) {
                    $product = $this->productFactory->createApiEntity($product, $locale);
                }
            );
        }

        return $products;
    }

    /**
     * Finds and returns a list of products for which a special price is
     * currently active.
     *
     * @param string $locale
     * @param int $limit
     * @param int $page
     *
     * @return array
     */
    public function findCurrentOfferedProducts($locale, $limit = 20, $page = 1)
    {
        $specialPrices = $this->specialPriceRepository->findAllCurrent($limit, $page);
        $products = [];
        foreach ($specialPrices as $specialPrice) {
            $product = $this->productFactory->createApiEntity($specialPrice->getProduct(), $locale);
            $this->createProductMedia($product, $locale);
            $products['product'][] = $product;
        }
        $products['pagerfanta'] = $specialPrices;

        return $products;
    }

    /**
     * Finds and returns a list of products for which a special price is
     * currently active.
     *
     * @param string $locale
     * @param int $numberResults
     *
     * @return array
     */
    public function findRandomOfferedProducts($locale, $numberResults)
    {
        // Get ids of special prices.
        $specialPriceIds = $this->specialPriceRepository->findAllCurrentIds();

        if (!$specialPriceIds) {
            return [];
        }

        // Check if number of desired results does not exceed number of special prices.
        $numberOfIds = count($specialPriceIds);
        $randomIds = $specialPriceIds;
        if ($numberResults > 0 && $numberOfIds > $numberResults) {
            // Get random ids.
            $randomIds = array_map(
                function ($key) use ($specialPriceIds) {
                    return $specialPriceIds[$key];
                },
                array_rand($specialPriceIds, $numberResults)
            );
        }

        // Get special prices.
        $specialPrices = $this->specialPriceRepository->findById($randomIds);

        // Shuffle prices.
        shuffle($specialPrices);

        $products = [];
        foreach ($specialPrices as $specialPrice) {
            $product = $this->productFactory->createApiEntity($specialPrice->getProduct(), $locale);
            $this->createProductMedia($product, $locale);
            $products[] = $product;
        }

        return $products;
    }

    /**
     * Returns all simple products in the given locale for the given number.
     *
     * @param string $locale The locale of the product to load
     * @param $internalItemNumber
     *
     * @return ProductInterface[]
     */
    public function findByLocaleAndInternalItemNumber($locale, $internalItemNumber)
    {
        $products = $this->productRepository->findByLocaleAndInternalItemNumber(
            $locale,
            $internalItemNumber
        );
        if ($products) {
            array_walk(
                $products,
                function (&$product) use ($locale) {
                    $product = $this->productFactory->createApiEntity($product, $locale);
                }
            );
        }

        return $products;
    }

    /**
     * Returns all products for the given internal-number.
     *
     * @param string $internalItemNumber
     *
     * @return ProductInterface[]
     */
    public function findEntitiesByInternalItemNumber($internalItemNumber)
    {
        $products = $this->productRepository->findByInternalItemNumber(
            $internalItemNumber
        );

        return $products;
    }

    /**
     * Fetches a product.
     *
     * @param int $id
     * @param string $locale
     *
     * @throws ProductNotFoundException
     *
     * @return \Sulu\Bundle\ProductBundle\Api\Product
     */
    protected function fetchProduct($id, $locale)
    {
        $product = $this->productRepository->findByIdAndLocale($id, $locale);

        if (!$product) {
            throw new ProductNotFoundException($id);
        }

        return $this->productFactory->createApiEntity($product, $locale);
    }

    /**
     * Generates the internal product number.
     *
     * @param string $prefix Type of product-owner
     * @param int $ownerId Id of Product-owner
     * @param string $number Number of the product
     *
     * @return string
     */
    public function generateInternalItemNumber($prefix, $ownerId, $number)
    {
        return $prefix . '-' . $ownerId . '-' . $number;
    }

    /**
     * Checks if datetime string is valid.
     *
     * @param string $dateTimeString
     *
     * @return DateTime
     */
    private function checkDateString($dateTimeString)
    {
        if (empty($dateTimeString)) {
            return null;
        }

        try {
            $date = new \DateTime($dateTimeString);
        } catch (Exception $e) {
            return null;
        }

        return $date;
    }

    /**
     * Copies all data from a changed product to an active one and
     * unsets deprecated state of active product.
     *
     * @param ProductInterface $changedProduct
     * @param ProductInterface $activeProduct
     */
    public function copyDataFromChangedToActiveProduct(
        ProductInterface $changedProduct,
        ProductInterface $activeProduct
    ) {
        // copy all data from changed to active product to ensure
        // that products id does not change
        $this->convertProduct($changedProduct, $activeProduct);

        // remove deprecated state
        $activeProduct->setIsDeprecated(false);
    }

    /**
     * {@inheritDoc}
     */
    public function save(
        array $data,
        $locale,
        $userId,
        $id = null,
        $flush = true,
        $skipChanged = false,
        $supplierId = null,
        $patch = false
    ) {
        $publishedProduct = null;

        if ($id) {
            // Update an extisting product
            $product = $this->fetchProduct($id, $locale);
            $publishedProduct = $this->getExistingActiveOrInactiveProduct($product, $data['status']['id'], $locale);

        } else {
            $this->checkData($data, $id === null);
            $product = $this->productFactory->createApiEntity($this->productFactory->createEntity(), $locale);
        }

        $user = $this->userRepository->findUserById($userId);

        $product->setName($this->getProperty($data, 'name', $product->getName()));

        if (array_key_exists('minimumOrderQuantity', $data)) {
            if (is_numeric($data['minimumOrderQuantity'])) {
                $value = $this->getProperty(
                    $data,
                    'minimumOrderQuantity',
                    $product->getMinimumOrderQuantity()
                );

                $product->setMinimumOrderQuantity(floatval($value));
            } else {
                $product->setMinimumOrderQuantity(null);
            }
        }

        if (array_key_exists('recommendedOrderQuantity', $data)) {
            if (is_numeric($data['recommendedOrderQuantity'])) {
                $value = $this->getProperty(
                    $data,
                    'recommendedOrderQuantity',
                    $product->getRecommendedOrderQuantity()
                );

                $product->setRecommendedOrderQuantity(floatval($value));
            } else {
                $product->setRecommendedOrderQuantity(null);
            }
        }

        if (array_key_exists('orderContentRatio', $data)) {
            if (is_numeric($data['orderContentRatio'])) {
                $value = $this->getProperty(
                    $data,
                    'orderContentRatio',
                    $product->getOrderContentRatio()
                );

                $product->setOrderContentRatio(floatval($value));
            } else {
                $product->setOrderContentRatio(1);
            }
        }

        $product->setShortDescription($this->getProperty($data, 'shortDescription', $product->getShortDescription()));
        $product->setLongDescription($this->getProperty($data, 'longDescription', $product->getLongDescription()));
        $product->setNumber($this->getProperty($data, 'number', $product->getNumber()));
        $product->setPriceInfo($this->getProperty($data, 'priceInfo', $product->getPriceInfo()));
        $product->setGlobalTradeItemNumber(
            $this->getProperty(
                $data,
                'globalTradeItemNumber',
                $product->getGlobalTradeItemNumber()
            )
        );
        $product->setManufacturer($this->getProperty($data, 'manufacturer', $product->getManufacturer()));
        $product->setAreGrossPrices($this->getProperty($data, 'areGrossPrices', $product->getAreGrossPrices()));
        $product->setPriceInfo($this->getProperty($data, 'priceInfo', $product->getPriceInfo()));
        if (!$product->getInternalItemNumber()) {
            if ($supplierId) {
                $product->setInternalItemNumber(
                    $this->generateInternalItemNumber(
                        self::SUPPLIER_PREFIX,
                        $supplierId,
                        $product->getNumber()
                    )
                );
            } else {
                $product->setInternalItemNumber(
                    $this->generateInternalItemNumber(
                        self::USER_PREFIX,
                        $userId,
                        $product->getNumber()
                    )
                );
            }
        }

        $this->processAttributes($data, $product, $locale);


        if (array_key_exists('specialPrices', $data)) {
            $specialPricesData = $data['specialPrices'];

            // Array for local special prices storage.
            $specialPrices = array();

            // Array of currency codes to be used as keys for special prices.
            $currencyCodes = array();

            // Create array of special price currency codes in json request.
            foreach ($specialPricesData as $key => $specialPrice) {
                if (!empty($specialPrice['currency']['code']) && !empty($specialPrice['price'])) {
                    array_push($currencyCodes, $specialPrice['currency']['code']);
                } else {
                    unset($specialPricesData[$key]);
                }
            }

            // Iterate through already added special prices for this specific product.
            foreach ($product->getSpecialPrices() as $specialPrice) {
                // Save special prices to array.
                $specialPrices[$specialPrice->getCurrency()->getCode()] = $specialPrice;

                // Check if special price code already exists in array if not remove it from product.
                if (!in_array($specialPrice->getCurrency()->getCode(), $currencyCodes)) {
                    $product->removeSpecialPrice($specialPrice->getEntity());
                    $this->em->remove($specialPrice->getEntity());
                }
            }

            // Iterate through send json array of special prices.
            foreach ($specialPricesData as $specialPriceData) {
                // If key does not exists add a new special price to product.
                if (!array_key_exists($specialPriceData['currency']['code'], $specialPrices)) {
                    $specialPrice = new SpecialPrice();

                    $currency = $this->currencyRepository->findByCode($specialPriceData['currency']['code']);
                    $specialPrice->setCurrency($currency);

                    $specialPrice->setProduct($product->getEntity());

                    $product->addSpecialPrice($specialPrice);
                    $this->em->persist($specialPrice);
                // Else update the already existing special price.
                } else {
                    $specialPrice = $specialPrices[$specialPriceData['currency']['code']]->getEntity();
                }

                if (isset($specialPriceData['price'])) {
                    $specialPrice->setPrice($specialPriceData['price']);
                }

                if (isset($specialPriceData['startDate'])) {
                    $startDate = $this->checkDateString($specialPriceData['startDate']);
                    $specialPrice->setStartDate($startDate);
                }

                if (isset($specialPriceData['endDate'])) {
                    $endDate = $this->checkDateString($specialPriceData['endDate']);

                    if ($endDate) {
                        // Set time to 23:59:59.
                        $endDate->setTime(23, 59, 59);
                    }
                    $specialPrice->setEndDate($endDate);
                }
            }
        }

        if (isset($data['parent']) && isset($data['parent']['id'])) {
            $parentId = $data['parent']['id'];
            $parentProduct = $this->findByIdAndLocale($parentId, $locale, false);
            if (!$parentProduct) {
                throw new ProductDependencyNotFoundException(static::$productEntityName, $parentId);
            }
            $product->setParent($parentProduct);
        }

        if (isset($data['cost']) && is_numeric($data['cost'])) {
            $product->setCost(floatval($data['cost']));
        }

        if (isset($data['searchTerms'])) {
            $searchTerms = $this->parseCommaSeparatedString($data['searchTerms']);
            $product->setSearchTerms($searchTerms);
        }

        if (isset($data['status']) && isset($data['status']['id'])) {
            $statusId = $data['status']['id'];
            /** @var Status $status */
            $this->setStatusForProduct($product, $statusId);
        }

        if (isset($data['type']) && isset($data['type']['id'])) {
            $typeId = $data['type']['id'];
            /** @var Type $type */
            $type = $this->typeRepository->find($typeId);
            if (!$type) {
                throw new ProductDependencyNotFoundException(self::$productTypeEntityName, $typeId);
            }
            $product->setType($type);
        }

        if (isset($data['orderUnit']) && isset($data['orderUnit']['id'])) {
            $orderUnitId = $data['orderUnit']['id'];
            /** @var Unit $orderUnit */
            $orderUnit = $this->unitRepository->find($orderUnitId);
            if (!$orderUnit) {
                throw new ProductDependencyNotFoundException(self::$unitEntityName, $orderUnitId);
            }
            $product->setOrderUnit($orderUnit);
        } elseif (!$id) {
            // Default Unit
            $orderUnit = $this->unitRepository->find(Unit::PIECE_ID);
            $product->setOrderUnit($orderUnit);
        }

        if (isset($data['contentUnit']) && isset($data['contentUnit']['id'])) {
            $contentUnitId = $data['contentUnit']['id'];
            /** @var Unit $contentUnit */
            $contentUnit = $this->unitRepository->find($contentUnitId);
            if (!$contentUnit) {
                throw new ProductDependencyNotFoundException(self::$unitEntityName, $contentUnitId);
            }
            $product->setContentUnit($contentUnit);
        } elseif (!$patch) {
            $product->setContentUnit(null);
        }

        if (array_key_exists('deliveryTime', $data)) {
            if (is_numeric($data['deliveryTime'])) {
                $product->setDeliveryTime(intval($data['deliveryTime']));
            } else {
                $product->setDeliveryTime(0);
            }
        }

        if (isset($data['supplier']) && isset($data['supplier']['id'])) {
            $supplierId = $data['supplier']['id'];
            /** @var Account $supplierId */
            $supplier = $this->accountRepository->find($supplierId);
            if (!$supplier) {
                throw new ProductDependencyNotFoundException(self::$accountsSupplierEntityName, $supplierId);
            }
            $product->setSupplier($supplier);
        } elseif (isset($data['supplier']) && !isset($data['supplier']['id'])) {
            $product->setSupplier(null);
        }

        if (isset($data['taxClass']) && isset($data['taxClass']['id'])) {
            $taxClassId = $data['taxClass']['id'];
            /** @var TaxClass $taxClass */
            $taxClass = $this->taxClassRepository->find($taxClassId);
            if (!$taxClass) {
                throw new ProductDependencyNotFoundException(self::$productTaxClassEntityName, $taxClassId);
            }
            $product->setTaxClass($taxClass);
        } elseif ($product->getTaxClass() == null) {
            // Default tax class
            $taxClass = $this->taxClassRepository->find(TaxClass::STANDARD_TAX_RATE);
            $product->setTaxClass($taxClass);
        }

        if (isset($data['categories'])) {
            $get = function (Category $category) {
                return $category->getId();
            };

            $add = function ($categoryData) use ($product) {
                return $this->addCategory($product->getEntity(), $categoryData);
            };

            $delete = function (Category $category) use ($product) {
                $product->removeCategory($category->getEntity());

                return true;
            };

            $this->processSubEntities(
                $product->getCategories(),
                $data['categories'],
                $get,
                $add,
                null,
                $delete
            );
        }

        if (array_key_exists('prices', $data)) {
            if (isset($data['id']) && ($product->getId() == $data['id'])) {
                $compare = function (ProductPrice $price, $data) {
                    if (isset($data['id'])) {
                        return $data['id'] == $price->getId();
                    } else {
                        return $this->priceHasChanged($data, $price);
                    }
                };
            } else {
                $compare = function (ProductPrice $price, $data) {
                    return $this->priceHasChanged($data, $price);
                };
            }

            $add = function ($priceData) use ($product) {
                return $this->addPrice($product->getEntity(), $priceData);
            };

            $update = function (ProductPrice $price, $matchedEntry) {
                return $this->updatePrice($price, $matchedEntry);
            };

            $delete = function (ProductPrice $price) use ($product) {
                return $this->removePrice($product->getEntity(), $price->getEntity());
            };

            $this->compareEntitiesWithData(
                $product->getPrices(),
                $data['prices'],
                $compare,
                $add,
                $update,
                $delete
            );
        }
        if (!$skipChanged || $product->getId() == null) {
            $product->setChanged(new DateTime());
            $product->setChanger($user);
        }

        if ($product->getId() == null) {
            $product->setCreated(new DateTime());
            $product->setCreator($user);
            $this->em->persist($product->getEntity());
        }

        if ($publishedProduct) {
            // Since there is already a published product with the same internal id we are going to update the
            // existing one with the properties of the current product.
            $this->convertProduct($product->getEntity(), $publishedProduct->getEntity());

            $product = $publishedProduct;
        }

        if (isset($data['deliveryStatus']) && isset($data['deliveryStatus']['id'])) {
            $deliveryStatusId = $data['deliveryStatus']['id'];
            /** @var DeliveryStatus $deliveryStatus */
            $deliveryStatus = $this->deliveryStatusRepository->find($deliveryStatusId);
            if (!$deliveryStatus) {
                throw new ProductDependencyNotFoundException(
                    self::$productDeliveryStatusEntityName,
                    $deliveryStatusId
                );
            }
            $product->setDeliveryStatus($deliveryStatus);
        } elseif ($product->getDeliveryStatus() === null) {
            // Default delivery status
            $deliveryStatus = $this->deliveryStatusRepository->find(DeliveryStatus::AVAILABLE);
            $product->setDeliveryStatus($deliveryStatus);
        }

        if ($product->getStatus()->getId() == StatusEntity::ACTIVE) {
            // If the status of the product is active then the product must be a valid shop product!
            if (!$product->isValidShopProduct($this->defaultCurrency)) {
                // Undo changes
                $this->em->refresh($product->getEntity());
                throw new ProductException('No valid product for shop!', ProductException::PRODUCT_NOT_VALID);
            }
        }

        if ($flush) {
            $this->em->flush();
        }

        return $product;
    }

    /**
     * Parses a comma separated string.
     * Trims all values and removes empty strings.
     *
     * @param string $string
     * @param int $maxLength
     *
     * @return null|string
     */
    public function parseCommaSeparatedString($string, $maxLength = self::MAX_SEARCH_TERMS_LENGTH)
    {
        $result = null;

        // Check if string is not empty.
        if (strlen(trim($string)) === 0) {
            return null;
        }

        // Lower case for case insensitivity.
        $string = mb_strtolower($string, 'UTF-8');

        // Convert string to array.
        $fields = explode(',', $string);

        // Validate and trim each field.
        $fields = array_map([$this, 'trimAndValidateField'], $fields);

        // Remove null entries.
        $fields = array_filter($fields);

        // Parse back into string.
        $result = implode(',', $fields);

        // Check if max length is exceeded.
        if (strlen($result) > $maxLength) {
            // Shorten to max-length.
            $result = substr($result, 0, $maxLength);

            $fields = explode(',', $result);

            // Remove last element.
            array_pop($fields);

            // Only one search field is provided and exceeds limit.
            if (count($fields) === 0) {
                return null;
            }

            // Parse back into string.
            $result = implode(',', $fields);
        }

        return $result;
    }

    /**
     * Checks if price of a product has changed.
     *
     * @param array $data
     * @param float $price
     *
     * @return bool
     */
    private function priceHasChanged($data, $price)
    {
        $currencyNotChanged = isset($data['currency']) &&
            array_key_exists('name', $data['currency']) &&
            $data['currency']['name'] == $price->getCurrency()->getName();

        $valueNotChanged = array_key_exists('price', $data) &&
            $data['price'] == $price->getPrice();

        $minimumQuantityNotChanged = array_key_exists('minimumQuantity', $data) &&
            $data['minimumQuantity'] == $price->getEntity()->getMinimumQuantity();

        return $currencyNotChanged && $valueNotChanged && $minimumQuantityNotChanged;
    }

    /**
     * {@inheritDoc}
     */
    public function partialUpdate(
        array $data,
        $locale,
        $userId,
        $id
    ) {
        // check if status is set
        $this->checkDataSet($data, 'status', false) && $this->checkDataSet($data['status'], 'id', false);

        if ($id) {
            // Update an extisting product
            $product = $this->productRepository->findById($id);
            $this->setStatusForProduct($product, $data['status']['id']);
        } else {
            throw new ProductNotFoundException($id);
        }
    }

    /**
     * Copy all properties from a entity to a 'deprecated' entity.
     *
     * @param ProductInterface $productEntity
     * @param ProductInterface $publishedProductEntity
     *
     * @return ProductInterface
     */
    private function convertProduct(ProductInterface $productEntity, ProductInterface $publishedProductEntity)
    {
        // Move prices
        foreach ($publishedProductEntity->getPrices() as $data) {
            $this->em->remove($data);
        }
        foreach ($productEntity->getPrices() as $data) {
            $publishedProductEntity->addPrice($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move productAttributes
        foreach ($publishedProductEntity->getProductAttributes() as $data) {
            $this->em->remove($data);
        }
        foreach ($productEntity->getProductAttributes() as $data) {
            $publishedProductEntity->addProductAttribute($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move translations
        foreach ($publishedProductEntity->getTranslations() as $data) {
            $this->em->remove($data);
        }
        foreach ($productEntity->getTranslations() as $data) {
            $publishedProductEntity->addTranslation($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move addons
        foreach ($publishedProductEntity->getAddons() as $data) {
            $publishedProductEntity->removeAddon($data);
        }
        foreach ($productEntity->getAddons() as $data) {
            $publishedProductEntity->addAddon($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move sets
        foreach ($publishedProductEntity->getSets() as $data) {
            $publishedProductEntity->removeSet($data);
        }
        foreach ($productEntity->getSets() as $data) {
            $publishedProductEntity->addSet($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move relation
        foreach ($publishedProductEntity->getRelations() as $data) {
            $publishedProductEntity->removeRelation($data);
        }
        foreach ($productEntity->getRelations() as $data) {
            $publishedProductEntity->addRelation($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move upsell
        foreach ($publishedProductEntity->getUpsells() as $data) {
            $publishedProductEntity->removeUpsell($data);
        }
        foreach ($productEntity->getUpsells() as $data) {
            $publishedProductEntity->addUpsell($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move crossells
        foreach ($publishedProductEntity->getCrosssells() as $data) {
            $publishedProductEntity->removeCrosssell($data);
        }
        foreach ($productEntity->getCrosssells() as $data) {
            $publishedProductEntity->addCrosssell($data);
            $data->setProduct($publishedProductEntity);
        }

        // Move categories
        foreach ($publishedProductEntity->getCategories() as $data) {
            $publishedProductEntity->removeCategory($data);
        }
        foreach ($productEntity->getCategories() as $data) {
            $publishedProductEntity->addCategory($data);
        }

        // Move media
        foreach ($publishedProductEntity->getMedia() as $data) {
            $publishedProductEntity->removeMedia($data);
        }
        foreach ($productEntity->getMedia() as $data) {
            $publishedProductEntity->addMedia($data);
        }

        $publishedProductEntity->setNumber($productEntity->getNumber());
        $publishedProductEntity->setGlobalTradeItemNumber($productEntity->getGlobalTradeItemNumber());
        $publishedProductEntity->setInternalItemNumber($productEntity->getInternalItemNumber());
        $publishedProductEntity->setManufacturer($productEntity->getManufacturer());
        $publishedProductEntity->setCost($productEntity->getCost());
        $publishedProductEntity->setPriceInfo($productEntity->getPriceInfo());
        $publishedProductEntity->setChanged($productEntity->getChanged());
        $publishedProductEntity->setManufacturerCountry($productEntity->getManufacturerCountry());
        $publishedProductEntity->setType($productEntity->getType());
        $publishedProductEntity->setStatus($productEntity->getStatus());
        $publishedProductEntity->setDeliveryStatus($productEntity->getDeliveryStatus());
        $publishedProductEntity->setDeliveryTime($productEntity->getDeliveryTime());
        $publishedProductEntity->setSupplier($productEntity->getSupplier());
        $publishedProductEntity->setParent($productEntity->getParent());
        $publishedProductEntity->setContentUnit($productEntity->getContentUnit());
        $publishedProductEntity->setOrderUnit($productEntity->getOrderUnit());
        $publishedProductEntity->setOrderContentRatio($productEntity->getOrderContentRatio());
        $publishedProductEntity->setMinimumOrderQuantity($productEntity->getMinimumOrderQuantity());
        $publishedProductEntity->setRecommendedOrderQuantity($productEntity->getRecommendedOrderQuantity());
        $publishedProductEntity->setChanger($productEntity->getChanger());
        $publishedProductEntity->setSearchTerms($productEntity->getSearchTerms());
        $publishedProductEntity->setTaxClass($productEntity->getTaxClass());

        // Move children
        foreach ($publishedProductEntity->getChildren() as $data) {
            $this->em->remove($data);
        }
        foreach ($productEntity->getChildren() as $data) {
            $data->setParent($publishedProductEntity);
        }

        $this->em->remove($productEntity);
        $this->em->flush();

        return $publishedProductEntity;
    }

    /**
     * Checks if a product with the same internal product id as the given product exists in published state and
     * returns it.
     *
     * @param Product $existingProduct
     * @param int $statusId
     * @param string $locale
     *
     * @return null|\Sulu\Bundle\ProductBundle\Api\Product
     */
    protected function getExistingActiveOrInactiveProduct($existingProduct, $statusId, $locale)
    {
        if (($statusId == StatusEntity::ACTIVE || $statusId == StatusEntity::INACTIVE) &&
            $existingProduct->getStatus()->getId() != $statusId) {
            // Check if the same product already exists in IMPORTED state
            $products = $this->productRepository->findByLocaleAndInternalItemNumber(
                $locale,
                $existingProduct->getInternalItemNumber()
            );
            foreach ($products as $product) {
                if ($product->isDeprecated() && $existingProduct->getId() != $product->getId()) {
                    $product->setIsDeprecated(false);
                    return $this->productFactory->createApiEntity($product, $locale);
                }
            }
        }
        return null;
    }

    /**
     * Sets the status for a given product.
     *
     * @param Product $product
     * @param int $statusId
     *
     * @throws ProductDependencyNotFoundException
     */
    public function setStatusForProduct($product, $statusId)
    {
        $status = $this->statusRepository->find($statusId);
        if (!$status) {
            throw new ProductDependencyNotFoundException(self::$productStatusEntityName, $statusId);
        }
        $product->setStatus($status);
    }

    /**
     * Sets the deliveryStatus for a given product.
     *
     * @param Product $product
     * @param int $statusId
     *
     * @throws ProductDependencyNotFoundException
     */
    public function setDeliveryStatusForProduct($product, $statusId)
    {
        $status = $this->deliveryStatusRepository->find($statusId);
        if (!$status) {
            throw new ProductDependencyNotFoundException(self::$productStatusEntityName, $statusId);
        }
        $product->setDeliveryStatus($status);
    }

    /**
     * Updates the given price with the values from the given array.
     *
     * @param ProductPrice $price
     * @param array $matchedEntry
     *
     * @throws ProductDependencyNotFoundException
     *
     * @return bool
     */
    protected function updatePrice(ProductPrice $price, $matchedEntry)
    {
        if (isset($matchedEntry['minimumQuantity'])) {
            $price->getEntity()->setMinimumQuantity($matchedEntry['minimumQuantity']);
        }
        if (isset($matchedEntry['price'])) {
            $price->setPrice($matchedEntry['price']);
        }
        if (isset($matchedEntry['priceInfo'])) {
            $price->getEntity()->setPriceInfo($matchedEntry['priceInfo']);
        }
        if (isset($matchedEntry['currency'])) {
            $currency = $this->currencyRepository->find($matchedEntry['currency']['id']);
            if (!$currency) {
                throw new ProductDependencyNotFoundException(
                    self::$productPriceEntityName,
                    $matchedEntry['currency']['id']
                );
            }
            $price->getEntity()->setCurrency($currency);
        }

        return true;
    }

    /**
     * Adds a price to the given product.
     *
     * @param ProductInterface $product The product to add the price to
     * @param array $priceData The array containing the data for the new price
     *
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws ProductDependencyNotFoundException
     *
     * @return bool
     */
    protected function addPrice(ProductInterface $product, $priceData)
    {
        if (isset($priceData['id'])) {
            throw new EntityIdAlreadySetException(self::$productPriceEntityName, $priceData['id']);
        } elseif (isset($priceData['price'])) {
            $currency = $this->currencyRepository->find($priceData['currency']['id']);

            if (!$currency) {
                throw new ProductDependencyNotFoundException(
                    self::$productPriceEntityName,
                    $priceData['currency']['id']
                );
            }

            $price = new ProductPriceEntity();
            $price->setPrice($priceData['price']);
            $price->setProduct($product);
            $price->setCurrency($currency);
            if (isset($priceData['priceInfo'])) {
                $price->setPriceInfo($priceData['priceInfo']);
            }
            if (isset($priceData['minimumQuantity'])) {
                $price->setMinimumQuantity($priceData['minimumQuantity']);
            }
            $product->addPrice($price);

            $this->em->persist($price);
        }

        return true;
    }

    /**
     * Removes a price from the given product.
     *
     * @param ProductInterface $product
     * @param ProductPriceEntity $price
     *
     * @return bool
     */
    protected function removePrice(ProductInterface $product, ProductPriceEntity $price)
    {
        $this->em->remove($price);
        $product->removePrice($price);

        return true;
    }

    /**
     * Adds a category to the given product.
     *
     * @param ProductInterface $product The product to add the price to
     * @param array $categoryData The array containing the data for the additional category
     *
     * @throws \Sulu\Component\Rest\Exception\EntityIdAlreadySetException
     * @throws ProductDependencyNotFoundException
     *
     * @return bool
     */
    protected function addCategory(ProductInterface $product, $categoryData)
    {
        $category = $this->categoryRepository->find($categoryData['id']);

        if (!$category) {
            throw new ProductDependencyNotFoundException(
                self::$categoryEntityName,
                $categoryData['id']
            );
        }

        $product->addCategory($category);

        $this->em->persist($category);

        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function addVariant($parentId, $variantId, $locale)
    {
        $variant = $this->productRepository->findById($variantId);

        if (!$variant) {
            throw new ProductNotFoundException($variantId);
        }

        $parent = $this->productRepository->findById($parentId);

        if (!$parent) {
            throw new ProductNotFoundException($parentId);
        }

        $variant->setParent($parent);

        $this->em->flush();

        return $this->productFactory->createApiEntity($variant, $locale);
    }

    /**
     * {@inheritDoc}
     */
    public function removeVariant($parentId, $variantId)
    {
        $variant = $this->productRepository->findById($variantId);

        if (!$variant || $variant->getParent()->getId() != $parentId) {
            // TODO think about better exception
            throw new ProductNotFoundException($variantId);
        }

        $variant->setParent(null);

        $this->em->flush();
    }

    /**
     * {@inheritDoc}
     */
    protected function singleDelete($id)
    {
        // id must be an integer
        if (!is_numeric($id)) {
            throw new InvalidProductAttributeException('id', $id);
        }

        $product = $this->productRepository->findById($id);

        if (!$product) {
            throw new ProductNotFoundException($id);
        }

        // do not allow to delete entity if child is existent
        if (count($product->getChildren()) > 0) {
            throw new ProductChildrenExistException($id);
        }

        $this->em->remove($product);
    }

    /**
     * {@inheritDoc}
     */
    public function delete($ids, $userId = null, $flush = true)
    {
        if (is_array($ids)) {
            // if ids is array -> multiple delete
            $counter = 0;
            foreach ($ids as $id) {
                $counter++;
                $this->singleDelete($id, null, false);

                if ($flush && ($counter % self::MAX_BATCH_DELETE) === 0) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }
        } else {
            // if ids is int
            $this->singleDelete($ids, null, false);
        }

        if ($flush) {
            $this->em->flush();
        }
    }

    /**
     * Returns the entry from the data with the given key, or the given default value, if the key does not exist.
     *
     * @param array $data
     * @param string $key
     * @param string $default
     *
     * @return mixed
     */
    protected function getProperty(array $data, $key, $default = null)
    {
        return array_key_exists($key, $data) ? $data[$key] : $default;
    }

    /**
     * Checks if the given data is correct.
     *
     * @param array $data The data to check
     * @param boolean $create Defines if check is for new or already existing data
     */
    protected function checkData($data, $create)
    {
        $this->checkDataSet($data, 'type', $create) && $this->checkDataSet($data['type'], 'id', $create);

        $this->checkDataSet($data, 'status', $create) && $this->checkDataSet($data['status'], 'id', $create);
    }

    /**
     * Processes attributes.
     *
     * @param array $data
     * @param ProductInterface $product
     * @param string $locale
     *
     * @throws ProductDependencyNotFoundException
     */
    protected function processAttributes(array $data, ProductInterface $product, $locale)
    {
        if (isset($data['attributes'])) {
            $attributeIds = [];
            foreach ($data['attributes'] as $attributeData) {
                if (isset($attributeData['attributeId'])) {
                    $attributeIds[] = $attributeData['attributeId'];
                }
            }

            // Create local array of all currently assigned attributes of product.
            $productAttributes = [];
            foreach ($product->getAttributes() as $productAttribute) {
                $productAttributes[$productAttribute->getAttribute()->getId()] = $productAttribute;
            }

            // Add and change attributes.
            foreach ($data['attributes'] as $attributeData) {
                $attributeValue = trim($attributeData['value']);
                $attributeId = $attributeData['attributeId'];

                // If attribute value is empty do not add.
                if (!$attributeValue) {
                    // If already set on product, remove.
                    if (array_key_exists($attributeId, $productAttributes)) {
                        $product->getEntity()->removeProductAttribute($productAttribute->getEntity());
                        $this->em->remove($productAttribute->getEntity());
                    }

                    continue;
                }

                if (!array_key_exists($attributeId, $productAttributes)) {
                    // Product attribute does not exists.
                    $productAttribute = new ProductAttribute();
                    $attribute = $this->attributeRepository->find($attributeData['attributeId']);
                    if (!$attribute) {
                        throw new ProductDependencyNotFoundException(
                            self::$attributeEntityName,
                            $attributeData['attributeId']
                        );
                    }
                    $productAttribute->setAttribute($attribute);
                    $productAttribute->setProduct($product->getEntity());


                    // TODO: Create new attribute translation.
//                    $productAttribute->setValue($attributeValue);

                    $this->createAttributeValue($attribute, $attributeValue, $locale);

                    $product->addProductAttribute($productAttribute);
                    $this->em->persist($productAttribute);
                } else {
                    // Product attribute exists.
                    $productAttribute = $productAttributes[$attributeId]->getEntity();

                    // TODO: Adapt existing value.
                    $productAttribute->setValue($attributeValue);
                }
            }
        }
    }

    /**
     * Creates a new attribute value. And its translation in the specified locale.
     *
     * @param Attribute $attribute
     * @param string $value
     * @param string $locale
     *
     * @return AttributeValue
     */
    private function createAttributeValue(Attribute $attribute, $value, $locale)
    {
        $attributeValue = new AttributeValue();
        $this->em->persist($attributeValue);

        $attributeValue->setAttribute($attribute);
        $attributeValue->setSelected(true);

        $this->createAttributeValueTranslation($attributeValue, $value, $locale);

        return $attributeValue;
    }

    private function createAttributeValueTranslation(AttributeValue $attributeValue, $value, $locale)
    {
        $translation = new AttributeValueTranslation();
        $this->em->persist($translation);
        $translation->setName($value);
        $translation->setLocale($locale);
        $translation->setAttributeValue($attributeValue);

        $attributeValue->addTranslation($translation);

        return $translation;
    }

    /**
     * Checks if data for the given key is set correctly.
     *
     * @param array $data The array with the data
     * @param string $key The array key to check
     * @param bool $create Defines if the is for new or already existing data
     *
     * @throws MissingProductAttributeException
     *
     * @return bool
     */
    private function checkDataSet(array $data, $key, $create)
    {
        $keyExists = array_key_exists($key, $data);

        if (($create && !($keyExists && $data[$key] !== null)) || (!$keyExists || $data[$key] === null)) {
            throw new MissingProductAttributeException($key);
        }

        return $keyExists;
    }

    /**
     * Trims, validates and parses a string.
     *
     * @param string $field
     *
     * @return string
     */
    private function trimAndValidateField($field)
    {
        $result = trim($field);

        if (strlen($result) === 0) {
            return null;
        }

        return $result;
    }

    /**
     * Adds an ProductPrice for every currency to the Product, if it is no existing already
     *
     * @param ProductInterface $product The product to fill with currencies
     */
    private function addAllCurrencies(ProductInterface $product)
    {
        $currencies = $this->currencyRepository->findAll();

        foreach ($product->getPrices() as $price) {
            if (($key = array_search($price->getCurrency(), $currencies)) !== false) {
                unset($currencies[$key]);
            }
        }

        foreach ($currencies as $currency) {
            $price = new ProductPriceEntity();
            $price->setCurrency($currency);

            $product->addPrice($price);
            $price->setProduct($product);
        }
    }

    /**
     * Get filters provided by the request
     *
     * @param Request $request
     *
     * @return List $filter
     */
    public function getFilters(Request $request)
    {
        $filter = array();

        $statuses = $request->get('status');
        if ($statuses) {
            $filter['status'] = explode(',', $statuses);
        }

        $statusIds = $request->get('status_id');
        if ($statusIds) {
            $filter['status_id'] = explode(',', $statusIds);
        }

        $types = $request->get('type');
        if ($types) {
            $filter['type_id'] = explode(',', $types);
        }

        $typeIds = $request->get('type_id');
        if ($typeIds) {
            $filter['type_id'] = explode(',', $typeIds);
        }

        $supplierId = $request->get('supplier_id');
        if ($supplierId) {
            $filter['accounts_supplier_id'] = $supplierId;
        }

        $isDeprecated = $request->get('is_deprecated');
        if ($isDeprecated !== null) {
            $filter['is_deprecated'] = $isDeprecated;
        }

        $parent = $request->get('parent');
        if ($parent) {
            $filter['parent'] = ($parent == 'null') ? null : $parent;
        }

        $categories = $request->get('categories');
        if ($categories) {
            $filter['categories'] = ($categories == 'null') ? null : $categories;
        }
        return $filter;
    }
}
