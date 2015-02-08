<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Hydration\Hydrator;
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
    private $originalDocumentData = [];
    private $documentUpdates = [];
    private $documentInserts = [];
    private $documentRemovals = [];
    private $documentStates = [];

    public function __construct(DocumentManager $manager) {
        $this->manager = $manager;
    }

    /**
     * Commits the UnitOfWork, executing all operations that have been postponed
     * up to this point. The state of all managed documents will be synchronized with
     * the database.
     *
     * The operations are executed in the following order:
     *
     * 1) All document insertions
     * 2) All document updates
     * 3) All document deletions
     *
     * @param object|array|null $document
     */
    public function commit($document = null) {
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

        // clean up
        $this->documentInserts
            = $this->documentUpdates
            = $this->documentRemovals
            = [];
    }

    public function execute(Query $query, $fetchPlan = null) {
        $binding = $this->getManager()->getBinding();
        $results = $binding->execute($query, $fetchPlan)->getResult();

        if (is_array($results) && $query->canHydrate()) {
            return $this->getHydrator()->hydrateCollection($results);
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
    public function isInIdentityMap(Rid $rid) {
        return isset($this->identityMap[$rid->getValue()]);
    }

    /**
     * @param Rid  $rid
     * @param bool $lazy
     * @param null $fetchPlan
     *
     * @return Proxy
     */
    public function getProxy(Rid $rid, $lazy = true, $fetchPlan = null) {
        if (!$this->isInIdentityMap($rid)) {
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
    public function getCollection(array $rids, $lazy = true, $fetchPlan = null) {
        if ($lazy) {
            $proxies = [];
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

    public function attachOriginalData($rid, array $originalData) {
        $this->originalDocumentData[$rid] = $originalData;
    }

    public function persist($document) {
        if ($document instanceof Proxy) {
            $this->identityMap[$this->getRid($document)] = $document;
        } else {
            $this->newDocuments[spl_object_hash($document)] = $document;
        }
    }

    /**
     * Clears the UnitOfWork.
     *
     * @param string|null $class if given, only documents of this type will be detached.
     *
     * @throws \Exception if $class is not null (not implemented)
     */
    public function clear($class = null) {
        if ($class === null) {
            $this->identityMap
                = $this->newDocuments
                = $this->originalDocumentData
                = $this->documentUpdates
                = $this->documentInserts
                = $this->documentRemovals
                = $this->documentStates = [];
        } else {
            throw new \Exception('not implemented');
        }
    }

    /**
     * @param Proxy $proxy
     */
    public function remove(Proxy $proxy) {
        $rid                          = $this->getRid($proxy);
        $this->documentRemovals[$rid] = ['document' => $proxy];
    }

    /**
     * @param object $document
     */
    public function refresh($document) {
        $rid = $this->getRid($document);
        throw new \Exception('not implemented');
    }

    /**
     * INTERNAL:
     * Computes the changeset of an individual document, independently of the
     * computeChangeSets() routine that is used at the beginning of a UnitOfWork#commit().
     *
     * The passed document must be a managed document. If the document already has a change set
     * because this method is invoked during a commit cycle then the change sets are added.
     * whereby changes detected in this method prevail.
     *
     * @ignore
     *
     * @param object $document The document for which to (re)calculate the change set.
     *
     * @throws \InvalidArgumentException If the passed document is not MANAGED.
     */
    public function recomputeSingleDocumentChangeSet($document) {
        $oid = spl_object_hash($document);
        if (!isset($this->documentStates[$oid]) || $this->documentStates[$oid] !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException('document must be managed.');
        }

        $this->computeSingleDocumentChangeSet($document);
    }

    /**
     * Computes the changesets for all documents attached to the UnitOfWork
     */
    protected function computeChangeSets() {
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
    protected function computeSingleDocumentChangeSet($document) {
        if ($document instanceof Proxy) {
            // if the proxy wasn't loaded, it wasn't touched either
            if (!$document->__isInitialized()) {
                return;
            }

            $identifier = $this->getRid($document);

            // if it is marked for removal, ignore the changes
            if (isset($this->documentRemovals[$identifier])) {
                return;
            }

            $originalData = isset($this->originalDocumentData[$identifier]) ? $this->originalDocumentData[$identifier] : null;
            $changes      = $this->buildChangeSet($document, $originalData);
            if ($changes) {
                $this->documentUpdates[$identifier] = ['changes' => $changes, 'document' => $document];
            }
        } else {
            $changes = $this->buildChangeSet($document);
            // identify the document by its hash to avoid duplicates
            $oid                         = spl_object_hash($document);
            $this->documentInserts[$oid] = ['changes' => $changes, 'document' => $document];
        }
    }

    /**
     * Built a change set from the original data for the specified document
     *
     * @param object $document
     * @param array  $originalData
     *
     * @return array
     * @internal
     */
    public function buildChangeSet($document, array $originalData = null) {
        $changes  = [];
        $metadata = $this->getManager()->getClassMetadata(get_class($document));
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            $propName = $mapping['name'];
            if ($propName === '@rid') continue;

            $currentValue = $metadata->getFieldValue($document, $fieldName);

            /* if we don't know the original data, or it doesn't have the field, or the field's
             * value is different we count it as a change
             */
            if (!$originalData || !isset($originalData[$propName]) || $originalData[$propName] !== $currentValue) {
                $changes[] = ['field' => $propName, 'value' => $currentValue, 'mapping' => $mapping];
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
    protected function getRid(Proxy $proxy) {
        $metadata = $this->getManager()->getClassMetadata(get_class($proxy));

        return $metadata->getIdentifierValues($proxy);
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return boolean true if this UnitOfWork has pending insertions
     */
    public function hasPendingInsertions() {
        return !empty($this->documentInserts);
    }

    /**
     * Calculates the size of the UnitOfWork. The size of the UnitOfWork is the
     * number of documents in the identity map.
     *
     * @return integer
     */
    public function size() {
        return count($this->identityMap);
    }

    /**
     * INTERNAL:
     * Registers a document as managed.
     *
     * @param object $document The document.
     * @param string $rid      The document RID
     * @param array  $data     The original document data.
     */
    public function registerManaged($document, $rid, array $data) {
        $oid                              = spl_object_hash($document);
        $this->documentStates[$oid]       = self::STATE_MANAGED;
        $this->originalDocumentData[$rid] = $data;
        $this->addToIdentityMap($document);
    }

    /**
     * Add the specified document to the identity map
     *
     * @param object $document
     *
     * @internal
     */
    public function addToIdentityMap($document) {
        if ($document instanceof Proxy) {
            $id = $this->getRid($document);
        } else {
            $id = spl_object_hash($document);
        }

        $this->identityMap[$id] = $document;
    }

    /**
     * Gets the identity map of the UnitOfWork.
     *
     * @return array
     */
    public function getIdentityMap() {
        return $this->identityMap;
    }

    /**
     * Gets the original data of a document. The original data is the data that was
     * present at the time the document was reconstituted from the database.
     *
     * @param object $document
     *
     * @return array
     */
    public function getOriginalDocumentData($document) {
        $oid = spl_object_hash($document);
        if (isset($this->originalDocumentData[$oid])) {
            return $this->originalDocumentData[$oid];
        }

        return [];
    }

    /**
     * Executes a query against OrientDB to find the specified RID and finalizes the
     * hydration result.
     *
     * Optionally the query can be executed using the specified fetch plan.
     *
     * @param  Rid   $rid
     * @param  mixed $fetchPlan
     *
     * @return object|null
     */
    protected function load(Rid $rid, $fetchPlan = null) {
        $results = $this->getHydrator()->load([$rid->getValue()], $fetchPlan);

        if (isset($results) && count($results)) {
            $record  = is_array($results) ? array_shift($results) : $results;
            $results = $this->getHydrator()->hydrate($record);

            return $results;
        }

        return null;
    }


    /**
     * Returns the manager the UnitOfWork is attached to
     *
     * @return DocumentManager
     */
    public function getManager() {
        return $this->manager;
    }

    /**
     *
     * Lazily instantiates and returns the Hydrator
     *
     * @return \Doctrine\ODM\OrientDB\Hydration\Hydrator
     */
    public function getHydrator() {
        if (!$this->hydrator) {
            $this->hydrator = new Hydrator($this);
        }

        return $this->hydrator;
    }

    protected function createPersister() {
        $strategy = $this->manager->getConfiguration()->getPersisterStrategy();
        if ('sql_batch' === $strategy) {
            return new SQLBatchPersister($this);
        }
    }
}