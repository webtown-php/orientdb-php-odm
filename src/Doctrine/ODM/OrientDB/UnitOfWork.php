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

    private $dm;
    private $hydrator;
    private $identityMap = [];
    private $newDocuments = [];

    /**
     * Map of the original document data of managed documents.
     * Keys are object ids (spl_object_hash). This is used for calculating changesets
     * at commit time.
     *
     * @var array
     * @internal Note that PHPs "copy-on-write" behavior helps a lot with memory usage.
     *           A value will only really be copied if the value in the document is modified
     *           by the user.
     */
    private $originalDocumentData = [];

    /**
     * Map of document changes. Keys are object ids (spl_object_hash).
     * Filled at the beginning of a commit of the UnitOfWork and cleaned at the end.
     *
     * @var array
     */
    private $documentChangeSets = array();

    private $documentUpdates = [];
    private $documentInsertions = [];
    private $documentDeletions = [];
    private $documentStates = [];


    public function __construct(DocumentManager $manager) {
        $this->dm = $manager;
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

        $changeSet = new ChangeSet($this->documentUpdates, $this->documentInsertions, $this->documentDeletions);
        $persister = $this->createPersister();
        $persister->process($changeSet);

        // clean up
        $this->documentInsertions
            = $this->documentUpdates
            = $this->documentDeletions
            = [];
    }

    public function execute(Query $query, $fetchPlan = null) {
        $binding = $this->dm->getBinding();
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

//        $visited = [];
//        $this->doPersist($document, $visited);
    }

    private function doPersist($document, &$visited) {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return;
        }

        $visited[$oid] = $document;

        $class = $this->dm->getClassMetadata(get_class($document));

        $documentState = $this->getDocumentState($document, self::STATE_NEW);
        switch ($documentState) {
            case self::STATE_MANAGED:
                // Nothing to do, except if policy is "deferred explicit"
                if ($class->isChangeTrackingDeferredExplicit()) {
                    $this->scheduleForDirtyCheck($document);
                }
                break;
            case self::STATE_NEW:
                $this->persistNew($class, $document);
                break;
            case self::STATE_DETACHED:
                throw new \InvalidArgumentException(
                    "Behavior of persist() for a detached document is not yet defined.");
                break;
            case self::STATE_REMOVED:
                if (!$class->isEmbeddedDocument) {
                    // Document becomes managed again
                    if ($this->isScheduledForDelete($document)) {
                        unset($this->documentDeletions[$oid]);
                    } else {
                        //FIXME: There's more to think of here...
                        $this->scheduleForInsert($class, $document);
                    }
                    break;
                }
            default:
                throw MongoDBException::invalidDocumentState($documentState);
        }

        $this->cascadePersist($document, $visited);
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
            $this->identityMap =
            $this->newDocuments =
            $this->originalDocumentData =
            $this->documentChangeSets =
            $this->documentInsertions =
            $this->documentUpdates =
            $this->documentDeletions =
            $this->documentStates = [];
        } else {
            throw new \Exception('not implemented');
        }
    }

    /**
     * @param Proxy $proxy
     */
    public function remove(Proxy $proxy) {
        $rid                           = $this->getRid($proxy);
        $this->documentDeletions[$rid] = ['document' => $proxy];
    }

    public function refresh($document) {
        throw new \Exception('not implemented');
    }

    /**
     * Gets the changeset for a document.
     *
     * @param object $document
     *
     * @return array
     */
    public function getDocumentChangeSet($document) {
        $oid = spl_object_hash($document);
        if (isset($this->documentChangeSets[$oid])) {
            return $this->documentChangeSets[$oid];
        }

        return [];
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
            if (isset($this->documentDeletions[$identifier])) {
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
            $oid                            = spl_object_hash($document);
            $this->documentInsertions[$oid] = ['changes' => $changes, 'document' => $document];
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
        $metadata = $this->dm->getClassMetadata(get_class($document));
        foreach ($metadata->fieldMappings as $fieldName => $mapping) {
            $propName = $mapping['name'];
            if ($propName === '@rid') continue;

            $currentValue = $metadata->getFieldValue($document, $fieldName);

            /* if we don't know the original data, or it doesn't have the field, or the field's
             * value is different we count it as a change
             */
            if (!$originalData ||
                !isset($originalData[$propName]) ||
                $originalData[$propName] !== $currentValue
            ) {
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
        $metadata = $this->dm->getClassMetadata(get_class($proxy));

        return $metadata->getIdentifierValues($proxy);
    }

    /**
     * Checks whether the UnitOfWork has any pending insertions.
     *
     * @return boolean true if this UnitOfWork has pending insertions
     */
    public function hasPendingInsertions() {
        return !empty($this->documentInsertions);
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
     * Checks whether a document is registered as removed/deleted with the unit
     * of work.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isScheduledForDelete($document) {
        return isset($this->documentDeletions[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is scheduled for insertion, update or deletion.
     *
     * @param $document
     *
     * @return boolean
     */
    public function isDocumentScheduled($document) {
        $oid = spl_object_hash($document);

        return
            isset($this->documentInsertions[$oid]) ||
            isset($this->documentUpdates[$oid]) ||
            isset($this->documentDeletions[$oid]);
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
     * Gets the state of a document with regard to the current unit of work.
     *
     * @param object   $document
     * @param int|null $assume The state to assume if the state is not yet known (not MANAGED or REMOVED).
     *                         This parameter can be set to improve performance of document state detection
     *                         by potentially avoiding a database lookup if the distinction between NEW and DETACHED
     *                         is either known or does not matter for the caller of the method.
     *
     * @return int The document state.
     */
    public function getDocumentState($document, $assume = null) {
        $oid = spl_object_hash($document);

        if (isset($this->documentStates[$oid])) {
            return $this->documentStates[$oid];
        }

        $class = $this->dm->getClassMetadata(get_class($document));

//        if ($class->isEmbeddedDocument) {
//            return self::STATE_NEW;
//        }

        if ($assume !== null) {
            return $assume;
        }

        /* State can only be NEW or DETACHED, because MANAGED/REMOVED states are
         * known. Note that you cannot remember the NEW or DETACHED state in
         * _documentStates since the UoW does not hold references to such
         * objects and the object hash can be reused. More generally, because
         * the state may "change" between NEW/DETACHED without the UoW being
         * aware of it.
         */
        $rid = $class->getIdentifierValues($document);

        if ($rid === null) {
            return self::STATE_NEW;
        }

        // Last try before DB lookup: check the identity map.
        if ($this->tryGetById($rid)) {
            return self::STATE_DETACHED;
        }

        // DB lookup
//        if ($this->getDocumentPersister($class->name)->exists($document)) {
//            return self::STATE_DETACHED;
//        }

        return self::STATE_NEW;
    }

    /**
     * INTERNAL:
     * Removes a document from the identity map. This effectively detaches the
     * document from the persistence management of Doctrine.
     *
     * @ignore
     *
     * @param object $document
     *
     * @return boolean
     */
    public function removeFromIdentityMap($document) {
        $oid = spl_object_hash($document);

        if ($document instanceof Proxy) {
            $id = $this->getRid($document);
        } else {
            $id = $oid;
        }
        if (isset($this->identityMap[$id])) {
            unset($this->identityMap[$id]);
            $this->documentStates[$oid] = self::STATE_DETACHED;

            return true;
        }

        return false;
    }

    /**
     * INTERNAL:
     * Gets a document in the identity map by its identifier hash.
     *
     * @ignore
     *
     * @param mixed $rid Document identifier
     *
     * @return object
     */
    public function getById($rid) {
        return isset($this->identityMap[$rid])
            ? $this->identityMap[$rid]
            : null;
    }

    /**
     * INTERNAL:
     * Tries to get a document by its identifier hash. If no document is found
     * for the given hash, FALSE is returned.
     *
     * @ignore
     *
     * @param mixed $rid Document identifier
     *
     * @return mixed The found document or FALSE.
     */
    public function tryGetById($rid) {
        return isset($this->identityMap[$rid])
            ? $this->identityMap[$rid]
            : false;
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
     *
     * Lazily instantiates and returns the Hydrator
     *
     * @return \Doctrine\ODM\OrientDB\Hydration\Hydrator
     */
    public function getHydrator() {
        if (!$this->hydrator) {
            $this->hydrator = new Hydrator($this->dm);
        }

        return $this->hydrator;
    }

    protected function createPersister() {
        $strategy = $this->dm->getConfiguration()->getPersisterStrategy();
        if ('sql_batch' === $strategy) {
            return new SQLBatchPersister($this->dm);
        }
    }
}