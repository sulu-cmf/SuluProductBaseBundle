<?php

/*
 * This file is part of Sulu.
 *
 * (c) MASSIVE ART WebServices GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sulu\Bundle\ProductBundle\DataFixtures\ORM\ProductStatuses;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Sulu\Bundle\ProductBundle\Entity\Status;
use Sulu\Bundle\ProductBundle\Entity\StatusTranslation;

class LoadProductStatuses implements FixtureInterface, OrderedFixtureInterface
{
    private static $translations = ['de', 'de_ch', 'en'];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        // force id = 1
        $metadata = $manager->getClassMetaData(get_class(new Status()));
        $metadata->setIdGeneratorType(\Doctrine\ORM\Mapping\ClassMetadata::GENERATOR_TYPE_NONE);

        $i = 1;
        $file = dirname(__FILE__) . '/../../product-statuses.xml';
        $doc = new \DOMDocument();
        $doc->load($file);

        $xpath = new \DOMXpath($doc);
        $elements = $xpath->query('/product-statuses/product-status');

        if (!is_null($elements)) {
            /** @var $element DOMNode */
            foreach ($elements as $element) {
                $status = new Status();
                $status->setId($i);
                $children = $element->childNodes;
                /** @var $child DOMNode */
                foreach ($children as $child) {
                    if (isset($child->nodeName) && (in_array($child->nodeName, self::$translations))) {
                        $translation = new StatusTranslation();
                        $translation->setLocale($child->nodeName);
                        $translation->setName($child->nodeValue);
                        $translation->setStatus($status);
                        $manager->persist($translation);
                    }
                }
                $manager->persist($status);
                ++$i;
            }
        }
        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 1;
    }
}
