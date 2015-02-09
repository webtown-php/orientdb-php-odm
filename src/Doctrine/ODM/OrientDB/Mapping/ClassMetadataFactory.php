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
    /** @var DocumentManager The DocumentManager instance */
    private $dm;

    /** @var Configuration The Configuration instance */
    private $config;

    /** @var \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver The used metadata driver. */
    private $driver;

    /** @var \Doctrine\Common\EventManager The event manager instance */
    private $evm;

    /**
     * Tries to find the PHP class mapping Doctrine\OrientDB's $OClass in each of the
     * directories where the documents are stored.
     *
     * @param  string $OClass
     *
     * @return ClassMetadata
     * @throws \Doctrine\ODM\OrientDB\OClassNotFoundException
     */
    public function getMetadataForOClass($OClass) {
        $cache = $this->getCacheDriver();
        if ($cache) {
            if (!($cached = $cache->fetch('oclassmap' . $this->cacheSalt))) {
                $cached = [];
                /** @var ClassMetadata $md */
                foreach ($this->getAllMetadata() as $md) {
                    $cached[$md->getOrientClass()] = $md->getName();
                }
                $cache->save('oclassmap' . $this->cacheSalt, $cached);
            }
            if (isset($cached[$OClass])) {
                return $this->getMetadataFor($cached[$OClass]);
            }
        } else {
            /** @var ClassMetadata $md */
            foreach ($this->getAllMetadata() as $md) {
                if ($OClass === $md->getOrientClass()) {
                    return $md;
                }
            }
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
     * Lazy initialization of this stuff, especially the metadata driver,
     * since these are not needed at all when a metadata cache is active.
     *
     * @return void
     */
    protected function initialize() {
        $this->driver      = $this->config->getMetadataDriverImpl();
        $this->evm         = $this->dm->getEventManager();
        $this->initialized = true;
    }

    /**
     * Gets the fully qualified class-name from the namespace alias.
     *
     * @param string $namespaceAlias
     * @param string $simpleClassName
     *
     * @return string
     */
    protected function getFqcnFromAlias($namespaceAlias, $simpleClassName) {
        return $this->config->getDocumentNamespace($namespaceAlias) . '\\' . $simpleClassName;
    }

    /**
     * Returns the mapping driver implementation.
     *
     * @return \Doctrine\Common\Persistence\Mapping\Driver\MappingDriver
     */
    protected function getDriver() {
        return $this->driver;
    }

    /**
     * Wakes up reflection after ClassMetadata gets unserialized from cache.
     *
     * @param ClassMetadataInfo $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    protected function wakeupReflection(ClassMetadataInfo $class, ReflectionService $reflService) {
        /** @var ClassMetadata $class */
        $class->wakeupReflection($reflService);
    }

    /**
     * Initializes Reflection after ClassMetadata was constructed.
     *
     * @param ClassMetadataInfo $class
     * @param ReflectionService $reflService
     *
     * @return void
     */
    protected function initializeReflection(ClassMetadataInfo $class, ReflectionService $reflService) {
        /** @var ClassMetadata $class */
        $class->initializeReflection($reflService);
    }

    /**
     * Checks whether the class metadata is an entity.
     *
     * This method should return false for mapped superclasses or embedded classes.
     *
     * @param ClassMetadataInfo $class
     *
     * @return boolean
     */
    protected function isEntity(ClassMetadataInfo $class) {
        return true;
    }

    /**
     * Actually loads the metadata from the underlying metadata.
     *
     * @param ClassMetadata      $class
     * @param ClassMetadata|null $parent
     * @param bool               $rootEntityFound
     * @param array              $nonSuperclassParents All parent class names
     *                                                 that are not marked as mapped superclasses.
     *
     * @throws MappingException
     */
    protected function doLoadMetadata($class, $parent, $rootEntityFound, array $nonSuperclassParents) {
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
     * Creates a new ClassMetadata instance for the given class name.
     *
     * @param string $className
     *
     * @return ClassMetadata
     */
    protected function newClassMetadataInstance($className) {
        return new ClassMetadata($className);
    }
}