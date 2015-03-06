<?php

/*
 * This file is part of the Doctrine\OrientDB package.
 *
 * (c) Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * (c) David Funaro <ing.davidino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Manager class.
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Alessandro Nadalin <alessandro.nadalin@gmail.com>
 * @author     David Funaro <ing.davidino@gmail.com>
 */

namespace Doctrine\ODM\OrientDB;

use Doctrine\Common\EventManager;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ODM\OrientDB\Caster\CastingMismatchException;
use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Hydrator\Dynamic\DynamicHydratorFactory;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory as MetadataFactory;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Mapping\ClusterMap;
use Doctrine\ODM\OrientDB\Proxy\Proxy;
use Doctrine\ODM\OrientDB\Proxy\ProxyFactory;
use Doctrine\ODM\OrientDB\Types\Rid;
use Doctrine\OrientDB\Binding\BindingInterface;
use Doctrine\OrientDB\Exception;
use Doctrine\OrientDB\Query\Query;

class DocumentManager implements ObjectManager
{
    /**
     * @var Configuration
     */
    protected $configuration;

    /**
     * @var BindingInterface
     */
    protected $binding;

    /**
     * @var ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * The DocumentRepository instances.
     *
     * @var DocumentRepository[]
     */
    private $repositories = [];

    /**
     * @var ProxyFactory
     */
    protected $proxyFactory;

    /**
     * @var UnitOfWork
     */
    protected $uow;

    /**
     * @var HydratorFactoryInterface
     */
    protected $hydratorFactory;

    /**
     * @var ClusterMap
     */
    protected $clusterMap;

    /**
     * Instantiates a new DocumentMapper, injecting the $mapper that will be used to
     * hydrate record retrieved through the $binding.
     *
     * @param BindingInterface $binding
     * @param Configuration    $configuration
     * @param EventManager     $eventManager
     *
     * @throws ConfigurationException
     */
    public function __construct(BindingInterface $binding, Configuration $configuration = null, EventManager $eventManager = null) {
        $this->binding       = $binding;
        $this->configuration = $configuration;
        $this->eventManager  = $eventManager ?: new EventManager();

        $metadataFactoryClassName = $this->configuration->getClassMetadataFactoryName();
        $this->metadataFactory    = new $metadataFactoryClassName();
        $this->metadataFactory->setDocumentManager($this);
        $this->metadataFactory->setConfiguration($this->configuration);
        if ($cacheDriver = $this->configuration->getMetadataCacheImpl()) {
            $this->metadataFactory->setCacheDriver($cacheDriver);
        }

        if (!$hf = $this->configuration->getHydratorFactoryImpl()) {
            $hf = new DynamicHydratorFactory();
        }
        $this->hydratorFactory = $hf;
        $hf->setDocumentManager($this);

        $this->clusterMap = new ClusterMap($this->binding, $this->configuration->getMetadataCacheImpl());

        $this->uow          = new UnitOfWork($this, $this->eventManager, $this->hydratorFactory);
        $this->proxyFactory = new ProxyFactory(
            $this,
            $configuration->getProxyDirectory(),
            $configuration->getProxyNamespace(),
            $configuration->getAutoGenerateProxyClasses()
        );


    }

    /**
     * @todo to implement/test
     *
     * @param \stdClass $object
     */
    public function detach($object) {
        throw new \Exception();
    }

    /**
     * Executes a $query against OrientDB.
     *
     * This method should be used to execute queries which should not return a
     * result (UPDATE, INSERT) or to retrieve multiple objects: to retrieve a
     * single record look at ->find*() methods.
     *
     * @param  Query $query
     *
     * @return array|Mixed
     */
    public function execute(Query $query, $fetchPlan = null) {
        return $this->getUnitOfWork()->execute($query, $fetchPlan);
    }

    /**
     * Returns a reference to an entity. It will be lazily and transparently
     * loaded if anything other than the identifier is touched.
     *
     * @param $rid
     *
     * @return Proxy
     */
    public function getReference($rid) {
        $oclass = $this->clusterMap->identifyClass(new RID($rid));
        $md     = $this->metadataFactory->getMetadataForOClass($oclass);

        if ($document = $this->uow->tryGetById($rid, $md)) {
            return $document;
        }

        $document = $this->proxyFactory->getProxy($md->name, [$md->getRidPropertyName() => $rid]);
        $this->uow->registerManaged($document, $rid, []);

        return $document;
    }

    /**
     * @inheritdoc
     */
    public function find($className, $id) {
        return $this->getRepository($className)->find($id);
    }

    /**
     * @param string $className
     * @param string $id
     * @param string $fetchPlan
     *
     * @return mixed|null
     * @throws Exception
     */
    public function findWithPlan($className, $id, $fetchPlan = '*:0') {
        return $this->getRepository($className)->findWithPlan($id, $fetchPlan);
    }

    /**
     * Queries for an object with the given $rid.
     *
     * If $lazy loading is used, all of this won't be executed unless the
     * returned Proxy object is called via __invoke, e.g.:
     *
     * <code>
     *   $lazyLoadedRecord = $manager->find('1:1', true);
     *
     *   $record = $lazyLoadedRecord();
     * </code>
     *
     * @param  string $rid
     * @param  string $fetchPlan
     *
     * @return Proxy|object
     * @throws OClassNotFoundException|Exception
     */
    public function findByRid($rid, $fetchPlan = '*:0') {
        $class = $this->clusterMap->identifyClass(new Rid($rid));
        $md    = $this->metadataFactory->getMetadataForOClass($class);

        return $this->findWithPlan($md->name, $rid, $fetchPlan);
    }

    /**
     * @todo to implement/test
     *
     * @param $document
     */
    public function flush($document = null) {
        $this->getUnitOfWork()->commit($document);
    }

    /**
     * Gets the $class Metadata.
     *
     * @param   string $class
     *
     * @return \Doctrine\ODM\OrientDB\Mapping\ClassMetadata
     */
    public function getClassMetadata($class) {
        return $this->getMetadataFactory()->getMetadataFor($class);
    }

    /**
     * Returns the ProxyFactory associated with this document manager.
     *
     * @return ProxyFactory
     */
    public function getProxyFactory() {
        return $this->proxyFactory;
    }

    /**
     * Gets the EventManager associated with this document manager.
     *
     * @return \Doctrine\Common\EventManager
     */
    public function getEventManager() {
        return $this->eventManager;
    }

    /**
     * Returns the Metadata factory associated with this document manager.
     *
     * @return MetadataFactory
     */
    public function getMetadataFactory() {
        return $this->metadataFactory;
    }

    /**
     * Returns the hydrator factory associated with this document manager
     * @return HydratorFactoryInterface
     */
    public function getHydratorFactory() {
        return $this->hydratorFactory;
    }

    /**
     * @return ClusterMap
     */
    public function getClusterMap() {
        return $this->clusterMap;
    }

    /**
     * Returns the unit of work associated with this document manager
     *
     * @return UnitOfWork
     */
    public function getUnitOfWork() {
        return $this->uow;
    }

    /**
     * @inheritdoc
     */
    public function getRepository($documentName) {
        $documentName = ltrim($documentName, '\\');

        if (isset($this->repositories[$documentName])) {
            return $this->repositories[$documentName];
        }

        $metadata                  = $this->getClassMetadata($documentName);
        $customRepositoryClassName = $metadata->customRepositoryClassName;

        if ($customRepositoryClassName !== null) {
            $repository = new $customRepositoryClassName($this, $this->uow, $metadata);
        } else {
            $repository = new DocumentRepository($this, $this->uow, $metadata);
        }

        $this->repositories[$documentName] = $repository;

        return $repository;
    }

    /**
     * @inheritdoc
     */
    public function initializeObject($obj) {
        throw new \Exception();
    }

    /**
     * @inheritdoc
     */
    public function merge($object) {
        throw new \Exception();
    }

    /**
     * @inheritdoc
     */
    public function persist($document) {
        if (!is_object($document)) {
            throw new \InvalidArgumentException(gettype($document));
        }
        $this->getUnitOfWork()->persist($document);
    }

    /**
     * @inheritdoc
     */
    public function remove($object) {
        $this->getUnitOfWork()->remove($object);
    }

    /**
     * @inheritdoc
     */
    public function refresh($object) {
        $this->getUnitOfWork()->refresh($object);
    }

    /**
     * @inheritdoc
     */
    public function clear($class = null) {
        $this->uow->clear($class);
    }

    /**
     * @inheritdoc
     */
    public function contains($object) {
        throw new \Exception();
    }


    /**
     * Returns the binding instance used to communicate OrientDB.
     *
     * @return BindingInterface
     */
    public function getBinding() {
        return $this->binding;
    }

    /**
     * Returns the Configuration of the Manager
     *
     * @return Configuration
     */
    public function getConfiguration() {
        return $this->configuration;
    }
}
