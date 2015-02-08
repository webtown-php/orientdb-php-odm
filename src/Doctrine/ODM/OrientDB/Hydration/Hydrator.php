<?php

namespace Doctrine\ODM\OrientDB\Hydration;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\Caster\Caster;
use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\DocumentNotFoundException;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Mapping\ClusterMap;
use Doctrine\ODM\OrientDB\Proxy\Proxy;
use Doctrine\ODM\OrientDB\Proxy\ProxyFactory;
use Doctrine\ODM\OrientDB\Types\Rid;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Exception;
use Doctrine\OrientDB\Query\Query;

/**
 * Class Hydrator
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class Hydrator
{
    const ORIENT_PROPERTY_CLASS = '@class';

    protected $proxyFactory;
    protected $metadataFactory;
    protected $enableMismatchesTolerance = false;
    protected $binding;
    protected $uow;
    protected $cache;
    protected $caster;
    protected $castedProperties = array();
    protected $clusterMap;

    /**
     * @param DocumentManager $dm
     */
    public function __construct(DocumentManager $dm) {
        $this->proxyFactory    = $dm->getProxyFactory();
        $this->metadataFactory = $dm->getMetadataFactory();
        $this->binding         = $dm->getBinding();
        $this->uow             = $dm->getUnitOfWork();
        $this->clusterMap      = new ClusterMap($this->binding, $dm->getConfiguration()->getMetadataCacheImpl());
        $this->caster          = new Caster($this);

        $this->enableMismatchesTolerance($dm->getConfiguration()->getMismatchesTolerance());
    }

    /**
     * @param string[] $rids
     * @param string   $fetchPlan
     *
     * @return mixed
     */
    public function load(array $rids, $fetchPlan = null) {
        $query   = new Query($rids);
        $results = $this->binding->execute($query, $fetchPlan)->getResult();

        return $results;
    }

    /**
     * Takes an Doctrine\OrientDB JSON object and finds the class responsible to map that
     * object.
     * If the class is found, a new POPO is instantiated and the properties inside the
     * JSON object are filled accordingly.
     *
     * @param  \stdClass $orientObject
     * @param  Proxy     $proxy
     *
     * @return Proxy
     * @throws DocumentNotFoundException
     */
    public function hydrate(\stdClass $orientObject, Proxy $proxy = null) {
        $classProperty = static::ORIENT_PROPERTY_CLASS;

        if ($proxy) {
            /** @var ClassMetadata $metadata */
            $metadata = $this->getMetadataFactory()->getMetadataFor(ClassUtils::getClass($proxy));
            $this->fill($metadata, $proxy, $orientObject);

            return $proxy;

        } elseif (property_exists($orientObject, $classProperty)) {
            $orientClass = $orientObject->$classProperty;

            if ($orientClass) {
                $metadata = $this->getMetadataFactory()->getMetadataForOClass($orientClass);
                $document = $this->createDocument($metadata, $orientObject);

                return $document;
            }

            throw new DocumentNotFoundException(self::ORIENT_PROPERTY_CLASS . ' property empty.');
        }

        throw new DocumentNotFoundException(self::ORIENT_PROPERTY_CLASS . ' property not found.');
    }

    /**
     * Hydrates an array of documents.
     *
     * @param  array $collection
     *
     * @return ArrayCollection
     */
    public function hydrateCollection(array $collection) {
        $records = array();

        foreach ($collection as $key => $record) {
            if ($record instanceof \stdClass) {
                $records[$key] = $this->hydrate($record);
            } else {
                $records[$key] = $this->hydrateRid(new Rid($record));
            }
        }

        return new ArrayCollection($records);
    }

    public function hydrateRid(Rid $rid) {
        $orientClass = $this->clusterMap->identifyClass($rid);
        $metadata    = $this->getMetadataFactory()->getMetadataForOClass($orientClass);
        $class       = $metadata->getName();

        return $this->getProxyFactory()->getProxy($class, [$metadata->getRidPropertyName() => $rid->getValue()]);
    }

    /**
     * Returns the ProxyFactory to which the hydrator is attached.
     *
     * @return ProxyFactory
     */
    protected function getProxyFactory() {
        return $this->proxyFactory;
    }

    /**
     * Returns the MetadataFactor.
     *
     * @return ClassMetadataFactory
     */
    protected function getMetadataFactory() {
        return $this->metadataFactory;
    }

    protected function getUnitOfWork() {
        return $this->uow;
    }

    /**
     * Either tries to get the proxy
     *
     *
     * @param  ClassMetadata $metadata
     * @param  \stdClass     $orientObject
     *
     * @return object of type $class
     */
    protected function createDocument(ClassMetadata $metadata, \stdClass $orientObject) {
        $class = $metadata->getName();

        /**
         * when a record from OrientDB doesn't have a RID
         * it means it's an embedded object, which can not be
         * lazily loaded.
         */
        if (isset($orientObject->{'@rid'})) {
            $rid = new Rid($orientObject->{'@rid'});
            if ($this->getUnitOfWork()->isInIdentityMap($rid)) {
                $document = $this->getUnitOfWork()->getProxy($rid);
            } else {
                $document = $this->getProxyFactory()
                                 ->getProxy($class, [$metadata->getRidPropertyName() => $rid->getValue()]);
            }
        } else {
            $class    = $metadata->getName();
            $document = new $class;
        }

        $this->fill($metadata, $document, $orientObject);

        return $document;
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
        $propertyId = $this->getCastedPropertyCacheKey($mapping['type'], $propertyValue);

        if (!isset($this->castedProperties[$propertyId])) {
            $method = 'cast' . Inflector::classify($mapping['type']);

            $this->getCaster()->setValue($propertyValue);
            $this->getCaster()->setProperty('mapping', $mapping);
            $this->verifyCastingSupport($this->getCaster(), $method, $mapping['type']);

            $this->castedProperties[$propertyId] = $this->getCaster()->$method();
        }

        return $this->castedProperties[$propertyId];
    }

    protected function getCastedPropertyCacheKey($type, $value) {
        return get_class() . "_casted_property_" . $type . "_" . serialize($value);
    }

    /**
     * Returns the caching layer of the mapper.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    protected function getCache() {
        return $this->cache;
    }

    /**
     * Given an object and an Orient-object, it fills the former with the
     * latter.
     *
     * @param ClassMetadata $metadata
     * @param  object       $document
     * @param  \stdClass    $object
     *
     * @return object
     * @throws \Exception
     */
    protected function fill(ClassMetadata $metadata, $document, \stdClass $object) {
        $hydratedData = [];

        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            $property = $mapping['name'];

            if (property_exists($object, $property)) {
                $value                   = $this->hydrateValue($object->$property, $mapping);
                $hydratedData[$property] = $value;
                $metadata->setFieldValue($document, $fieldName, $value);
            }
        }

        // attach the original data for non-embedded documents
        if (isset($object->{'@rid'})) {
            $this->uow->registerManaged($document, $object->{'@rid'}, $hydratedData);
        }
        if ($document instanceof Proxy) {
            $document->__setInitialized(true);
        }

        return $document;
    }


    /**
     * Returns the caster instance.
     *
     * @return \Doctrine\ODM\OrientDB\Caster\Caster
     */
    protected function getCaster() {
        return $this->caster;
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
     * Sets whether the Hydrator should tolerate mismatches during hydration.
     *
     * @param bool $tolerate
     */
    public function enableMismatchesTolerance($tolerate) {
        $this->enableMismatchesTolerance = $tolerate;
    }

    /**
     * Checks whether the Hydrator throws exceptions or not when encountering an
     * mismatch error during hydration.
     *
     * @return bool
     */
    public function toleratesMismatches() {
        return (bool)$this->enableMismatchesTolerance;
    }

    /**
     * Verifies if the given $caster supports casting with $method.
     * If not, an exception is raised.
     *
     * @param  Caster $caster
     * @param  string $method
     * @param  string $annotationType
     *
     * @throws Exception
     */
    protected function verifyCastingSupport(Caster $caster, $method, $annotationType) {
        if (!method_exists($caster, $method)) {
            $message = sprintf(
                'You are trying to map a property which seems not to have a standard type (%s). Do you have a typo in your annotation?' .
                'If you think everything\'s ok, go check on %s class which property types are supported.',
                $annotationType,
                get_class($caster)
            );

            throw new Exception($message);
        }
    }

}