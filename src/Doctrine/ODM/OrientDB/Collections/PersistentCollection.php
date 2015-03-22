<?php

namespace Doctrine\ODM\OrientDB\Collections;

use Doctrine\Common\Collections\Collection as BaseCollection;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\UnitOfWork;

class PersistentCollection implements BaseCollection
{
    /**
     * A snapshot of the collection at the moment it was fetched from the database.
     * This is used to create a diff of the collection at commit time.
     *
     * @var array
     */
    private $snapshot = [];

    /**
     * Collection's owning entity
     *
     * @var object
     */
    private $owner;

    /**
     * @var array
     */
    private $mapping;

    /**
     * Whether the collection is dirty and needs to be synchronized with the database
     * when the UnitOfWork that manages its persistent state commits.
     *
     * @var boolean
     */
    private $isDirty = false;

    /**
     * Whether the collection has already been initialized.
     *
     * @var boolean
     */
    private $initialized = true;

    /**
     * The wrapped Collection instance.
     *
     * @var BaseCollection
     */
    private $coll;

    /**
     * The DocumentManager that manages the persistence of the collection.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork that manages the persistence of the collection.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Any hints to account for during reconstitution/lookup of the documents.
     *
     * @var array
     */
    private $hints = [];

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param BaseCollection  $coll
     * @param DocumentManager $dm
     * @param UnitOfWork      $uow
     */
    public function __construct(BaseCollection $coll, DocumentManager $dm, UnitOfWork $uow) {
        $this->coll = $coll;
        $this->dm   = $dm;
        $this->uow  = $uow;
    }

    /**
     * Get hints to account for during reconstitution/lookup of the documents.
     *
     * @return array $hints
     */
    public function getHints() {
        return $this->hints;
    }

    /**
     * Set hints to account for during reconstitution/lookup of the documents.
     *
     * @param array $hints
     */
    public function setHints(array $hints) {
        $this->hints = $hints;
    }

    /**
     * @return mixed
     */
    public function getData() {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data) {
        if ($data instanceof \stdClass) {
            $data = (array)$data;
        }
        $this->data = $data;
    }

    /**
     * Gets a boolean flag indicating whether this collection is dirty which means
     * its state needs to be synchronized with the database.
     *
     * @return boolean TRUE if the collection is dirty, FALSE otherwise.
     */
    public function isDirty() {
        return $this->isDirty;
    }

    /**
     * Sets a boolean flag, indicating whether this collection is dirty.
     *
     * @param boolean $dirty Whether the collection should be marked dirty or not.
     */
    public function setDirty($dirty) {
        $this->isDirty = $dirty;
    }

    /**
     * INTERNAL:
     * Clears the internal snapshot information and sets isDirty to true if the collection
     * has elements.
     */
    public function clearSnapshot() {
        $this->snapshot = [];
        $this->isDirty  = $this->count() ? true : false;
    }

    /**
     * {@inheritdoc}
     */
    public function count() {
        $this->initialize();

        return $this->coll->count();
    }

    /**
     * Initializes the collection by loading its contents from the database
     * if the collection is not yet initialized.
     */
    public function initialize() {
        if ($this->initialized || !$this->mapping) {
            return;
        }

        $newObjects = [];

        if ($this->isDirty) {
            // Remember any NEW objects added through add()
            $newObjects = $this->coll->toArray();
        }

        $this->coll->clear();
        $this->uow->loadCollection($this);
        $this->takeSnapshot();

        // Reattach any NEW objects added through add()
        if ($newObjects) {
            $useKey = boolval($this->mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY);
            foreach ($newObjects as $key => $obj) {
                if ($useKey) {
                    $this->coll->set($key, $obj);
                } else {
                    $this->coll->add($obj);
                }
            }

            $this->isDirty = true;
        }

        $this->initialized = true;
    }

    /**
     * INTERNAL:
     * Tells this collection to take a snapshot of its current state.
     */
    public function takeSnapshot() {
        $this->snapshot = $this->coll->toArray();
        $this->isDirty  = false;
    }

    /**
     * INTERNAL:
     * Returns the last snapshot of the elements in the collection.
     *
     * @return array The last snapshot of the elements.
     */
    public function getSnapshot() {
        return $this->snapshot;
    }

    /**
     * INTERNAL:
     * getDeleteDiff
     *
     * @return array
     */
    public function getDeleteDiff() {
        return array_udiff_assoc(
            $this->snapshot,
            $this->coll->toArray(),
            function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }

    /**
     * INTERNAL:
     * getInsertDiff
     *
     * @return array
     */
    public function getInsertDiff() {
        return array_udiff_assoc(
            $this->coll->toArray(),
            $this->snapshot,
            function ($a, $b) {
                return $a === $b ? 0 : 1;
            }
        );
    }

    /**
     * INTERNAL:
     * Gets the collection owner.
     *
     * @return object
     */
    public function getOwner() {
        return $this->owner;
    }

    /**
     * INTERNAL:
     * Sets the collection's owning entity together with the AssociationMapping that
     * describes the association between the owner and the elements of the collection.
     *
     * @param object $document
     * @param array  $mapping
     */
    public function setOwner($document, array $mapping) {
        $this->owner   = $document;
        $this->mapping = $mapping;
    }

    public function getMapping() {
        return $this->mapping;
    }

    /**
     * Checks whether this collection has been initialized.
     *
     * @return boolean
     */
    public function isInitialized() {
        return $this->initialized;
    }

    /**
     * Sets the initialized flag of the collection, forcing it into that state.
     *
     * @param boolean $bool
     */
    public function setInitialized($bool) {
        $this->initialized = $bool;
    }

    /** {@inheritdoc} */
    public function first() {
        $this->initialize();

        return $this->coll->first();
    }

    /** {@inheritdoc} */
    public function last() {
        $this->initialize();

        return $this->coll->last();
    }

    /**
     * {@inheritdoc}
     */
    public function removeElement($element) {
        $this->initialize();
        $removed = $this->coll->removeElement($element);

        if (!$removed) {
            return $removed;
        }

        $this->changed();

        if ($this->isOrphanRemovalEnabled()) {
            $this->uow->scheduleOrphanRemoval($element);
        }

        return $removed;
    }

    /**
     * Marks this collection as changed/dirty.
     */
    private function changed() {
        if ($this->isDirty) {
            return;
        }

        $this->isDirty = true;

        if ($this->dm &&
            $this->mapping !== null &&
            $this->mapping['isOwningSide'] &&
            $this->owner //&&
            //$this->dm->getClassMetadata(get_class($this->owner))->isChangeTrackingNotify()
        ) {
            $this->uow->scheduleForDirtyCheck($this->owner);
        }
    }

    /**
     * Returns whether or not this collection has orphan removal enabled.
     *
     * Embedded documents are automatically considered as "orphan removal enabled" because they might have references
     * that require to trigger cascade remove operations.
     *
     * @return boolean
     */
    private function isOrphanRemovalEnabled() {
        if ($this->mapping === null) {
            return false;
        }

        if (isset($this->mapping['embedded'])) {
            return true;
        }

        if (isset($this->mapping['reference']) && $this->mapping['isOwningSide'] && $this->mapping['orphanRemoval']) {
            return true;
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function contains($element) {
        $this->initialize();

        return $this->coll->contains($element);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(\Closure $p) {
        $this->initialize();

        return $this->coll->exists($p);
    }

    /**
     * {@inheritdoc}
     */
    public function indexOf($element) {
        $this->initialize();

        return $this->coll->indexOf($element);
    }

    /**
     * {@inheritdoc}
     */
    public function getKeys() {
        $this->initialize();

        return $this->coll->getKeys();
    }

    /**
     * {@inheritdoc}
     */
    public function getValues() {
        $this->initialize();

        return $this->coll->getValues();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator() {
        $this->initialize();

        return $this->coll->getIterator();
    }

    /**
     * {@inheritdoc}
     */
    public function map(\Closure $func) {
        $this->initialize();

        return $this->coll->map($func);
    }

    /**
     * {@inheritdoc}
     */
    public function filter(\Closure $p) {
        $this->initialize();

        return $this->coll->filter($p);
    }

    /**
     * {@inheritdoc}
     */
    public function forAll(\Closure $p) {
        $this->initialize();

        return $this->coll->forAll($p);
    }

    /**
     * {@inheritdoc}
     */
    public function partition(\Closure $p) {
        $this->initialize();

        return $this->coll->partition($p);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray() {
        $this->initialize();

        return $this->coll->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function clear() {
        if ($this->initialized && $this->isEmpty()) {
            return;
        }

        if ($this->isOrphanRemovalEnabled()) {
            $this->initialize();

            foreach ($this->coll as $element) {
                $this->uow->scheduleOrphanRemoval($element);
            }
        }

        $this->coll->clear();
        if ($this->mapping['isOwningSide']) {
            $this->changed();
            $this->uow->scheduleCollectionDeletion($this);
            $this->takeSnapshot();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty() {
        return $this->count() === 0;
    }

    /**
     * {@inheritdoc}
     */
    public function slice($offset, $length = null) {
        $this->initialize();

        return $this->coll->slice($offset, $length);
    }

    /**
     * Called by PHP when this collection is serialized. Ensures that only the
     * elements are properly serialized.
     *
     * @internal Tried to implement Serializable first but that did not work well
     *           with circular references. This solution seems simpler and works well.
     */
    public function __sleep() {
        return ['coll', 'initialized'];
    }

    /**
     * @see containsKey()
     */
    public function offsetExists($offset) {
        return $this->containsKey($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function containsKey($key) {
        $this->initialize();

        return $this->coll->containsKey($key);
    }

    /**
     * @see get()
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function get($key) {
        $this->initialize();

        return $this->coll->get($key);
    }

    /* ArrayAccess implementation */

    /**
     * @see add()
     * @see set()
     */
    public function offsetSet($offset, $value) {
        if (!isset($offset)) {
            return $this->add($value);
        }

        $this->set($offset, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function add($value) {
        $this->coll->add($value);
        $this->changed();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function set($key, $value) {
        $this->coll->set($key, $value);
        $this->changed();
    }

    /**
     * @see remove()
     */
    public function offsetUnset($offset) {
        return $this->remove($offset);
    }

    /**
     * {@inheritdoc}
     */
    public function remove($key) {
        $this->initialize();
        $removed = $this->coll->remove($key);

        if (!$removed) {
            return $removed;
        }

        $this->changed();

        if ($this->isOrphanRemovalEnabled()) {
            $this->uow->scheduleOrphanRemoval($removed);
        }

        return $removed;
    }

    public function key() {
        return $this->coll->key();
    }

    /**
     * Gets the element of the collection at the current iterator position.
     */
    public function current() {
        return $this->coll->current();
    }

    /**
     * Moves the internal iterator position to the next element.
     */
    public function next() {
        return $this->coll->next();
    }

    /**
     * Retrieves the wrapped Collection instance.
     */
    public function unwrap() {
        return $this->coll;
    }

    /**
     * Cleanup internal state of cloned persistent collection.
     *
     * The following problems have to be prevented:
     * 1. Added documents are added to old PersistentCollection
     * 2. New collection is not dirty, if reused on other document nothing
     * changes.
     * 3. Snapshot leads to invalid diffs being generated.
     * 4. Lazy loading grabs entities from old owner object.
     * 5. New collection is connected to old owner and leads to duplicate keys.
     */
    public function __clone() {
        if (is_object($this->coll)) {
            $this->coll = clone $this->coll;
        }

        $this->initialize();

        $this->owner    = null;
        $this->snapshot = [];

        $this->changed();
    }
}
