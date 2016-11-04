<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\EntityListener;

use Doctrine\Common\Persistence\Event\LifecycleEventArgs;
use Sulu\Bundle\ProductBundle\Entity\ProductTranslation;
use Sulu\Bundle\RouteBundle\Manager\RouteManagerInterface;

/**
 * Entity listener for product translation class.
 */
class ProductTranslationEntityListener
{
    /**
     * @param RouteManagerInterface $routeManager
     */
    public function __construct(
        RouteManagerInterface $routeManager
    )
    {
        $this->routeManager = $routeManager;
    }

    /**
     * {@inheritdoc}
     *
     * @param ProductTranslation $productTranslation
     * @param LifecycleEventArgs $event
     */
    public function postPersist(ProductTranslation $productTranslation, LifecycleEventArgs $event) {
        // TODO: Create route for entity
//        $this->routeManager->create($productTranslation);

//        $event->getObjectManager()->flush();
    }
}
