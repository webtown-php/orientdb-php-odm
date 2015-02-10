<?php

namespace Doctrine\ODM\OrientDB\Hydrator\Dynamic;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Inflector\Inflector;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\HydratorInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\PersistentCollection;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;

class DynamicHydrator implements HydratorInterface
{
    const ORIENT_PROPERTY_CLASS = '@class';

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
            $property = $mapping['name'];
            $propertyValue = property_exists($data, $property) ? $data->$property : null;

            if (!isset($mapping['association'])) {
                if ($propertyValue === null) {
                    continue;
                }

                $type = Type::getType($mapping['type']);
                $value = $type->convertToPHPValue($propertyValue);
                $this->metadata->setFieldValue($document, $fieldName, $value);
                $hydratedData[$property] = $value;
                continue;
            }

            if ($mapping['association'] & ClassMetadata::TO_MANY) {
                $coll = new PersistentCollection(new ArrayCollection(), $this->dm, $this->uow);
                $coll->setOwner($document, $this->metadata->fieldMappings[$mapping['fieldName']]);
                $coll->setInitialized(false);
                if ($propertyValue) {
                    $coll->setData($propertyValue);
                }
                $this->metadata->setFieldValue($document, $fieldName, $coll);
                $hydratedData[$property] = $coll;
                continue;
            }

            if ($mapping['association'] === ClassMetadata::LINK) {
                if ($propertyValue === null) {
                    continue;
                }
                $link = $this->dm->getReference($propertyValue);
                $this->metadata->setFieldValue($document, $fieldName, $link);
                $hydratedData[$property] = $link;
            }
        }

        return $hydratedData;
    }

    /**
     * Hydrates the value
     *
     * @param       $value
     * @param array $mapping
     *
     * @return mixed|null
     * @throws \Exception
     */
    protected function hydrateValue($value, array $mapping) {
        if (isset($mapping['type'])) {
            try {
                $value = $this->castProperty($mapping, $value);
            } catch (\Exception $e) {
                if ($mapping['nullable']) {
                    $value = null;
                } else {
                    throw $e;
                }
            }
        }

        return $value;
    }

    /**
     * Casts a value according to how it was annotated.
     *
     * @param  array $mapping
     * @param  mixed $propertyValue
     *
     * @return mixed
     */
    protected function castProperty(array $mapping, $propertyValue) {
        $method = 'cast' . Inflector::classify($mapping['type']);

        $this->getCaster()->setValue($propertyValue);
        $this->getCaster()->setProperty('mapping', $mapping);
        $this->verifyCastingSupport($this->getCaster(), $method, $mapping['type']);

        $this->castedProperties[$propertyId] = $this->getCaster()->$method();
    }

    protected function getCastedPropertyCacheKey($type, $value) {
        return get_class() . "_casted_property_" . $type . "_" . serialize($value);
    }
}