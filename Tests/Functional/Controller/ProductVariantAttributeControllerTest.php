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
use Sulu\Bundle\ProductBundle\Entity\AttributeType;
use Sulu\Bundle\ProductBundle\Tests\Resources\ProductTestData;
use Sulu\Bundle\TestBundle\Testing\SuluTestCase;
use Symfony\Component\HttpKernel\Client;

class ProductVariantAttributeControllerTest extends SuluTestCase
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
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var AttributeType
     */
    protected $attributeType2;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var ProductTestData
     */
    private $productTestData;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->entityManager = $this->getEntityManager();
        $this->purgeDatabase();
        $this->setUpTestData();
        $this->client = $this->createAuthenticatedClient();
        $this->entityManager->flush();
    }

    /**
     * Create initial data for tests.
     */
    private function setUpTestData()
    {
        $this->productTestData = new ProductTestData($this->getContainer(), true);

        $product = $this->productTestData->getProduct();
        $attribute1 = $this->productTestData->createAttribute();
        $attribute2 = $this->productTestData->createAttribute();

        $product->addVariantAttribute($attribute1);
        $product->addVariantAttribute($attribute2);

        $this->entityManager->flush();
    }

    /**
     * Returns base path for receiving variantAttributes.
     *
     * @return string
     */
    private function getBasePath()
    {
        return sprintf('/api/products/%s/variant-attributes', $this->productTestData->getProduct()->getId());
    }

    /**
     * Test fields api.
     */
    public function testGetFields()
    {
        $this->client->request('GET', '/api/product-variant-attributes/fields');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent());
        $this->assertGreaterThanOrEqual(2, $response);
    }

    /**
     * Get all available attributes.
     */
    public function testGetAll()
    {
        $this->client->request('GET', $this->getBasePath() . '?flat=true&locale=' . static::REQUEST_LOCALE);
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $response = json_decode($this->client->getResponse()->getContent(), true);
        $attributes = $response['_embedded']['variantAttributes'];
        $this->assertCount(2, $attributes);
    }
}
