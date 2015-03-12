<?php

namespace Doctrine\ODM\OrientDB;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\EventManager;
use Doctrine\Common\NotifyPropertyChanged;
use Doctrine\Common\PropertyChangedListener;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\Event\LifecycleEventArgs;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Persister\PersisterInterface;
use Doctrine\ODM\OrientDB\Persister\SQLBatchPersister;
use Doctrine\ODM\OrientDB\Proxy\Proxy;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\OrientDB\Query\Query;

/**
 * Class UnitOfWork
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class UnitOfWork implements PropertyChangedListener
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

    /**
     * The identity map holds references to all managed documents.
     *
     * Documents are grouped by their class name, and then indexed by the
     * serialized string of their database identifier field or, if the class
     * has no identifier, the SPL object hash. Serializing the identifier allows
     * differentiation of values that may be equal (via type juggling) but not
     * identical.
     *
     * Since all classes in a hierarchy must share the same identifier set,
     * we always take the root class name of the hierarchy.
     *
     * @var array
     */
    private $identityMap = [];

    /**
     * Map of all identifiers of managed documents.
     * Keys are object ids (spl_object_hash).
     *
     * @var array
     */
    private $documentIdentifiers = [];

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

    /**
     * @var array
     */
    private $documentStates = [];

    /**
     * @var array
     */
    private $scheduledForDirtyCheck = [];

    /**
     * @var array
     */
    private $documentUpdates = [];

    /**
     * @var array
     */
    private $documentInsertions = [];

    /**
     * @var array
     */
    private $documentDeletions = [];

    /**
     * All pending collection deletions.
     *
     * @var array
     */
    private $collectionDeletions = [];

    /**
     * All pending collection updates.
     *
     * @var array
     */
    private $collectionUpdates = [];

    /**
     * List of collections visited during change set calculation on a commit-phase of a UnitOfWork.
     * At the end of the UnitOfWork all these collections will make new snapshots
     * of their data.
     *
     * @var PersistentCollection[]
     */
    private $visitedCollections = [];

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

    /**
     * Embedded documents that are scheduled for removal.
     *
     * @var array
     */
    private $orphanRemovals = [];


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
     * @return Persister\DocumentPersister
     */
    public function getDocumentPersister($documentName) {
        if (!isset($this->persisters[$documentName])) {
            $class                           = $this->dm->getClassMetadata($documentName);
            $this->persisters[$documentName] = new Persister\DocumentPersister($this->dm, $this->evm, $this, $this->hydratorFactory, $class);
        }

        return $this->persisters[$documentName];
    }

    /**
     * Get the collection persister instance.
     *
     * @return \Doctrine\ODM\OrientDB\Persister\CollectionPersister
     */
    public function getCollectionPersister() {
        if (!isset($this->collectionPersister)) {
            $pb                        = $this->getPersistenceBuilder();
            $this->collectionPersister = new Persister\CollectionPersister($this->dm, $pb, $this);
        }

        return $this->collectionPersister;
    }

    /**
     * Set the document persister instance to use for the given document name
     *
     * @param string                      $documentName
     * @param Persister\DocumentPersister $persister
     */
    public function setDocumentPersister($documentName, Persister\DocumentPersister $persister) {
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
        // Raise preFlush
        if ($this->evm->hasListeners(Events::preFlush)) {
            $this->evm->dispatchEvent(Events::preFlush, new Event\PreFlushEventArgs($this->dm));
        }

        if (null === $document) {
            $this->computeChangeSets();
        } elseif (is_object($document)) {
            $this->computeSingleDocumentChangeSet($document);
        } elseif (is_array($document)) {
            foreach ($document as $object) {
                $this->computeSingleDocumentChangeSet($object);
            }
        }

        if (!($this->documentInsertions ||
            $this->documentDeletions ||
            $this->documentUpdates ||
            $this->collectionUpdates ||
            $this->collectionDeletions ||
            $this->orphanRemovals)
        ) {
            return; // Nothing to do.
        }

        if ($this->orphanRemovals) {
            foreach ($this->orphanRemovals as $removal) {
                $this->remove($removal);
            }
        }

        // Raise onFlush
        if ($this->evm->hasListeners(Events::onFlush)) {
            $this->evm->dispatchEvent(Events::onFlush, new Event\OnFlushEventArgs($this->dm));
        }

        $p = $this->createPersister();
        $p->process($this);
//
//        // Now we need a commit order to maintain referential integrity
//        $commitOrder = $this->getCommitOrder();
//
//        if ($this->documentInsertions) {
//            foreach ($commitOrder as $class) {
//                if ($class->isEmbeddedDocument) {
//                    continue;
//                }
//                $this->executeInserts($class);
//            }
//        }
//
//        if ($this->documentUpdates) {
//            foreach ($commitOrder as $class) {
//                $this->executeUpdates($class);
//            }
//        }
//
//        // Collection deletions (deletions of complete collections)
//        foreach ($this->collectionDeletions as $collectionToDelete) {
//            $this->getCollectionPersister()->delete($collectionToDelete);
//        }
//        // Collection updates (deleteRows, updateRows, insertRows)
//        foreach ($this->collectionUpdates as $collectionToUpdate) {
//            $this->getCollectionPersister()->update($collectionToUpdate);
//        }
//
//        // Document deletions come last and need to be in reverse commit order
//        if ($this->documentDeletions) {
//            for ($count = count($commitOrder), $i = $count - 1; $i >= 0; --$i) {
//                $this->executeDeletions($commitOrder[$i]);
//            }
//        }

        // Take new snapshots from visited collections
        foreach ($this->visitedCollections as $coll) {
            $coll->takeSnapshot();
        }

        // Raise postFlush
        if ($this->evm->hasListeners(Events::postFlush)) {
            $this->evm->dispatchEvent(Events::postFlush, new Event\PostFlushEventArgs($this->dm));
        }

        // Clear up
        $this->documentInsertions =
        $this->documentUpdates =
        $this->documentDeletions =
        $this->documentChangeSets =
        $this->collectionUpdates =
        $this->collectionDeletions =
        $this->visitedCollections =
        $this->scheduledForDirtyCheck =
        $this->orphanRemovals = [];
    }

    /**
     * @return array
     */
    public function getDocumentInsertions() {
        return $this->documentInsertions;
    }

    /**
     * @return array
     */
    public function getDocumentUpdates() {
        return $this->documentUpdates;
    }

    /**
     * @return array
     */
    public function getDocumentDeletions() {
        return $this->documentDeletions;
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
        $oid = spl_object_hash($document);

        if (!isset($this->documentIdentifiers[$oid])) {
            return false;
        }

        /** @var ClassMetadata $class */
        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isEmbeddedDocument) {
            $id = $oid;
        } else {
            $id = $this->getRid($document);
        }

        return isset($this->identityMap[$class->name][$id]);
    }

    /**
     * Returns the RID of the specified managed document or null if it is not managed
     *
     * @param object $document
     *
     * @return string
     */
    public function getDocumentRid($document) {
        $oid = spl_object_hash($document);

        if (isset($this->documentIdentifiers[$oid])) {
            return $this->documentIdentifiers[$oid];
        }

        return null;
    }

    /**
     * @param \stdClass $data
     * @param array     $hints
     *
     * @return object
     */
    public function getOrCreateDocument(\stdClass $data, array &$hints = []) {
        /** @var ClassMetadata $class */
        $class = $this->dm->getMetadataFactory()->getMetadataForOClass($data->{'@class'});

        $id = $data->{'@rid'};
        if (isset($this->identityMap[$class->name][$id])) {
            $document = $this->identityMap[$class->name][$id];
            $oid      = spl_object_hash($document);
            if ($document instanceof Proxy && !$document->__isInitialized__) {
                $document->__isInitialized__ = true;
                if ($document instanceof NotifyPropertyChanged) {
                    $document->addPropertyChangedListener($this);
                }
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
                    "behavior of persist() for a detached document is undefined");
                break;

            /** @noinspection PhpMissingBreakStatementInspection */
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
                throw OrientDBException::invalidDocumentState($documentState);
        }

        $this->cascadePersist($document, $visited);
    }

    /**
     * Cascades the save operation to associated documents.
     *
     * @param object $document
     * @param array  $visited
     */
    private function cascadePersist($document, array &$visited) {
        $class = $this->dm->getClassMetadata(get_class($document));

        foreach ($class->associationMappings as $fieldName => $mapping) {
            if (!$mapping['isCascadePersist']) {
                continue;
            }

            $relatedDocuments = $class->getFieldValue($document, $fieldName);

            if ($relatedDocuments instanceof Collection || is_array($relatedDocuments)) {
                if ($relatedDocuments instanceof PersistentCollection) {
                    // Unwrap so that foreach() does not initialize
                    $relatedDocuments = $relatedDocuments->unwrap();
                }

                foreach ($relatedDocuments as $relatedDocument) {
                    $this->doPersist($relatedDocument, $visited);
                }
            } elseif ($relatedDocuments !== null) {
                $this->doPersist($relatedDocuments, $visited);
            }
        }
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

        if (!$class->isEmbeddedDocument) {
            $idValue                         = $class->getIdentifierValue($document);
            $this->documentIdentifiers[$oid] = $idValue;
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
            $this->documentIdentifiers =
            $this->originalDocumentData =
            $this->documentChangeSets =
            $this->documentStates =
            $this->scheduledForDirtyCheck =
            $this->documentInsertions =
            $this->documentUpdates =
            $this->documentDeletions =
            $this->collectionUpdates =
            $this->collectionDeletions =
            $this->parentAssociations =
            $this->orphanRemovals = [];
        } else {
            $visited = [];
            foreach ($this->identityMap as $className => $documents) {
                if ($className === $class) {
                    foreach ($documents as $document) {
                        $this->doDetach($document, $visited, true);
                    }
                }
            }
        }
    }

    /**
     * INTERNAL:
     * Schedules an embedded document for removal. The remove() operation will be
     * invoked on that document at the beginning of the next commit of this
     * UnitOfWork.
     *
     * @ignore
     *
     * @param object $document
     */
    public function scheduleOrphanRemoval($document) {
        $this->orphanRemovals[spl_object_hash($document)] = $document;
    }

    /**
     * INTERNAL:
     * Schedules a complete collection for removal when this UnitOfWork commits.
     *
     * @param PersistentCollection $coll
     */
    public function scheduleCollectionDeletion(PersistentCollection $coll) {
        //TODO: if $coll is already scheduled for recreation ... what to do?
        // Just remove $coll from the scheduled recreations?
        $this->collectionDeletions[] = $coll;
    }

    /**
     * Checks whether a PersistentCollection is scheduled for deletion.
     *
     * @param PersistentCollection $coll
     *
     * @return boolean
     */
    public function isCollectionScheduledForDeletion(PersistentCollection $coll) {
        return in_array($coll, $this->collectionDeletions, true);
    }

    /**
     * Checks whether a PersistentCollection is scheduled for update.
     *
     * @param PersistentCollection $coll
     *
     * @return boolean
     */
    public function isCollectionScheduledForUpdate(PersistentCollection $coll) {
        return in_array($coll, $this->collectionUpdates, true);
    }

    /**
     * @param $document
     */
    public function remove($document) {
        $visited = [];
        $this->doRemove($document, $visited);
    }

    /**
     * @param object $document
     * @param array  $visited
     *
     * @throws OrientDBException
     */
    private function doRemove($document, array &$visited) {
        $oid = spl_object_hash($document);
        if (isset($visited[$oid])) {
            return; // Prevent infinite recursion
        }

        $visited[$oid] = $document; // mark visited

        /* Cascade first, because scheduleForDelete() removes the entity from
         * the identity map, which can cause problems when a lazy Proxy has to
         * be initialized for the cascade operation.
         */
        $this->cascadeRemove($document, $visited);

        $class         = $this->dm->getClassMetadata(get_class($document));
        $documentState = $this->getDocumentState($document);
        switch ($documentState) {
            case self::STATE_NEW:
            case self::STATE_REMOVED:
                // nothing to do
                break;
            case self::STATE_MANAGED:
//                if ( ! empty($class->lifecycleCallbacks[Events::preRemove])) {
//                    $class->invokeLifecycleCallbacks(Events::preRemove, $document);
//                }
                if ($this->evm->hasListeners(Events::preRemove)) {
                    $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($document, $this->dm));
                }
                $this->scheduleForDelete($document);
                $this->cascadePreRemove($class, $document);
                break;
            case self::STATE_DETACHED:
                throw OrientDBException::detachedDocumentCannotBeRemoved();
            default:
                throw OrientDBException::invalidDocumentState($documentState);
        }
    }

    /**
     * Cascades the delete operation to associated documents.
     *
     * @param object $document
     * @param array  $visited
     */
    private function cascadeRemove($document, array &$visited) {
        $class = $this->dm->getClassMetadata(get_class($document));
        foreach ($class->fieldMappings as $mapping) {
            if (!$mapping['isCascadeRemove']) {
                continue;
            }
            if ($document instanceof Proxy && !$document->__isInitialized__) {
                $document->__load();
            }
            if (isset($mapping['embedded'])) {
                $relatedDocuments = $class->getFieldValue($document, $mapping['fieldName']);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->cascadeRemove($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->cascadeRemove($relatedDocuments, $visited);
                }
            } elseif (isset($mapping['reference'])) {
                $relatedDocuments = $class->getFieldValue($document, $mapping['fieldName']);
                if (($relatedDocuments instanceof Collection || is_array($relatedDocuments))) {
                    // If its a PersistentCollection initialization is intended! No unwrap!
                    foreach ($relatedDocuments as $relatedDocument) {
                        $this->doRemove($relatedDocument, $visited);
                    }
                } elseif ($relatedDocuments !== null) {
                    $this->doRemove($relatedDocuments, $visited);
                }
            }
        }
    }

    /**
     * Cascades the preRemove event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object        $document
     */
    private function cascadePreRemove(ClassMetadata $class, $document) {
        $hasPreRemoveListeners = $this->evm->hasListeners(Events::preRemove);

        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->getFieldValue($document, $mapping['fieldName']);
                if ($value === null) {
                    continue;
                }
                if ($mapping['association'] & ClassMetadata::TO_ONE) {
                    $value = [$value];
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
//                    if ( ! empty($entryClass->lifecycleCallbacks[Events::preRemove])) {
//                        $entryClass->invokeLifecycleCallbacks(Events::preRemove, $entry);
//                    }
                    if ($hasPreRemoveListeners) {
                        $this->evm->dispatchEvent(Events::preRemove, new LifecycleEventArgs($entry, $this->dm));
                    }
                    $this->cascadePreRemove($entryClass, $entry);
                }
            }
        }
    }

    /**
     * Cascades the postRemove event to embedded documents.
     *
     * @param ClassMetadata $class
     * @param object        $document
     */
    private function cascadePostRemove(ClassMetadata $class, $document) {
        $hasPostRemoveListeners = $this->evm->hasListeners(Events::postRemove);

        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['embedded'])) {
                $value = $class->getFieldValue($document, $mapping['fieldName']);
                if ($value === null) {
                    continue;
                }
                if ($mapping['type'] & ClassMetadata::TO_ONE) {
                    $value = [$value];
                }
                foreach ($value as $entry) {
                    $entryClass = $this->dm->getClassMetadata(get_class($entry));
//                    if ( ! empty($entryClass->lifecycleCallbacks[Events::postRemove])) {
//                        $entryClass->invokeLifecycleCallbacks(Events::postRemove, $entry);
//                    }
                    if ($hasPostRemoveListeners) {
                        $this->evm->dispatchEvent(Events::postRemove, new LifecycleEventArgs($entry, $this->dm));
                    }
                    $this->cascadePostRemove($entryClass, $entry);
                }
            }
        }
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
    public function &getDocumentActualData($document) {
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
                $value = $coll;
            }

            $actualData[$fieldName] = $value;
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
     * @param ClassMetadata $class    The class descriptor of the document.
     * @param object        $document The document for which to compute the changes.
     */
    public function computeChangeSet(ClassMetadata $class, $document) {

        // Fire PreFlush lifecycle callbacks
//        if ( ! empty($class->lifecycleCallbacks[Events::preFlush])) {
//            $class->invokeLifecycleCallbacks(Events::preFlush, $document);
//        }

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
     * @param ClassMetadata $class
     * @param object        $document The document for which to (re)calculate the change set.
     *
     */
    public function recomputeSingleDocumentChangeSet(ClassMetadata $class, $document) {
        $oid = spl_object_hash($document);
        if (!isset($this->documentStates[$oid]) || $this->documentStates[$oid] !== self::STATE_MANAGED) {
            throw new \InvalidArgumentException('document must be managed.');
        }

        $this->computeOrRecomputeChangeSet($class, $document, true);
    }

    /**
     * Computes the changesets for all documents attached to the UnitOfWork
     */
    public function computeChangeSets() {
        $this->computeScheduleInsertsChangeSets();

        foreach ($this->identityMap as $className => $documents) {
            $class = $this->dm->getClassMetadata($className);
            if ($class->isEmbeddedDocument) {
                // Embedded documents should only compute by the document itself which include the embedded document.
                // This is done separately later.
                // @see computeChangeSet()
                // @see computeAssociationChanges()
                continue;
            }

            // If change tracking is explicit or happens through notification, then only compute
            // changes on documents of that type that are explicitly marked for synchronization.
            $documentsToProcess = !$class->isChangeTrackingDeferredImplicit()
                ? (isset($this->scheduledForDirtyCheck[$className])
                    ? $this->scheduledForDirtyCheck[$className]
                    : [])
                : $documents;

            foreach ($documentsToProcess as $document) {
                // Ignore uninitialized proxy objects
                if ($document instanceof Proxy && !$document->__isInitialized__) {
                    continue;
                }
                // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
                $oid = spl_object_hash($document);
                if (!isset($this->documentInsertions[$oid])
                    && !isset($this->documentDeletions[$oid])
                    && isset($this->documentStates[$oid])
                ) {
                    $this->computeChangeSet($class, $document);
                }
            }
        }
    }

    /**
     * Compute changesets of all documents scheduled for insertion.
     *
     * Embedded documents will not be processed.
     */
    private function computeScheduleInsertsChangeSets() {
        foreach ($this->documentInsertions as $document) {
            $class = $this->dm->getClassMetadata(get_class($document));

            if ($class->isEmbeddedDocument) {
                continue;
            }

            $this->computeChangeSet($class, $document);
        }
    }


    /**
     * Only flush the given document according to a ruleset that keeps the UoW consistent.
     *
     * 1. All documents scheduled for insertion, (orphan) removals and changes in collections are processed as well!
     * 2. Proxies are skipped.
     * 3. Only if document is properly managed.
     *
     * @param  object $document
     *
     * @throws \InvalidArgumentException If the document is not STATE_MANAGED
     * @return void
     */
    private function computeSingleDocumentChangeSet($document) {
        $state = $this->getDocumentState($document);

        if ($state !== self::STATE_MANAGED && $state !== self::STATE_REMOVED) {
            throw new \InvalidArgumentException("Document has to be managed or scheduled for removal for single computation " . self::objToStr($document));
        }

        $class = $this->dm->getClassMetadata(get_class($document));

        if ($state === self::STATE_MANAGED && $class->isChangeTrackingDeferredImplicit()) {
            $this->persist($document);
        }

        // Compute changes for INSERTed and UPSERTed documents first. This must always happen even in this case.
        $this->computeScheduleInsertsChangeSets();

        // Ignore uninitialized proxy objects
        if ($document instanceof Proxy && !$document->__isInitialized__) {
            return;
        }

        // Only MANAGED documents that are NOT SCHEDULED FOR INSERTION, UPSERT OR DELETION are processed here.
        $oid = spl_object_hash($document);

        if (!isset($this->documentInsertions[$oid])
            && !isset($this->documentDeletions[$oid])
            && isset($this->documentStates[$oid])
        ) {
            $this->computeChangeSet($class, $document);
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
            // Document is "fully" MANAGED: it was already fully persisted before
            // and we have a copy of the original data
            $originalData = $this->originalDocumentData[$oid];
            $changeSet    = [];
            foreach ($actualData as $propName => $actualValue) {
                $orgValue = isset($originalData[$propName]) ? $originalData[$propName] : null;

                // skip if value has not changed
                if ($orgValue === $actualValue) {
                    continue;
                }

                // if relationship is a embed-one, schedule orphan removal to trigger cascade remove operations
                $field = &$class->fieldMappings[$propName];
                if (isset($field['embedded']) && $field['association'] === ClassMetadata::EMBED) {
                    if ($orgValue !== null) {
                        $this->scheduleOrphanRemoval($orgValue);
                    }

                    $changeSet[$propName] = [$orgValue, $actualValue];
                    continue;
                }

                // if owning side of reference-one relationship
                if (isset($field['reference'])) {
                    if ($field['association'] === ClassMetadata::LINK && $field['isOwningSide']) {
                        if ($orgValue !== null && $field['orphanRemoval']) {
                            $this->scheduleOrphanRemoval($orgValue);
                        }

                        $changeSet[$propName] = [$orgValue, $actualValue];
                        continue;
                    }

                    // ignore inverse side of reference-many relationship
                    if ($field['association'] & ClassMetadata::TO_MANY && !$field['isOwningSide']) {
                        continue;
                    }
                }

                // Persistent collection was exchanged with the "originally"
                // created one. This can only mean it was cloned and replaced
                // on another document.
                if ($actualValue instanceof PersistentCollection) {
                    $owner = $actualValue->getOwner();
                    if ($owner === null) { // cloned
                        $actualValue->setOwner($document, $field);
                    } elseif ($owner !== $document) { // no clone, we have to fix
                        if (!$actualValue->isInitialized()) {
                            $actualValue->initialize(); // we have to do this otherwise the cols share state
                        }
                        $newValue = clone $actualValue;
                        $newValue->setOwner($document, $field);
                        $class->setFieldValue($document, $propName, $newValue);
                    }
                }

                // if embed-many or reference-many relationship
                if (isset($field['association']) && $field['association'] & ClassMetadata::TO_MANY) {
                    $changeSet[$propName] = [$orgValue, $actualValue];
                    if ($orgValue instanceof PersistentCollection) {
                        $this->collectionDeletions[] = $orgValue;
                    }
                    continue;
                }

                // skip equivalent date values
                if (isset($field['type']) && $field['type'] === 'date') {
                    $dateType = Type::getType('date');
                    if ($dateType->equalsPHP($orgValue, $actualValue)) {
                        continue;
                    }
                }

                // regular field
                $changeSet[$propName] = [$orgValue, $actualValue];
            }
            if ($changeSet) {
                $this->documentChangeSets[$oid] = ($recompute && isset($this->documentChangeSets[$oid]))
                    ? $changeSet + $this->documentChangeSets[$oid]
                    : $changeSet;

                $this->originalDocumentData[$oid] = $actualData;
                $this->documentUpdates[$oid]      = $document;
            }
        }

        // Look for changes in associations of the document
        foreach ($class->associationMappings as $mapping) {
            $value = $class->getFieldValue($document, $mapping['fieldName']);
            if ($value !== null) {
                $this->computeAssociationChanges($document, $mapping, $value);
                if (isset($mapping['reference'])) {
                    continue;
                }

                // embedded documents must set the state of their parent

                $values = $value;
                if ($mapping['association'] & ClassMetadata::TO_ONE) {
                    $values = [$values];
                } elseif ($values instanceof PersistentCollection) {
                    if ($values->isDirty()) {
                        $this->documentChangeSets[$oid][$mapping['fieldName']] = [$value, $value];
                        if (!$isNewDocument) {
                            $this->documentUpdates[$oid] = $document;
                        }
                        continue;
                    }
                    $values = $values->unwrap();
                }
                foreach ($values as $obj) {
                    $oid2 = spl_object_hash($obj);
                    if (isset($this->documentChangeSets[$oid2])) {
                        $this->documentChangeSets[$oid][$mapping['fieldName']] = [$value, $value];
                        if (!$isNewDocument) {
                            $this->documentUpdates[$oid] = $document;
                        }
                        break;
                    }
                }
            }
        }
    }

    /**
     * Computes the changes of an embedded document.
     *
     * @param object $parentDocument
     * @param array  $mapping
     * @param mixed  $value The value of the association.
     *
     * @throws \InvalidArgumentException
     */
    private function computeAssociationChanges($parentDocument, $mapping, $value) {
        $isNewParentDocument   = isset($this->documentInsertions[spl_object_hash($parentDocument)]);
        $class                 = $this->dm->getClassMetadata(get_class($parentDocument));
        $topOrExistingDocument = (!$isNewParentDocument || !$class->isEmbeddedDocument);

        if ($value instanceof PersistentCollection && $value->isDirty() && $mapping['isOwningSide'] && $mapping['association'] & ClassMetadata::LINK_MANY) {
            if ($topOrExistingDocument) {
                if (!in_array($value, $this->collectionUpdates, true)) {
                    $this->collectionUpdates[] = $value;
                }
            }
            $this->visitedCollections[] = $value;
        }

        if (!$mapping['isCascadePersist']) {
            return; // "Persistence by reachability" only if persist cascade specified
        }

        if ($mapping['association'] & ClassMetadata::TO_ONE) {
            if ($value instanceof Proxy && !$value->__isInitialized__) {
                return; // Ignore uninitialized proxy objects
            }
            $value = [$value];
        } elseif ($value instanceof PersistentCollection) {
            $value = $value->unwrap();
        }
        $count = 0;
        foreach ($value as $key => $entry) {
            $targetClass = $this->dm->getClassMetadata(get_class($entry));
            $state       = $this->getDocumentState($entry, self::STATE_NEW);

            // Handle "set" strategy for multi-level hierarchy
            $pathKey = $mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY
                ? $key
                : $count;
            $path    = $mapping['association'] & ClassMetadata::TO_MANY
                ? $mapping['name'] . '.' . $pathKey
                : $mapping['name'];

            $count++;
            if ($state == self::STATE_NEW) {
                if (!$mapping['isCascadePersist']) {
                    throw new \InvalidArgumentException("A new document was found through a relationship that was not"
                        . " configured to cascade persist operations: " . self::objToStr($entry) . "."
                        . " Explicitly persist the new document or configure cascading persist operations"
                        . " on the relationship.");
                }
                $this->persistNew($targetClass, $entry);
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } elseif ($state == self::STATE_MANAGED && $targetClass->isEmbeddedDocument) {
                $this->setParentAssociation($entry, $mapping, $parentDocument, $path);
                $this->computeChangeSet($targetClass, $entry);
            } elseif ($state == self::STATE_REMOVED) {
                throw new \InvalidArgumentException("Removed document detected during flush: "
                    . self::objToStr($entry) . ". Remove deleted documents from associations.");
            } elseif ($state == self::STATE_DETACHED) {
                // Can actually not happen right now as we assume STATE_NEW,
                // so the exception will be raised from the DBAL layer (constraint violation).
                throw new \InvalidArgumentException("A detached document was found through a "
                    . "relationship during cascading a persist operation.");
            }
        }
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

        return $metadata->getIdentifierValue($document);
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
     * INTERNAL:
     * Registers a document as managed.
     *
     * @param object $document The document.
     * @param string $rid      The document RID
     * @param array  $data     The original document data.
     */
    public function registerManaged($document, $rid, array $data = null) {
        $oid   = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isEmbeddedDocument || $rid === null) {
            $this->documentIdentifiers[$oid] = $oid;
        } else {
            $this->documentIdentifiers[$oid] = $rid;
        }
        $this->documentStates[$oid]       = self::STATE_MANAGED;
        $this->originalDocumentData[$oid] = $data;
        $this->addToIdentityMap($document);
    }

    /**
     * Notifies this UnitOfWork of a property change in a document.
     *
     * @param object $document     The document that owns the property.
     * @param string $propertyName The name of the property that changed.
     * @param mixed  $oldValue     The old value of the property.
     * @param mixed  $newValue     The new value of the property.
     */
    public function propertyChanged($document, $propertyName, $oldValue, $newValue) {
        $oid   = spl_object_hash($document);
        $class = $this->dm->getClassMetadata(get_class($document));

        if (!isset($class->fieldMappings[$propertyName])) {
            return; // ignore non-persistent fields
        }

        // Update changeset and mark document for synchronization
        $this->documentChangeSets[$oid][$propertyName] = [$oldValue, $newValue];
        if (!isset($this->scheduledForDirtyCheck[$class->name][$oid])) {
            $this->scheduleForDirtyCheck($document);
        }
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
        if (isset($this->documentIdentifiers[$oid])) {
            $this->addToIdentityMap($document);
        }
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
     * Schedules a document for being updated.
     *
     * @param object $document The document to schedule for being updated.
     *
     * @throws \InvalidArgumentException
     */
    public function scheduleForUpdate($document) {
        $oid = spl_object_hash($document);
        if (!isset($this->documentIdentifiers[$oid])) {
            throw new \InvalidArgumentException("document has no identity.");
        }
        if (isset($this->documentDeletions[$oid])) {
            throw new \InvalidArgumentException("document is removed.");
        }

        if (!isset($this->documentUpdates[$oid]) && !isset($this->documentInsertions[$oid])) {
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
        $class = $this->dm->getClassMetadata(get_class($document));
        $id    = $this->getRid($document);
        if (empty($id)) {
            $id = spl_object_hash($document);
        }

        $this->identityMap[$class->name][$id] = $document;

        if ($document instanceof NotifyPropertyChanged) {
            $document->addPropertyChangedListener($this);
        }
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

        if ($class->isEmbeddedDocument) {
            return self::STATE_NEW;
        }

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
        if ($this->tryGetById($rid, $class)) {
            return self::STATE_DETACHED;
        }

        // DB lookup
        if ($this->getDocumentPersister($class->name)->exists($document)) {
            return self::STATE_DETACHED;
        }

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

        // Check if id is registered first
        if (!isset($this->documentIdentifiers[$oid])) {
            return false;
        }

        $class = $this->dm->getClassMetadata(get_class($document));
        if ($class->isEmbeddedDocument) {
            $id = spl_object_hash($document);
        } else {
            $id = $this->documentIdentifiers[spl_object_hash($document)];
        }

        if (isset($this->identityMap[$class->name][$id])) {
            unset($this->identityMap[$class->name][$id]);
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
     * @param mixed         $rid Document identifier
     * @param ClassMetadata $class
     *
     * @return object
     */
    public function getById($rid, ClassMetadata $class) {
        return isset($this->identityMap[$class->name][$rid])
            ? $this->identityMap[$class->name][$rid]
            : null;
    }

    /**
     * INTERNAL:
     * Tries to get a document by its identifier hash. If no document is found
     * for the given hash, FALSE is returned.
     *
     * @ignore
     *
     * @param mixed         $rid Document identifier
     * @param ClassMetadata $class
     *
     * @return mixed The found document or FALSE.
     */
    public function tryGetById($rid, ClassMetadata $class) {
        return isset($this->identityMap[$class->name][$rid])
            ? $this->identityMap[$class->name][$rid]
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

    /**
     * @return PersisterInterface
     * @throws \Exception
     */
    private function createPersister() {
        return new SQLBatchPersister($this->dm->getMetadataFactory(), $this->dm->getBinding());
    }

    private static function objToStr($obj) {
        return method_exists($obj, '__toString') ? (string)$obj : get_class($obj) . '@' . spl_object_hash($obj);
    }
}