<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\Api;

use Hateoas\Configuration\Annotation\Relation;
use JMS\Serializer\Annotation\ExclusionPolicy;
use JMS\Serializer\Annotation\SerializedName;
use JMS\Serializer\Annotation\VirtualProperty;
use Sulu\Bundle\ProductBundle\Entity\AttributeSet as Entity;
use Sulu\Component\Rest\ApiWrapper;

/**
 * The Attribute set class which will be exported to the API.
 *
 * @Relation("self", href="expr('/api/admin/attribute-sets/' ~ object.getId())")
 * @ExclusionPolicy("all")
 */
class AttributeSet extends ApiWrapper
{
    /**
     * @param Entity $type
     * @param string $locale
     */
    public function __construct(Entity $type, $locale)
    {
        $this->entity = $type;
        $this->locale = $locale;
    }

    /**
     * The id of the type.
     *
     * @return int The id of the type
     * @VirtualProperty
     * @SerializedName("id")
     */
    public function getId()
    {
        return $this->entity->getId();
    }

    /**
     * The name of the type.
     *
     * @return int The name of the type
     * @VirtualProperty
     * @SerializedName("name")
     */
    public function getName()
    {
        return $this->entity->getTranslation($this->locale)->getName();
    }
}
