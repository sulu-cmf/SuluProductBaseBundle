<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Controller;

use FOS\RestBundle\Controller\Annotations\Delete;
use FOS\RestBundle\Controller\Annotations\Get;
use FOS\RestBundle\Controller\Annotations\Post;
use FOS\RestBundle\Routing\ClassResourceInterface;
use Sulu\Bundle\ProductBundle\Product\Exception\AttributeNotFoundException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Product\ProductFactoryInterface;
use Sulu\Bundle\ProductBundle\Product\ProductLocaleManager;
use Sulu\Bundle\ProductBundle\Product\ProductManagerInterface;
use Sulu\Bundle\ProductBundle\Product\ProductVariantAttributeManager;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ProductVariantAttributeController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $productEntityName = 'SuluProductBundle:Product';
    protected static $attributeEntityName = 'SuluProductBundle:Attribute';

    protected static $entityKey = 'variantAttributes';

    /**
     * {@inheritdoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.product.products';
    }

    /**
     * Returns all fields that can be used by list.
     *
     * @Get("product-variant-attributes/fields")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function getFieldsAction(Request $request)
    {
        $locale = $this->getProductLocaleManager()->retrieveLocale($this->getUser(), $request->getLocale());

        return $this->handleView(
            $this->view(
                array_values($this->getVariantAttributeManager()->retrieveFieldDescriptors($locale))
            )
        );
    }

    /**
     * Returns a list of attributes.
     *
     * @Get("products/{productId}/variant-attributes")
     *
     * @param Request $request
     * @param int $productId
     *
     * @return Response
     */
    public function cgetAction(Request $request, $productId)
    {
        $locale = $this->getProductLocaleManager()->retrieveLocale($this->getUser(), $request->get('locale'));

        $list = $this->getListRepresentation($request, $locale, $productId);

        $view = $this->view($list, Response::HTTP_OK);

        return $this->handleView($view);
    }

    /**
     * Creates and stores a new attribute.
     *
     * @Post("products/{productId}/variant-attributes")
     *
     * @param Request $request
     * @param int $productId
     *
     * @return Response
     */
    public function postAction(Request $request, $productId)
    {
        try {
            $this->getVariantAttributeManager()->createVariantAttributeRelation(
                $productId,
                $request->request->all()
            );

            $this->getDoctrine()->getEntityManager()->flush();

            $view = $this->view([], Response::HTTP_NO_CONTENT);
        } catch (AttributeNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), Response::HTTP_BAD_REQUEST);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Deletes attribute from variant.
     *
     * @Delete("products/{productId}/variant-attributes/{attributeId}")
     *
     * @param int $productId
     * @param int $attributeId
     *
     * @return Response
     */
    public function deleteAction($productId, $attributeId)
    {
        try {
            $this->getVariantAttributeManager()->removeVariantAttributeRelation(
                $productId,
                $attributeId
            );

            $this->getDoctrine()->getEntityManager()->flush();

            $view = $this->view(null, Response::HTTP_NO_CONTENT);
        } catch (AttributeNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), Response::HTTP_BAD_REQUEST);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), Response::HTTP_BAD_REQUEST);
        } catch (ProductException $exc) {
            $view = $this->view($exc->getMessage(), Response::HTTP_BAD_REQUEST);
        }

        return $this->handleView($view);
    }

    /**
     * Returns a list representation.
     *
     * @param Request $request
     * @param string $locale
     * @param int $productId
     *
     * @return ListRepresentation
     */
    private function getListRepresentation($request, $locale, $productId)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listBuilder = $factory->create(static::$productEntityName);

        $restHelper->initializeListBuilder(
            $listBuilder,
            $this->getVariantAttributeManager()->retrieveFieldDescriptors($locale)
        );

        // Get all variant-attributes for product.
        $listBuilder->where(
            $this->getProductManager()->getFieldDescriptors($locale)['id'],
            $productId
        );

        $list = new ListRepresentation(
            $listBuilder->execute(),
            self::$entityKey,
            'get_attributes',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $listBuilder->count()
        );

        return $list;
    }

    /**
     * @return ProductManagerInterface
     */
    private function getProductManager()
    {
        return $this->get('sulu_product.product_manager');
    }

    /**
     * @return ProductLocaleManager
     */
    private function getProductLocaleManager()
    {
        return $this->get('sulu_product.product_locale_manager');
    }

    /**
     * @return ProductVariantAttributeManager
     */
    private function getVariantAttributeManager()
    {
        return $this->get('sulu_product.product_variant_attribute_manager');
    }

    /**
     * @return ProductFactoryInterface
     */
    private function getProductFactory()
    {
        return $this->getContainer()->get('sulu_product.product_factory');
    }
}
