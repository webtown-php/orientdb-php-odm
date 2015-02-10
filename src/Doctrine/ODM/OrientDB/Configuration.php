<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\SimpleAnnotationReader;
use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Persistence\Mapping\Driver\MappingDriver;
use Doctrine\Common\Proxy\AbstractProxyFactory;
use Doctrine\ODM\OrientDB\Hydrator\Dynamic\DynamicHydratorFactory;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Mapping\Driver\AnnotationDriver;

/**
 * Class Configuration
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class Configuration
{
    private $_attributes;

    private $supportedPersisterStrategies = ['sql_batch'];

    public function __construct(array $options = []) {
        $defaults = [
            'proxy_namespace'           => 'Doctrine\ODM\OrientDB\Proxy',
            'proxy_autogenerate_policy' => AbstractProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS,
            'hydratorFactoryImpl'       => null,
        ];

        $this->_attributes = array_merge($defaults, $options);
    }

    /**
     * Adds a namespace under a certain alias.
     *
     * @param string $alias
     * @param string $namespace
     */
    public function addDocumentNamespace($alias, $namespace) {
        $this->_attributes['documentNamespaces'][$alias] = $namespace;
    }

    /**
     * Resolves a registered namespace alias to the full namespace.
     *
     * @param string $documentNamespaceAlias
     *
     * @return string
     * @throws MongoDBException
     */
    public function getDocumentNamespace($documentNamespaceAlias) {
        if (!isset($this->_attributes['documentNamespaces'][$documentNamespaceAlias])) {
            throw MongoDBException::unknownDocumentNamespace($documentNamespaceAlias);
        }

        return trim($this->_attributes['documentNamespaces'][$documentNamespaceAlias], '\\');
    }

    /**
     * Retrieves the list of registered document namespace aliases.
     *
     * @return array
     */
    public function getDocumentNamespaces() {
        return $this->_attributes['documentNamespaces'];
    }

    /**
     * Set the document alias map
     *
     * @param array $documentNamespaces
     *
     * @return void
     */
    public function setDocumentNamespaces(array $documentNamespaces) {
        $this->_attributes['documentNamespaces'] = $documentNamespaces;
    }

    public function getAttributes() {
        return $this->_attributes;
    }

    public function getProxyDirectory() {
        if (!isset($this->_attributes['proxy_dir'])) {
            throw ConfigurationException::missingKey('proxy_dir');
        }

        return $this->_attributes['proxy_dir'];
    }

    public function getProxyNamespace() {
        return $this->_attributes['proxy_namespace'];
    }

    public function getAutoGenerateProxyClasses() {
        return isset($this->_attributes['proxy_autogenerate_policy']) ? $this->_attributes['proxy_autogenerate_policy'] : null;
    }

    public function getMismatchesTolerance() {
        return isset($this->_attributes['mismatches_tolerance']) ? $this->_attributes['mismatches_tolerance'] : false;
    }

    /**
     * Set the class metadata factory class name.
     *
     * @param string $cmfName
     */
    public function setClassMetadataFactoryName($cmfName) {
        $this->_attributes['classMetadataFactoryName'] = $cmfName;
    }

    /**
     * Gets the class metadata factory class name.
     *
     * @return string
     */
    public function getClassMetadataFactoryName() {
        if (!isset($this->_attributes['classMetadataFactoryName'])) {
            $this->_attributes['classMetadataFactoryName'] = ClassMetadataFactory::class;
        }

        return $this->_attributes['classMetadataFactoryName'];
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param MappingDriver $driverImpl
     *
     * @todo Force parameter to be a Closure to ensure lazy evaluation
     *       (as soon as a metadata cache is in effect, the driver never needs to initialize).
     */
    public function setMetadataDriverImpl(MappingDriver $driverImpl) {
        $this->_attributes['metadataDriverImpl'] = $driverImpl;
    }

    /**
     * Add a new default annotation driver with a correctly configured annotation reader. If $useSimpleAnnotationReader
     * is true, the notation `@Document` will work, otherwise, the notation `@ODM\Document` will be supported.
     *
     * @param array $paths
     * @param bool  $useSimpleAnnotationReader
     *
     * @return AnnotationDriver
     */
    public function newDefaultAnnotationDriver($paths = array(), $useSimpleAnnotationReader = true) {
        AnnotationRegistry::registerFile(__DIR__ . '/Mapping/Annotations/DoctrineAnnotations.php');

        if ($useSimpleAnnotationReader) {
            // Register the ORM Annotations in the AnnotationRegistry
            $reader = new SimpleAnnotationReader();
            $reader->addNamespace('Doctrine\ODM\OrientDB\Mapping\Annotations');
            $cachedReader = new CachedReader($reader, new ArrayCache());

            return new AnnotationDriver($cachedReader, (array)$paths);
        }

        return new AnnotationDriver(
            new CachedReader(new AnnotationReader(), new ArrayCache()),
            (array)$paths
        );
    }

    /**
     * Gets the cache driver implementation that is used for the mapping metadata.
     *
     * @return MappingDriver
     */
    public function getMetadataDriverImpl() {
        return isset($this->_attributes['metadataDriverImpl'])
            ? $this->_attributes['metadataDriverImpl']
            : null;
    }

    /**
     * Gets the cache driver implementation that is used for metadata caching.
     *
     * @return \Doctrine\Common\Cache\Cache
     */
    public function getMetadataCacheImpl() {
        return isset($this->_attributes['metadataCacheImpl'])
            ? $this->_attributes['metadataCacheImpl']
            : null;
    }

    /**
     * Sets the cache driver implementation that is used for metadata caching.
     *
     * @param \Doctrine\Common\Cache\Cache $cacheImpl
     */
    public function setMetadataCacheImpl(Cache $cacheImpl) {
        $this->_attributes['metadataCacheImpl'] = $cacheImpl;
    }

    /**
     * Sets the hydrator factory implementation that is used for hydrating documents
     *
     * @param HydratorFactoryInterface $factoryImpl
     */
    public function setHydratorFactoryImpl(HydratorFactoryInterface $factoryImpl) {
        $this->_attributes['hydratorFactoryImpl'] = $factoryImpl;
    }

    /**
     * Gets the hydrator factory implementation
     *
     * @return HydratorFactoryInterface
     */
    public function getHydratorFactoryImpl() {
        return $this->_attributes['hydratorFactoryImpl'];
    }

    public function getPersisterStrategy() {
        if (isset($this->_attributes['persister_strategy'])) {
            $strategy = $this->_attributes['persister_strategy'];
            if (!in_array($strategy, $this->supportedPersisterStrategies)) {
                throw ConfigurationException::invalidPersisterStrategy($strategy, $this->supportedPersisterStrategies);
            }
        } else {
            $strategy = 'sql_batch';
        }

        return $strategy;
    }
}
