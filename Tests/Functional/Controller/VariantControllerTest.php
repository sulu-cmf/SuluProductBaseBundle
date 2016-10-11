<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Tests\Functional\Controller;

use Doctrine\ORM\EntityManager;
use Sulu\Bundle\ProductBundle\Api\ApiProductInterface;
use Sulu\Bundle\ProductBundle\Entity\Attribute;
use Sulu\Bundle\ProductBundle\Entity\Currency;
use Sulu\Bundle\ProductBundle\Entity\CurrencyRepository;
use Sulu\Bundle\ProductBundle\Entity\Status;
use Sulu\Bundle\ProductBundle\Entity\StatusTranslation;
use Sulu\Bundle\ProductBundle\Entity\Type;
use Sulu\Bundle\ProductBundle\Product\ProductFactoryInterface;
use Sulu\Bundle\ProductBundle\Tests\Resources\ProductTestData;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Bundle\FrameworkBundle\Client;

class VariantControllerTest extends SuluTestCase
{
    /**
     * @var string
     */
    const REQUEST_LOCALE = 'en';

    /**
     * @var array
     */
    protected static $entities;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Status
     */
    protected $activeStatus;

    /**
     * @var Type
     */
    protected $productType;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var ApiProductInterface
     */
    private $product;

    /**
     * @var Currency
     */
    private $currencyEUR;

    /**
     * @var Type
     */
    protected $productWithVariantsType;

    /**
     * @var array
     */
    private $productVariants = [];

    /**
     * @var ProductTestData
     */
    private $productTestData;

    /**
     * @var Attribute
     */
    private $attribute1;

    /**
     * @var Attribute
     */
    private $attribute2;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->em = $this->getEntityManager();
        $this->purgeDatabase();
        $this->createFixtures();
        $this->client = $this->createAuthenticatedClient();
    }

    /**
     * Creates initial data for test.
     */
    public function createFixtures()
    {
        $this->productTestData = new ProductTestData($this->getContainer(), false);
        $this->attribute1 = $this->productTestData->createAttribute();
        $this->attribute2 = $this->productTestData->createAttribute();

        $this->currencyEUR = $this->getCurrencyRepository()->findByCode('EUR');

        $this->productType = new Type();
        $this->productType->setTranslationKey('Type1');
        $this->em->persist($this->productType);

        $this->productWithVariantsType = new Type();
        $this->productWithVariantsType->setTranslationKey('Type2');
        $this->em->persist($this->productWithVariantsType);

        $this->activeStatus = new Status();
        $activeStatusTranslation = new StatusTranslation();
        $activeStatusTranslation->setLocale(self::REQUEST_LOCALE);
        $activeStatusTranslation->setName('Active');
        $activeStatusTranslation->setStatus($this->activeStatus);

        $this->em->persist($this->activeStatus);
        $this->em->persist($activeStatusTranslation);

        $this->product = $this->getProductFactory()->createApiEntity(
            $this->getProductFactory()->createEntity(),
            self::REQUEST_LOCALE
        );
        $productEntity = $this->product->getEntity();
        $this->product->setName('Product with Variants');
        $this->product->setNumber('1');
        $this->product->setStatus($this->activeStatus);
        $this->product->setType($this->productWithVariantsType);

        $productEntity->addVariantAttribute($this->attribute1);
        $productEntity->addVariantAttribute($this->attribute2);

        $this->em->persist($this->product->getEntity());

        $productVariant1 = $this->getProductFactory()->createApiEntity(
            $this->getProductFactory()->createEntity(),
            self::REQUEST_LOCALE
        );
        $productVariant1->setName('Productvariant');
        $productVariant1->setNumber('2');
        $productVariant1->setStatus($this->activeStatus);
        $productVariant1->setType($this->productType);
        $productVariant1->setParent($this->product);
        $this->em->persist($productVariant1->getEntity());
        $this->productVariants[] = $productVariant1;

        $productVariant2 = $this->getProductFactory()->createApiEntity(
            $this->getProductFactory()->createEntity(),
            self::REQUEST_LOCALE
        );
        $productVariant2->setName('Another Productvariant');
        $productVariant2->setNumber('3');
        $productVariant2->setStatus($this->activeStatus);
        $productVariant2->setType($this->productType);
        $productVariant2->setParent($this->product);
        $this->em->persist($productVariant2->getEntity());
        $this->productVariants[] = $productVariant2;

        $anotherProduct = $this->getProductFactory()->createApiEntity(
            $this->getProductFactory()->createEntity(),
            self::REQUEST_LOCALE
        );
        $anotherProduct->setName('Another product');
        $anotherProduct->setNumber('4');
        $anotherProduct->setStatus($this->activeStatus);
        $anotherProduct->setType($this->productType);
        $this->em->persist($anotherProduct->getEntity());

        $this->em->flush();
    }

    /**
     * Test flat get all variants of a product.
     */
    public function testGetAll()
    {
        $this->client->request(
            'GET',
            '/api/products/' . $this->product->getId() . '/variants?flat=true&locale=' . self::REQUEST_LOCALE
        );
        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(2, $response->total);
        $this->assertCount(2, $response->_embedded->products);
        $this->assertEquals('Productvariant', $response->_embedded->products[0]->name);
        $this->assertEquals('Another Productvariant', $response->_embedded->products[1]->name);
    }

    /**
     * Test post for creating a new product.
     *
     * TODO: ADD POST STRUCTURE TO DOCUMENTATION.
     */
    public function testPost()
    {
        $this->client->request(
            'POST',
            '/api/products/' . $this->product->getId() . '/variants?locale=' . self::REQUEST_LOCALE,
            [
                'name' => 'ProductVariant 1',
                'number' => '1234',
                'prices' => [
                    [
                        'price' => 17.99,
                        'currency' => [
                            'id' => $this->currencyEUR->getId(),
                        ],
                    ],
                ],
                'attributes' => [
                    [
                        'attributeId' => $this->attribute1->getId(),
                        'attributeValueName' => 'Attribute-1-Value-Text',
                    ],
                    [
                        'attributeId' => $this->attribute2->getId(),
                        'attributeValueName' => 'Attribute-2-Value-Text',
                    ],
                ],
            ]
        );

        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('2', $response->number);
        $this->assertEquals('1', $response->parent->number);

        $this->client->request('GET', ProductControllerTest::getGetUrlForProduct($this->productVariants[0]->getId()));

        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals('1', $response->parent->number);
    }

    /**
     * Test post when parent product does not exist.
     */
    public function testPostWithNotExistingParent()
    {
        $this->client->request('POST', '/api/products/3/variants', ['id' => $this->productVariants[0]->getId()]);

        $response = json_decode($this->client->getResponse()->getContent());

        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
        $this->assertEquals(
            'Entity with the type "SuluProductBundle:Product" and the id "3" not found.',
            $response->message
        );
    }

// FIXME: This test is not needed anymore since variant logic changed.
//    public function testPostWithNotExistingVariant()
//    {
//        $this->client->request('POST', '/api/products/' . $this->product->getId() . '/variants', ['id' => 2]);
//
//        $response = json_decode($this->client->getResponse()->getContent());
//
//        $this->assertEquals(400, $this->client->getResponse()->getStatusCode());
//        $this->assertEquals(
//            'Entity with the type "SuluProductBundle:Product" and the id "2" not found.',
//            $response->message
//        );
//    }

    /**
     * Test deleting a product variant.
     */
    public function testDelete()
    {
        $this->client->request(
            'GET',
            '/api/products/' . $this->product->getId() . '/variants/' . $this->productVariants[1]->getId()
        );
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'DELETE',
            '/api/products/' . $this->product->getId() . '/variants/' . $this->productVariants[1]->getId()
        );
        $this->assertEquals(204, $this->client->getResponse()->getStatusCode());

        $this->client->request(
            'GET',
            '/api/products/' . $this->product->getId() . '/variants/' . $this->productVariants[1]->getId()
        );
        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * @return ProductFactoryInterface
     */
    private function getProductFactory()
    {
        return $this->getContainer()->get('sulu_product.product_factory');
    }

    /**
     * @return CurrencyRepository
     */
    private function getCurrencyRepository()
    {
        return $this->getContainer()->get('sulu_product.currency_repository');
    }

}
