<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\Collections\ArrayCollection;
use Doctrine\ODM\OrientDB\Event\LifecycleEventArgs;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
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

    /**
     * The DocumentManager that "owns" this UnitOfWork instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The EventManager used for dispatching events.
     *
     * @var EventManager
     */
    private $evm;

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
    private $documentChangeSets = [];

    private $documentStates = [];

    private $scheduledForDirtyCheck = [];

    private $documentUpdates = [];
    private $documentInsertions = [];
    private $documentDeletions = [];

    /**
     * The HydratorFactory used for hydrating array Mongo documents to Doctrine object documents.
     *
     * @var HydratorFactoryInterface
     */
    private $hydratorFactory;

    /**
     * The document persister instances used to persist document instances.
     *
     * @var array
     */
    private $persisters = [];

    /**
     * Array of parent associations between embedded documents
     *
     * @todo We might need to clean up this array in clear(), doDetach(), etc.
     * @var array
     */
    private $parentAssociations = [];


    public function __construct(DocumentManager $manager, EventManager $evm, HydratorFactoryInterface $hydratorFactory) {
        $this->dm              = $manager;
        $this->evm             = $evm;
        $this->hydratorFactory = $hydratorFactory;
    }

    /**
     * Sets the parent association for a given embedded document.
     *
     * @param object $document
     * @param array  $mapping
     * @param object $parent
     * @param string $propertyPath
     */
    public function setParentAssociation($document, $mapping, $parent, $propertyPath) {
        $oid                            = spl_object_hash($document);
        $this->parentAssociations[$oid] = [$mapping, $parent, $propertyPath];
    }

    /**
     * Gets the parent association for a given embedded document.
     *
     *     <code>
     *     list($mapping, $parent, $propertyPath) = $this->getParentAssociation($embeddedDocument);
     *     </code>
     *
     * @param object $document
     *
     * @return array $association
     */
    public function getParentAssociation($document) {
        $oid = spl_object_hash($document);
        if (!isset($this->parentAssociations[$oid])) {
            return null;
        }

        return $this->parentAssociations[$oid];
    }

    /**
     * Get the document persister instance for the given document name
     *
     * @param string $documentName
     *
     * @return Persisters\DocumentPersister
     */
    public function getDocumentPersister($documentName) {
        if (!isset($this->persisters[$documentName])) {
            $class                           = $this->dm->getClassMetadata($documentName);
            $this->persisters[$documentName] = new Persisters\DocumentPersister($this->dm, $this->evm, $this, $this->hydratorFactory, $class);
        }

        return $this->persisters[$documentName];
    }

    /**
     * Get the collection persister instance.
     *
     * @return \Doctrine\ODM\OrientDB\Persisters\CollectionPersister
     */
    public function getCollectionPersister() {
        if (!isset($this->collectionPersister)) {
            $pb                        = $this->getPersistenceBuilder();
            $this->collectionPersister = new Persisters\CollectionPersister($this->dm, $pb, $this);
        }

        return $this->collectionPersister;
    }

    /**
     * Set the document persister instance to use for the given document name
     *
     * @param string                       $documentName
     * @param Persisters\DocumentPersister $persister
     */
    public function setDocumentPersister($documentName, Persisters\DocumentPersister $persister) {
        $this->persisters[$documentName] = $persister;
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
            = $this->scheduledForDirtyCheck
            = [];
    }

    public function execute(Query $query, $fetchPlan = null) {
        $binding = $this->dm->getBinding();
        $results = $binding->execute($query, $fetchPlan)->getResult();

        if (is_array($results) && $query->canHydrate()) {
            $documents = [];
            foreach ($results as $data) {
                $documents [] = $this->getOrCreateDocument($data);
            }

            return new ArrayCollection($documents);
        }

        return true;
    }

    /**
     * Schedules a document for dirty-checking at commit-time.
     *
     * @param object $document The document to schedule for dirty-checking.
     *
     * @todo Rename: scheduleForSynchronization
     */
    public function scheduleForDirtyCheck($document) {
        $this->scheduledForDirtyCheck[spl_object_hash($document)] = $document;
    }

    /**
     * Checks whether an entity identified by the $rid is registered in the identity map of this UnitOfWork.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isInIdentityMap($document) {
        $id = $this->getRid($document);
        return isset($this->identityMap[$id]);
    }

    /**
     * @param \stdClass $data
     * @param array     $hints
     *
     * @return object
     */
    public function getOrCreateDocument($data, &$hints = []) {
        /** @var ClassMetadata $class */
        $class = $this->dm->getMetadataFactory()->getMetadataForOClass($data->{'@class'});

        $id = $data->{'@rid'};
        if (isset($this->identityMap[$id])) {
            $document = $this->identityMap[$id];
            $oid      = spl_object_hash($document);
            if ($document instanceof Proxy && !$document->__isInitialized__) {
                $document->__isInitialized__ = true;
            }

            $data                             = $this->hydratorFactory->hydrate($document, $data, $hints);
            $this->originalDocumentData[$oid] = $data;
        } else {
            $document = $class->newInstance();
            $data     = $this->hydratorFactory->hydrate($document, $data, $hints);
            $this->registerManaged($document, $id, $data);
        }

        return $document;
    }

    public function attachOriginalData($rid, array $originalData) {
        $this->originalDocumentData[$rid] = $originalData;
    }

    public function persist($document) {
        $visited = [];
        $this->doPersist($document, $visited);
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
                //if ($class->isChangeTrackingDeferredExplicit()) {
                $this->scheduleForDirtyCheck($document);
                //}
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

        //$this->cascadePersist($document, $visited);
    }

    /**
     * @param        $class
     * @param object $document
     */
    private function persistNew(ClassMetadata $class, $document) {
        $oid = spl_object_hash($document);
//        if ( ! empty($class->lifecycleCallbacks[Events::prePersist])) {
//            $class->invokeLifecycleCallbacks(Events::prePersist, $document);
//        }
        if ($this->evm->hasListeners(Events::prePersist)) {
            $this->evm->dispatchEvent(Events::prePersist, new LifecycleEventArgs($document, $this->dm));
        }

        $this->documentStates[$oid] = self::STATE_MANAGED;
        $this->scheduleForInsert($class, $document);
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
            $this->scheduledForDirtyCheck =
            $this->parentAssociations =
            $this->documentStates = [];
        } else {
            throw new \Exception('not implemented');
        }
    }

    /**
     * @param $proxy
     */
    public function remove($proxy) {
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
     * Get a documents actual data, flattening all the objects to arrays.
     *
     * @param object $document
     *
     * @return array
     */
    public function getDocumentActualData($document) {
        $class      = $this->dm->getClassMetadata(get_class($document));
        $actualData = array();
        foreach ($class->fieldMappings as $fieldName => $mapping) {
            $value = $class->getFieldValue($document, $fieldName);
            if ((isset($mapping['association']) && $mapping['association'] & ClassMetadata::TO_MANY)
                && $value !== null && !($value instanceof PersistentCollection)
            ) {
                // If $actualData[$name] is not a Collection then use an ArrayCollection.
                if (!$value instanceof Collection) {
                    $value = new ArrayCollection($value);
                }

                // Inject PersistentCollection
                $coll = new PersistentCollection($value, $this->dm, $this);
                $coll->setOwner($document, $mapping);
                $coll->setDirty(!$value->isEmpty());
                $class->setFieldValue($document, $fieldName, $coll);
                $actualData[$fieldName] = $coll;
            } else {
                $actualData[$fieldName] = $value;
            }
        }

        return $actualData;
    }

    /**
     * Computes the changes that happened to a single document.
     *
     * Modifies/populates the following properties:
     *
     * {@link originalDocumentData}
     * If the document is NEW or MANAGED but not yet fully persisted (only has an id)
     * then it was not fetched from the database and therefore we have no original
     * document data yet. All of the current document data is stored as the original document data.
     *
     * {@link documentChangeSets}
     * The changes detected on all properties of the document are stored there.
     * A change is a tuple array where the first entry is the old value and the second
     * entry is the new value of the property. Changesets are used by persisters
     * to INSERT/UPDATE the persistent document state.
     *
     * {@link documentUpdates}
     * If the document is already fully MANAGED (has been fetched from the database before)
     * and any changes to its properties are detected, then a reference to the document is stored
     * there to mark it for an update.
     *
     * @param ClassMetadata $class The class descriptor of the document.
     * @param object $document The document for which to compute the changes.
     */
    public function computeChangeSet(ClassMetadata $class, $document)
    {
        $this->computeOrRecomputeChangeSet($class, $document);
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
    public function computeChangeSets() {
        $this->computeScheduleInsertsChangeSets();

        foreach ($this->identityMap as $document) {
            $class = $this->dm->getClassMetadata(ClassUtils::getClass($document));
            if ($class->isEmbeddedDocument) {
                // Embedded documents should only compute by the document itself which include the embedded document.
                // This is done separately later.
                // @see computeChangeSet()
                // @see computeAssociationChanges()
                continue;
            }
            $this->computeChangeSet($class, $document);
            //$this->computeSingleDocumentChangeSet($proxy);
        }

        foreach ($this->newDocuments as $document) {
            $this->computeSingleDocumentChangeSet($document);
        }
    }

    /**
     * Compute changesets of all documents scheduled for insertion.
     *
     * Embedded documents will not be processed.
     */
    private function computeScheduleInsertsChangeSets()
    {
        foreach ($this->documentInsertions as $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if ($class->isEmbeddedDocument) {
                continue;
            }

            $this->computeChangeSet($class, $document);
        }
    }



    /**
     * Computes the changeset for the specified document.
     *
     * @param $document
     */
    private function computeSingleDocumentChangeSet($document) {
        $state = $this->getDocumentState($document);
        if ($state !== self::STATE_MANAGED && $state !== self::STATE_REMOVED) {
            throw new \InvalidArgumentException("document has to be managed or scheduled for removal for single computation " . self::objToStr($document));
        }

        // Ignore uninitialized proxy objects
        if ($document instanceof Proxy && !$document->__isInitialized__) {
            return;
        }

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
            $changes                        = $this->buildChangeSet($document);
            $oid                            = spl_object_hash($document);
            $this->documentChangeSets[$oid] = ['changes' => $changes, 'document' => $document];
            // identify the document by its hash to avoid duplicates
            //$this->documentInsertions[$oid] = ['changes' => $changes, 'document' => $document];
        }
    }

    private function computeOrRecomputeChangeSet(ClassMetadata $class, $document, $recompute = false) {
        $oid           = spl_object_hash($document);
        $actualData    = $this->getDocumentActualData($document);
        $isNewDocument = !isset($this->originalDocumentData[$oid]);
        if ($isNewDocument) {
            // Document is either NEW or MANAGED but not yet fully persisted (only has an id).
            // These result in an INSERT.
            $this->originalDocumentData[$oid] = $actualData;
            $changeSet                        = [];
            foreach ($actualData as $propName => $actualValue) {
                $changeSet[$propName] = [null, $actualValue];
            }
            $this->documentChangeSets[$oid] = $changeSet;
        } else {

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
     * Gets the rid of the document
     *
     * @param object $document
     *
     * @return string
     */
    protected function getRid($document) {
        $metadata = $this->dm->getClassMetadata(ClassUtils::getClass($document));
        if ($metadata->isEmbeddedDocument) {
            return spl_object_hash($document);
        }

        return $metadata->getIdentifierValues($document);
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
    public function registerManaged($document, $rid, array $data = null) {
        $oid                              = spl_object_hash($document);
        $this->documentStates[$oid]       = self::STATE_MANAGED;
        $this->originalDocumentData[$oid] = $data;
        $this->addToIdentityMap($document);
    }

    /**
     * Schedules a document for insertion into the database.
     * If the document already has an identifier, it will be added to the
     * identity map.
     *
     * @param ClassMetadata $class
     * @param object        $document The document to schedule for insertion.
     *
     * @throws \InvalidArgumentException
     */
    public function scheduleForInsert(ClassMetadata $class, $document) {
        $oid = spl_object_hash($document);

        if (isset($this->documentUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty document can not be scheduled for insertion.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed document can not be scheduled for insertion.");
        }
        if (isset($this->documentInsertions[$oid])) {
            throw new \InvalidArgumentException("Document can not be scheduled for insertion twice.");
        }

        $this->documentInsertions[$oid] = $document;
        $this->addToIdentityMap($document);
    }

    /**
     * Schedules a document for upsert into the database and adds it to the
     * identity map
     *
     * @param ClassMetadata $class
     * @param object        $document The document to schedule for upsert.
     *
     * @throws \InvalidArgumentException
     */
    public function scheduleForUpsert(ClassMetadata $class, $document) {
        $oid = spl_object_hash($document);

        if (isset($this->documentUpdates[$oid])) {
            throw new \InvalidArgumentException("Dirty document can not be scheduled for upsert.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("Removed document can not be scheduled for upsert.");
        }
        if (isset($this->documentUpserts[$oid])) {
            throw new \InvalidArgumentException("Document can not be scheduled for upsert twice.");
        }

        $this->documentUpserts[$oid]     = $document;
        $this->documentIdentifiers[$oid] = $class->getIdentifierValue($document);
        $this->addToIdentityMap($document);
    }

    /**
     * Checks whether a document is scheduled for insertion.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isScheduledForInsert($document) {
        return isset($this->documentInsertions[spl_object_hash($document)]);
    }

    /**
     * Checks whether a document is scheduled for upsert.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isScheduledForUpsert($document) {
        return isset($this->documentUpserts[spl_object_hash($document)]);
    }

    /**
     * Schedules a document for being updated.
     *
     * @param object $document The document to schedule for being updated.
     *
     * @throws \InvalidArgumentException
     */
    public function scheduleForUpdate($document) {
        $oid = spl_object_hash($document);
        $id  = $this->getRid($document);
        if (!isset($this->documentIdentifiers[$oid])) {
            throw new \InvalidArgumentException("document has no identity.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("document is removed.");
        }

        if (!isset($this->documentUpdates[$oid]) && !isset($this->documentInsertions[$oid]) && !isset($this->documentUpserts[$oid])) {
            $this->documentUpdates[$oid] = $document;
        }
    }

    /**
     * Checks whether a document is registered as dirty in the unit of work.
     * Note: Is not very useful currently as dirty documents are only registered
     * at commit time.
     *
     * @param object $document
     *
     * @return boolean
     */
    public function isScheduledForUpdate($document) {
        return isset($this->documentUpdates[spl_object_hash($document)]);
    }

    public function isScheduledForDirtyCheck($document) {
        $class = $this->dm->getClassMetadata(get_class($document));

        return isset($this->scheduledForDirtyCheck[$class->name][spl_object_hash($document)]);
    }

    /**
     * INTERNAL:
     * Schedules a document for deletion.
     *
     * @param object $document
     */
    public function scheduleForDelete($document) {
        $oid = spl_object_hash($document);

        if (isset($this->documentInsertions[$oid])) {
            if ($this->isInIdentityMap($document)) {
                $this->removeFromIdentityMap($document);
            }
            unset($this->documentInsertions[$oid]);

            return; // document has not been persisted yet, so nothing more to do.
        }

        if (!$this->isInIdentityMap($document)) {
            return; // ignore
        }

        $this->removeFromIdentityMap($document);
        $this->documentStates[$oid] = self::STATE_REMOVED;

        if (isset($this->documentUpdates[$oid])) {
            unset($this->documentUpdates[$oid]);
        }
        if (!isset($this->documentDeletions[$oid])) {
            $this->documentDeletions[$oid] = $document;
        }
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
        $id = $this->getRid($document);
        if (empty($id)) {
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
        $id  = $this->getRid($document);
        if (empty($id)) {
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
     * Initializes (loads) an uninitialized persistent collection of a document.
     *
     * @param PersistentCollection $collection The collection to initialize.
     */
    public function loadCollection(PersistentCollection $collection) {
        $this->getDocumentPersister(get_class($collection->getOwner()))->loadCollection($collection);
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
     * @param object $document
     * @param array  $data
     */
    public function setOriginalDocumentData($document, array $data) {
        $this->originalDocumentData[spl_object_hash($document)] = $data;
    }

    protected function createPersister() {
        $strategy = $this->dm->getConfiguration()->getPersisterStrategy();
        if ('sql_batch' === $strategy) {
            return new SQLBatchPersister($this->dm);
        }
    }
}