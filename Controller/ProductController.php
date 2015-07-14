<?php
/*
 * This file is part of the Sulu CMS.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Controller;

use FOS\RestBundle\Routing\ClassResourceInterface;
use Hateoas\Representation\CollectionRepresentation;
use Sulu\Bundle\ProductBundle\Api\Product;
use Sulu\Bundle\ProductBundle\Product\Exception\MissingProductAttributeException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductChildrenExistException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductDependencyNotFoundException;
use Sulu\Bundle\ProductBundle\Product\Exception\ProductNotFoundException;
use Sulu\Bundle\ProductBundle\Product\ProductManagerInterface;
use Sulu\Component\Rest\Exception\EntityIdAlreadySetException;
use Sulu\Component\Rest\Exception\EntityNotFoundException;
use Sulu\Component\Rest\Exception\MissingArgumentException;
use Sulu\Component\Rest\Exception\RestException;
use Sulu\Component\Rest\ListBuilder\Doctrine\DoctrineListBuilderFactory;
use Sulu\Component\Rest\ListBuilder\ListRepresentation;
use Sulu\Component\Rest\ListBuilder\ListRestHelper;
use Sulu\Component\Rest\RestController;
use Sulu\Component\Rest\RestHelperInterface;
use Sulu\Component\Security\SecuredControllerInterface;
use Symfony\Component\HttpFoundation\Request;
use FOS\RestBundle\Controller\Annotations\Get;

class ProductController extends RestController implements ClassResourceInterface, SecuredControllerInterface
{
    protected static $entityName = 'SuluProductBundle:Product';

    protected static $entityKey = 'products';

    /**
     * Returns the repository object for AdvancedProduct
     *
     * @return ProductManagerInterface
     */
    protected function getManager()
    {
        return $this->get('sulu_product.product_manager');
    }

    /**
     * returns all fields that can be used by list
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function fieldsAction(Request $request)
    {
        return $this->handleView(
            $this->view(
                array_values(
                    array_diff_key(
                        $this->getManager()->getFieldDescriptors($this->getLocale($request)),
                        array(
                            'statusId' => false,
                            'categoryIds' => false
                        )
                    )
                ),
                200
            )
        );
    }

    /**
     * Retrieves and shows a product with the given ID
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id product ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);
        $view = $this->responseGetById(
            $id,
            function ($id) use ($locale) {
                /** @var Product $product */
                $product = $this->getManager()->findByIdAndLocale($id, $locale);

                return $product;
            }
        );

        return $this->handleView($view);
    }

    /**
     * Returns a list of products
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function cgetAction(Request $request)
    {
        $filter = $this->getManager()->getFilters($request);

        if ($request->get('flat') == 'true') {
            $filterFieldDescriptors = $this->getManager()->getFilterFieldDescriptors();
            $fieldDescriptors = $this->getManager()->getFieldDescriptors(
                $this->getLocale($request)
            );

            // TODO: get ids by filters

            // TODO: create fast count function 

            $list = $this->flatResponse(
                $request,
                $filter,
                $filterFieldDescriptors,
                $fieldDescriptors,
                static::$entityName
            );
        } elseif ($request->get('ids') !== '') {
            $list = new CollectionRepresentation(
                $this->getManager()->findAllByIdsAndLocale($this->getLocale($request), $request->get('ids')),
                self::$entityKey
            );
        } else {
            $list = new CollectionRepresentation(
                $this->getManager()->findAllByLocale($this->getLocale($request), $filter),
                self::$entityKey
            );
        }

        $view = $this->view($list, 200);

        return $this->handleView($view);
    }

    /**
     * Processes the request for a flat response
     *
     * @param Request $request
     *
     * @return list
     */
    protected function flatResponse($request, $filter, $filterFieldDescriptors, $fieldDescriptors, $entityName)
    {
        /** @var RestHelperInterface $restHelper */
        $restHelper = $this->get('sulu_core.doctrine_rest_helper');

        /** @var DoctrineListBuilderFactory $factory */
        $factory = $this->get('sulu_core.doctrine_list_builder_factory');

        $listRestHelper = new ListRestHelper($request);

        $listBuilder = $factory->create($entityName);

        $restHelper->initializeListBuilder(
            $listBuilder,
            $fieldDescriptors
        );

//        foreach ($filter as $key => $value) {
//            if (is_array($value)) {
//                $listBuilder->in($filterFieldDescriptors[$key], $value);
//            } else {
//                $listBuilder->where($filterFieldDescriptors[$key], $value);
//            }
//        }

        // get total count
        $locale = $this->getLocale($request);
        // TODO: test search - take search-fields into account
        $filter['search'] = $listRestHelper->getSearchPattern($request);
        $filter['searchFields'] = $listRestHelper->getSearchFields();
        // filter null vars
        $filter = array_filter($filter);
        $count = $this->getManager()->countByFilter($filter, $locale);

        $filter['limit'] = $listRestHelper->getLimit($request);
        $filter['page'] = $listRestHelper->getPage($request);
        $sortColumn = $listRestHelper->getSortColumn();
        if ($sortColumn) {
//            $filter['orderBy'] = $this->getManager()->getFieldDescriptor('changed')->getEntityName . '' . $sortColumn . ' ' . $listRestHelper->getSortOrder();
        }

        // find product ids
        $ids = $this->getManager()->findIdsByFilter($filter, $locale);

        // filter result by ids found in previous query
        $filter['ids'] = $ids;
        $listBuilder->in($fieldDescriptors['id'], $filter['ids']);

        // TODO: total-count

        // if "categories" are requested - group by id
        if (isset($filter['categories'])) {
            $listBuilder->addGroupBy($fieldDescriptors['id']);
        }

        $list = new ListRepresentation(
            $listBuilder->execute(),
            self::$entityKey,
            'get_products',
            $request->query->all(),
            $listBuilder->getCurrentPage(),
            $listBuilder->getLimit(),
            $count
        );

        return $list;
    }

    /**
     * Change a product entry by the given product id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id product ID
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function putAction(Request $request, $id)
    {
        try {
            $product = $this->getManager()->save(
                $request->request->all(),
                $this->getLocale($request),
                $this->getUser()->getId(),
                $id
            );

            $view = $this->view($product, 200);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 404);
        } catch (ProductDependencyNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingProductAttributeException $exc) {
            $exception = new MissingArgumentException(static::$entityName, $exc->getAttribute());
            $view = $this->view($exception->toArray(), 400);
        } catch (EntityIdAlreadySetException $exc) {
            $view = $this->view($exc->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Creates and stores a new product.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function postAction(Request $request)
    {
        try {
            $product = $this->getManager()->save(
                $request->request->all(),
                $this->getLocale($request),
                $this->getUser()->getId()
            );

            $view = $this->view($product, 200);
        } catch (ProductDependencyNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 400);
        } catch (MissingProductAttributeException $exc) {
            $exception = new MissingArgumentException(static::$entityName, $exc->getAttribute());
            $view = $this->view($exception->toArray(), 400);
        }

        return $this->handleView($view);
    }

    /**
     * Delete a product with the given id.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id product id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function deleteAction(Request $request, $id)
    {
        $locale = $this->getLocale($request);

        $delete = function ($id) use ($locale) {
            try {
                $this->getManager()->delete($id, $this->getUser()->getId());
            } catch (ProductChildrenExistException $exc) {
                throw new RestException('Deletion not allowed, because the product has sub products', 400);
            }
        };
        $view = $this->responseDelete($id, $delete);

        return $this->handleView($view);
    }

    /**
     * {@inheritDoc}
     */
    public function getSecurityContext()
    {
        return 'sulu.product.products';
    }

    /**
     * Make a partial update of a product
     *
     * @param Request $request
     * @param $id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function patchAction(Request $request, $id)
    {
        try {
            $product = $this->getManager()->partialUpdate(
                $request->request->all(),
                $this->getLocale($request),
                $this->getUser()->getId(),
                $id
            );

            $view = $this->view($product, 200);
        } catch (ProductNotFoundException $exc) {
            $exception = new EntityNotFoundException($exc->getEntityName(), $exc->getId());
            $view = $this->view($exception->toArray(), 404);
        }

        return $this->handleView($view);
    }
}
