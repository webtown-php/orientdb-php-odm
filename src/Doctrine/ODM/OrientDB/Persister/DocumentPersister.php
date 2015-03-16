<?php

namespace Doctrine\ODM\OrientDB\Persister;


use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Hydrator\HydratorInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\PersistentCollection;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Query\Query;

class DocumentPersister
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var \Doctrine\OrientDB\Binding\BindingInterface
     */
    private $binding;

    /**
     * The EventManager instance
     *
     * @var EventManager
     */
    private $evm;

    /**
     * The UnitOfWork instance.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * The Hydrator instance
     *
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * The ClassMetadata instance for the document type being persisted.
     *
     * @var ClassMetadata
     */
    private $class;

    public function __construct(DocumentManager $dm, EventManager $evm, UnitOfWork $uow, HydratorFactoryInterface $hydratorFactory, ClassMetadata $class) {
        $this->dm              = $dm;
        $this->binding         = $dm->getBinding();
        $this->evm             = $evm;
        $this->uow             = $uow;
        $this->hydratorFactory = $hydratorFactory;
        $this->class           = $class;
    }

    /**
     * @param object $document
     *
     * @return bool
     */
    public function exists($document) {
        $rid = $this->class->getIdentifierValue($document);
        $q   = new Query([$rid]);
        $q->select(['@rid']);
        $results = $this->binding->execute($q)->getResult();

        return isset($results) && count($results) === 1;
    }

    /**
     * @param string      $rid
     * @param string      $fetchPlan
     *
     * @param object|null $document
     *
     * @return mixed
     */
    public function load($rid, $fetchPlan = '*:0', $document = null) {
        $query   = new Query([$rid]);
        $results = $this->binding->execute($query, $fetchPlan)->getResult();
        if (isset($results) && count($results)) {
            $record = is_array($results) ? array_shift($results) : $results;

            return $this->createDocument($record, $document);
        }

        return null;
    }

    /**
     * Loads a PersistentCollection data. Used in the initialize() method.
     *
     * @param PersistentCollection $collection
     */
    public function loadCollection(PersistentCollection $collection) {
        $mapping = $collection->getMapping();
        switch ($mapping['association']) {
            case ClassMetadata::EMBED_LIST:
            case ClassMetadata::EMBED_SET:
            case ClassMetadata::EMBED_MAP:
                $this->loadEmbedArrayCollection($collection);
                break;

            case ClassMetadata::LINK_LIST:
            case ClassMetadata::LINK_SET:
            case ClassMetadata::LINK_MAP:
                $this->loadLinkArrayCollection($collection);
                break;
        }
    }

    private function loadEmbedArrayCollection(PersistentCollection $collection) {
        $data = $collection->getData();
        if (count($data) === 0) {
            return;
        }

        if (is_array($data)) {
            $mapping  = $collection->getMapping();
            $useKey   = (bool)($mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY);
            $owner    = $collection->getOwner();
            $metadata = $this->dm->getClassMetadata($mapping['targetClass']);
            foreach ($data as $key => $v) {
                $document = $metadata->newInstance();
                $data     = $this->hydratorFactory->hydrate($document, $v);
                $this->uow->registerManaged($document, null, $data);
                $this->uow->setParentAssociation($document, $mapping, $owner, $mapping['name'] . '.' . $key);
                if ($useKey) {
                    $collection->set($key, $document);
                } else {
                    $collection->add($document);
                }
            }
        }
    }

    private function loadLinkArrayCollection(PersistentCollection $collection) {
        $rows = $collection->getData();
        if (count($rows) === 0) {
            return;
        }
        $mapping = $collection->getMapping();
        $useKey  = (bool)($mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY);
        if (is_scalar(reset($rows))) {
            $query = new Query(array_values($rows));
            if ($useKey) {
                $keys = array_flip($rows);
            }
            $results = $this->binding->execute($query)->getResult();
        } else {
            $results = $rows;
        }

        foreach ($results as $key => $data) {
            $document = $this->uow->getOrCreateDocument($data);
            if ($useKey) {
                $key = isset($keys) ? $keys[$data->{'@rid'}] : $key;
                $collection->set($key, $document);
            } else {
                $collection->add($document);
            }
        }
    }

    /**
     * Creates or fills a single document object from an query result.
     *
     * @param object $result   The query result.
     * @param object $document The document object to fill, if any.
     * @param array  $hints    Hints for document creation.
     *
     * @return object The filled and managed document object or NULL, if the query result is empty.
     */
    private function createDocument($result, $document = null, array $hints = []) {
        if ($result === null) {
            return null;
        }

        if ($document !== null) {
            $this->uow->registerManaged($document, $result->{'@rid'}, null);
        }

        return $this->uow->getOrCreateDocument($result, $hints);
    }
}