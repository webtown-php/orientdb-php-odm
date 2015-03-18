<?php

namespace Doctrine\ODM\OrientDB\Mapping;

use Doctrine\Common\Persistence\Mapping\AbstractClassMetadataFactory;
use Doctrine\Common\Persistence\Mapping\ClassMetadata as ClassMetadataInfo;
use Doctrine\Common\Persistence\Mapping\ReflectionService;
use Doctrine\ODM\OrientDB\Configuration;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Event\LoadClassMetadataEventArgs;
use Doctrine\ODM\OrientDB\Events;
use Doctrine\ODM\OrientDB\OClassNotFoundException;

class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /**
     * @var DocumentManager The DocumentManager instance
     */
    private $dm;

    /**
     * @var Configuration The Configuration instance
     */
    private $config;

    /**
     * @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver The used metadata driver.
     */
    private $driver;

    /**
     * @var \Doctrine\Common\EventManager The event manager instance
     */
    private $evm;

    /**
     * Tries to find the PHP class mapping Doctrine\OrientDB's $OClass in each of the
     * directories where the documents are stored.
     *
     * @param  string $OClass
     *
     * @return ClassMetadata
     * @throws MappingException
     * @throws OClassNotFoundException
     */
    public function getMetadataForOClass($OClass) {
        $cache = $this->getCacheDriver();
        if (!($cached = $cache->fetch('oclassmap' . $this->cacheSalt))) {
            $cached = [];
            /** @var ClassMetadata $md */
            foreach ($this->getAllMetadata() as $md) {
                $orientClass = $md->getOrientClass();
                if (isset($cached[$orientClass])) {
                    $existing = $cached[$orientClass];
                    throw MappingException::duplicateOrientClassMapping($orientClass, $existing, $md->name);
                }
                $cached[$orientClass] = $md->getName();
            }
            $cache->save('oclassmap' . $this->cacheSalt, $cached);
        }
        if (isset($cached[$OClass])) {
            return $this->getMetadataFor($cached[$OClass]);
        }

        throw new OClassNotFoundException($OClass);
    }

    /**
     * Sets the DocumentManager instance for this class.
     *
     * @param DocumentManager $dm The DocumentManager instance
     */
    public function setDocumentManager(DocumentManager $dm) {
        $this->dm = $dm;
    }

    /**
     * Sets the Configuration instance
     *
     * @param Configuration $config
     */
    public function setConfiguration(Configuration $config) {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    protected function initialize() {
        $this->driver      = $this->config->getMetadataDriverImpl();
        $this->evm         = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /**
     * @inheritdoc
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName) {
        return $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * @inheritdoc
     */
    protected function getDriver() {
        return $this->driver;
    }

    /**
     * @inheritdoc
     */
    protected function wakeupReflection(ClassMetadataInfo $class, ReflectionService $reflService) {
        /** @var ClassMetadata $class */
        $class->wakeupReflection($reflService);
    }

    /**
     * @inheritdoc
     */
    protected function initializeReflection(ClassMetadataInfo $class, ReflectionService $reflService) {
        /** @var ClassMetadata $class */
        $class->initializeReflection($reflService);
    }

    /**
     * @inheritdoc
     */
    protected function isEntity(ClassMetadataInfo $class) {
        /** @var ClassMetadata $class */
        return ($class->isMappedSuperclass || $class->isAbstract || $class->isEmbeddedDocument) === false;
    }

    /**
     * @inheritdoc
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents) {

        /** @var $class ClassMetadata */
        /** @var $parent ClassMetadata */
        if ($parent) {
            $this->addInheritedFields($class, $parent);
//            $this->addInheritedIndexes($class, $parent);
            $class->setIdentifier($parent->identifier);
            $class->setVersion($parent->version);
//            $class->setLifecycleCallbacks($parent->lifecycleCallbacks);
//            $class->setAlsoLoadMethods($parent->alsoLoadMethods);
            $class->setChangeTrackingPolicy($parent->changeTrackingPolicy);
            if ($parent->isMappedSuperclass) {
                $class->setCustomRepositoryClass($parent->customRepositoryClassName);
            }
        }

        try {
            $this->driver->loadMetadataForClass($class->getName(), $class);
        } catch (\ReflectionException $ex) {
            throw MappingException::reflectionFailure($class->getName(), $ex);
        }

        if ($this->evm->hasListeners(Events::loadClassMetadata)) {
            $eventArgs = new LoadClassMetadataEventArgs($class, $this->dm);
            $this->evm->dispatchEvent(Events::loadClassMetadata, $eventArgs);
        }
    }

    /**
     * Adds inherited fields to the subclass mapping.
     *
     * @param ClassMetadata $subClass
     * @param ClassMetadata $parentClass
     */
    private function addInheritedFields(ClassMetadata $subClass, ClassMetadata $parentClass) {
        foreach ($parentClass->fieldMappings as $fieldName => $mapping) {
            if (!isset($mapping['inherited']) && !$parentClass->isMappedSuperclass) {
                $mapping['inherited'] = $parentClass->name;
            }
            if (!isset($mapping['declared'])) {
                $mapping['declared'] = $parentClass->name;
            }
            $subClass->addInheritedFieldMapping($mapping);
        }
        foreach ($parentClass->reflFields as $name => $field) {
            $subClass->reflFields[$name] = $field;
        }
    }

    /**
     * @inheritdoc
     */
    protected function newClassMetadataInstance($className) {
        return new ClassMetadata($className);
    }
}