<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\Hydration\Hydrator;
use Doctrine\ODM\OrientDB\Persistence\ChangeSet;
use Doctrine\ODM\OrientDB\Persistence\SQLBatchPersister;
use Doctrine\ODM\OrientDB\Proxy\Proxy;
use Doctrine\ODM\OrientDB\Types\Rid;
use Doctrine\OrientDB\Query\Query;

/**
 * Class UnitOfWork
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class UnitOfWork
{
    /**
     * A document is in MANAGED state when its persistence is managed by a DocumentManager.
     */
    const STATE_MANAGED = 1;
    /**
     * A document is new if it has just been instantiated (i.e. using the "new" operator)
     * and is not (yet) managed by a DocumentManager.
     */
    const STATE_NEW = 2;
    /**
     * A detached document is an instance with a persistent identity that is not
     * (or no longer) associated with a DocumentManager (and a UnitOfWork).
     */
    const STATE_DETACHED = 3;
    /**
     * A removed document instance is an instance with a persistent identity,
     * associated with a DocumentManager, whose persistent state has been
     * deleted (or is scheduled for deletion).
     */
    const STATE_REMOVED = 4;

    private $manager;
    private $hydrator;
    private $identityMap = [];
    private $newDocuments = [];
    private $originalData = [];
    private $documentUpdates = [];
    private $documentInserts = [];
    private $documentRemovals = [];
    private $documentStates = [];

    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    public function commit($document = null)
    {
        if (null === $document) {
            $this->computeChangeSets();
        } elseif (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } elseif (is_array($document)) {
            foreach ($document as $object) {
                $this->computeSingleDocumentChangeSet($object);
            }
        }

        $changeSet = new ChangeSet($this->documentUpdates, $this->documentInserts, $this->documentRemovals);
        $persister = $this->createPersister();
        $persister->process($changeSet);

        $this->documentInserts
            = $this->documentUpdates
            = array();
    }


    public function execute(Query $query, $fetchPlan = null)
    {
        $binding = $this->getManager()->getBinding();
        $results = $binding->execute($query, $fetchPlan)->getResult();

        if (is_array($results) && $query->canHydrate()) {
            $collection = $this->getHydrator()->hydrateCollection($results);

            foreach ($collection as $entity) {
                $this->persist($entity);
            }

            return $collection;
        }

        return true;
    }

    /**
     * Checks whether an entity identified by the $rid is registered in the identity map of this UnitOfWork.
     *
     * @param Rid $rid
     *
     * @return boolean
     */
    public function isInIdentityMap(Rid $rid)
    {
        return isset($this->identityMap[$rid->getValue()]);
    }

    /**
     * @param Rid $rid
     * @param bool $lazy
     * @param null $fetchPlan
     *
     * @return Proxy
     */
    public function getProxy(Rid $rid, $lazy = true, $fetchPlan = null)
    {
        if (! $this->isInIdentityMap($rid)) {
            if ($lazy) {
                $proxy = $this->getHydrator()->hydrateRid($rid);
            } else {
                $proxy = $this->load($rid, $fetchPlan);
            }

            $this->identityMap[$rid->getValue()] = $proxy;
        }

        return $this->identityMap[$rid->getValue()];
    }

    /**
     * @param string[] $rids
     * @param bool     $lazy
     * @param string   $fetchPlan
     *
     * @return ArrayCollection|null
     */
    public function getCollection(array $rids, $lazy = true, $fetchPlan = null)
    {
        if ($lazy) {
            $proxies = array();
            foreach ($rids as $rid) {
                $proxies[] = $this->getProxy(new Rid($rid), $lazy);
            }

            return new ArrayCollection($proxies);
        }

        $results = $this->getHydrator()->load($rids, $fetchPlan);

        if (is_array($results)) {
            return $this->getHydrator()->hydrateCollection($results);
        }

        return null;

    }

    public function attachOriginalData($rid, array $originalData)
    {
        $this->originalData[$rid] = $originalData;
    }

    public function persist($document)
    {
        if ($document instanceof Proxy) {
            $this->identityMap[$this->getRid($document)] = $document;
        } else {
            $this->newDocuments[spl_object_hash($document)] = $document;
        }
    }

    public function clear($document)
    {
        if ($document instanceof Proxy) {
            $rid = $this->getRid($document);
            if (isset($this->identityMap[$rid])) {
                unset($this->identityMap[$rid]);
            }
        } else {
            $hash = spl_object_hash($document);
            if (isset($this->newDocuments[$hash])) {
                unset($this->newDocuments[$hash]);
            }
        }
    }

    /**
     * @param Proxy $proxy
     */
    public function remove(Proxy $proxy)
    {
        $rid = $this->getRid($proxy);
        $this->documentRemovals[$rid] = ['document' => $proxy];
    }

    /**
     * @param object $document
     */
    public function refresh($document) {
        $rid = $this->getRid($document);
        throw new \Exception('not implemented');
    }

    public function recomputeSingleDocumentChangeSet(ClassMetadata $class, $document) {
        $oid = spl_object_hash($document);
        throw new \Exception('not implemented');
    }

    /**
     * Computes the changesets for all documents attached to the UnitOfWork
     */
    protected function computeChangeSets()
    {
        foreach ($this->identityMap as $proxy) {
            $this->computeSingleDocumentChangeSet($proxy);
        }

        foreach ($this->newDocuments as $document) {
            $this->computeSingleDocumentChangeSet($document);
        }
    }


    /**
     * Computes the changeset for the specified document.
     *
     * @param $document
     */
    protected function computeSingleDocumentChangeSet($document)
    {
        if ($document instanceof Proxy) {
            // if the proxy wasn't loaded, it wasn't touched either
            if (! $document->__isInitialized()) {
                return;
            }

            $identifier = $this->getRid($document);

            // if it is marked for removal, ignore the changes
            if (isset($this->documentRemovals[$identifier])) {
                return;
            }

            $originalData = isset($this->originalData[$identifier]) ? $this->originalData[$identifier] : null;
            $changes = $this->extractChangeSet($document, $originalData);
            if ($changes) {
                $this->documentUpdates[$identifier] = array('changes' => $changes, 'document' => $document);
            }
        } else {
            $changes = $this->extractChangeSet($document);
            // identify the document by its hash to avoid duplicates
            $identifier = spl_object_hash($document);
            $this->documentInserts[$identifier] = array('changes' => $changes, 'document' => $document);
        }
    }

    protected function extractChangeSet($document, array $originalData = null)
    {
        $changes = array();
        $metadata = $this->getManager()->getClassMetadata(get_class($document));
        foreach ($metadata->getReflectionFields() as $reflField) {
            $fieldAnnotation = $metadata->getField($reflField->getName());

            // if the field isn't mapped we can just ignore it.
            if (! $fieldAnnotation) {
                continue;
            }

            $fieldName = $fieldAnnotation->name;
            $currentValue = $reflField->getValue($document);


            /* if we don't know the original data, or it doesn't have the field, or the field's
             * value is different we count it as a change
             */
            if (! $originalData || ! isset($originalData[$fieldName]) || $originalData[$fieldName] !== $currentValue) {
                $changes[] = array('field' => $fieldName, 'value' => $currentValue, 'annotation' => $fieldAnnotation);
            }
        }

        return $changes;
    }


    /**
     * Gets the rid of the proxy.
     *
     * @param Proxy $proxy
     *
     * @return string
     */
    protected function getRid(Proxy $proxy)
    {
        $metadata = $this->getManager()->getClassMetadata(get_class($proxy));

        return $metadata->getIdentifierValues($proxy);
    }

    /**
     * Executes a query against OrientDB to find the specified RID and finalizes the
     * hydration result.
     *
     * Optionally the query can be executed using the specified fetch plan.
     *
     * @param  Rid   $rid
     * @param  mixed $fetchPlan
     * @return object|null
     */
    protected function load(Rid $rid, $fetchPlan = null)
    {
        $results = $this->getHydrator()->load(array($rid->getValue()), $fetchPlan);

        if (isset($results) && count($results)) {
            $record = is_array($results) ? array_shift($results) : $results;
            $results = $this->getHydrator()->hydrate($record);

            return $results;
        }

        return null;
    }


    /**
     * Returns the manager the UnitOfWork is attached to
     *
     * @return Manager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     *
     * Lazily instantiates and returns the Hydrator
     *
     * @return Hydrator
     */
    public function getHydrator()
    {
        if (! $this->hydrator) {
            $this->hydrator = new Hydrator($this);
        }

        return $this->hydrator;
    }

    protected function getInflector()
    {
        return $this->getManager()->getInflector();
    }

    protected function createPersister()
    {
        $strategy = $this->manager->getConfiguration()->getPersisterStrategy();
        if ('sql_batch' === $strategy) {
            return new SQLBatchPersister($this, $this->getInflector());
        }
    }
}