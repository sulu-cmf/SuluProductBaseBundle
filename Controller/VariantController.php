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

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Product\ProductManagerInterface;
use Sulu\Bundle\ProductBundle\Product\ProductVariantManagerInterface;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller is responsible for managing variants to a specific product.
 */
class VariantController extends RestController implements ClassResourceInterface
{
    protected static $entityName = 'SuluProductBundle:Product';

    protected static $entityKey = 'products';

    /**
     * Retrieves and shows the variant with the given ID for the parent product.
     *
     * @param Request $request
     * @param int $parentId
     * @param int $variantId
     *
     * @return Response
     */
    public function getAction(Request $request, $parentId, $variantId)
    {
        $locale = $this->getLocale($request);
        $view = $this->responseGetById(
            $variantId,
            function ($id) use ($locale, $parentId) {
                $product = $this->getProductManager()->findByIdAndLocale($id, $locale);

                if ($product !== null && $product->getParent() && $product->getParent()->getId() == $parentId) {
                    return $product;
                } else {
                    return null;
                }
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns a list of product variants for the requested product.
     *
     * @param Request $request
     * @param int $parentId
     *
     * @return Response
     */
    public function cgetAction(Request $request, $parentId)
    {
        if ($request->get('flat') == 'true') {
            /** @var RestHelperInterface $restHelper */
            $restHelper = $this->get('sulu_core.doctrine_rest_helper');

            /** @var DoctrineListBuilderFactory $factory */
            $factory = $this->get('sulu_core.doctrine_list_builder_factory');

            $listBuilder = $factory->create(self::$entityName);

            $fieldDescriptors = $this->getProductManager()->getFieldDescriptors($this->getLocale($request));

            $restHelper->initializeListBuilder(
                $listBuilder,
                $fieldDescriptors
            );

            $listBuilder->where($fieldDescriptors['parent'], $parentId);

            // Only add group by id if categories are processed.
            $fieldsParam = $request->get('fields');
            $fields = explode(',', $fieldsParam);
            if (isset($filter['categories']) ||
                !$fieldsParam ||
                array_search('categories', $fields) !== false
            ) {
                $listBuilder->addGroupBy($fieldDescriptors['id']);
            }

            $list = new ListRepresentation(
                $listBuilder->execute(),
                self::$entityKey,
                'get_products',
                $request->query->all(),
                $listBuilder->getCurrentPage(),
                $listBuilder->getLimit(),
                $listBuilder->count()
            );
        } else {
            $list = new CollectionRepresentation(
                $this->getProductManager()->findAllByLocale($this->getLocale($request)),
                self::$entityKey
            );
        }

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Adds a new variant to given product.
     *
     * @param Request $request
     * @param int $parentId
     *
     * @return Response
     */
    public function postAction(Request $request, $parentId)
    {
        $requestData = $request->request->all();
        $userId = $this->getUser()->getId();

        try {
            $variant = $this->getProductVariantManager()->createVariant(
                $parentId,
                $requestData,
                $this->getLocale($request),
                $userId
            );

            $view = $this->view($variant, 200);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Removes a variant of product.
     *
     * @param int $parentId
     * @param int $variantId
     *
     * @return Response
     */
    public function deleteAction($parentId, $variantId)
    {
        try {
            $this->getProductVariantManager()->deleteVariant($parentId, $variantId);

            $view = $this->view(null, 204);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 404);
        }

        return $this->handleView($view);
    }

    /**
     * @return ProductManagerInterface
     */
    private function getProductManager()
    {
        return $this->get('sulu_product.product_manager');
    }

    /**
     * @return ProductVariantManagerInterface
     */
    private function getProductVariantManager()
    {
        return $this->get('sulu_product.product_variant_manager');
    }
} 
