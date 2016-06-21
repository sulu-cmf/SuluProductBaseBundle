<?php
/*
 * This file is part of the Sulu CMF.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Product;

use Sulu\Bundle\ProductBundle\Api\Status;
use Sulu\Bundle\ProductBundle\Entity\StatusRepository;

/**
 * Manager responsible for product statuses
 * @package Sulu\Bundle\ProductBundle\Product
 */
class StatusManager
{
    /**
     * @var StatusRepository
     */
    private $statusRepository;

    public function __construct(StatusRepository $statusRepository)
    {
        $this->statusRepository = $statusRepository;
    }

    /**
     * @param string $locale
     * @return null|self[]
     */
    public function findAll($locale)
    {
        $statuses = $this->statusRepository->findAllByLocale($locale);

        array_walk(
            $statuses,
            function (&$status) use ($locale) {
                $status = new Status($status, $locale);
            }
        );

        return $statuses;
    }

    /**
     * @param integer $id
     *
     * @return null|self
     */
    public function find($id)
    {
        return $this->statusRepository->find($id);
    }
} 
