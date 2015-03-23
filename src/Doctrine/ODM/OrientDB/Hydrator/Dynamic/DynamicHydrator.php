<?php

namespace Doctrine\ODM\OrientDB\Hydrator\Dynamic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\HydratorException;
use Doctrine\ODM\OrientDB\Hydrator\HydratorInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;

class DynamicHydrator implements HydratorInterface
{
    const ORIENT_PROPERTY_CLASS = '@class';
    const ORIENT_PROPERTY_VERSION = '@version';
    const ORIENT_PROPERTY_TYPE = '@type';

    /**
     * @var DocumentManager
     */
    protected $dm;
    /**
     * @var UnitOfWork
     */
    protected $uow;
    /**
     * @var ClassMetadata
     */
    protected $metadata;

    /**
     * @param DocumentManager $dm
     * @param UnitOfWork      $uow
     * @param ClassMetadata   $metadata
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow, ClassMetadata $metadata) {
        $this->dm       = $dm;
        $this->uow      = $uow;
        $this->metadata = $metadata;
    }

    /**
     * @inheritdoc
     */
    function hydrate($document, $data, array $hints = []) {
        $hydratedData = [];

        foreach ($this->metadata->fieldMappings as $fieldName => $mapping) {
            $name          = $mapping['name'];
            $propertyValue = property_exists($data, $name) ? $data->{$name} : null;

            if (!isset($mapping['association'])) {
                if ($propertyValue === null) {
                    continue;
                }

                $type  = Type::getType($mapping['type']);
                $value = $type->convertToPHPValue($propertyValue);
                $this->metadata->setFieldValue($document, $fieldName, $value);
                $hydratedData[$fieldName] = $value;
                continue;
            }

            if ($mapping['association'] & ClassMetadata::TO_MANY) {
                $coll = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
                $coll->setOwner($document, $mapping);
                $coll->setInitialized(false);
                if ($propertyValue) {
                    $coll->setData($propertyValue);
                }
                $this->metadata->setFieldValue($document, $fieldName, $coll);
                $hydratedData[$fieldName] = $coll;
                continue;
            }

            if ($propertyValue === null) {
                continue;
            }

            if ($mapping['association'] === ClassMetadata::LINK) {
                if (is_string($propertyValue)) {
                    $link = $this->dm->getReference($propertyValue);
                } else {
                    $link = $this->uow->getOrCreateDocument($propertyValue);
                }
                $this->metadata->setFieldValue($document, $fieldName, $link);
                $hydratedData[$fieldName] = $link;
                continue;
            }

            if ($mapping['association'] === ClassMetadata::EMBED) {
                // an embed one must have @class, we would support generic JSON properties via another mapping type
                if (!property_exists($propertyValue, self::ORIENT_PROPERTY_CLASS)) {
                    throw new HydratorException(sprintf("missing @class for embedded property '%s'", $name));
                }
                $oclass           = $propertyValue->{self::ORIENT_PROPERTY_CLASS};
                $embeddedMetadata = $this->dm->getMetadataFactory()->getMetadataForOClass($oclass);
                $doc              = $embeddedMetadata->newInstance();
                $embeddedData     = $this->dm->getHydratorFactory()->hydrate($doc, $propertyValue, $hints);
                $this->uow->registerManaged($doc, null, $embeddedData);
                $this->metadata->setFieldValue($document, $fieldName, $doc);
                $hydratedData[$fieldName] = $doc;
                continue;
            }
        }

        return $hydratedData;
    }
}