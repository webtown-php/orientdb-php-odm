<?php

namespace Doctrine\ODM\OrientDB\Persister;

use Doctrine\Common\EventManager;
use Doctrine\ODM\OrientDB\Collections\PersistentCollection;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Hydrator\HydratorFactoryInterface;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Query\QueryBuilder;

class DocumentPersister
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * @var \Doctrine\OrientDB\Binding\HttpBindingInterface
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

    public function refresh($rid, $document) {
        $this->load($rid, '*:0', $document);
    }

    /**
     * @param object $document
     *
     * @return bool
     */
    public function exists($document) {
        $rid = $this->class->getIdentifierValue($document);

        return $this->binding->documentExists($rid);
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
        $result = $this->binding->getDocument($rid, $fetchPlan);
        if ($result->isValid()) {
            return $this->createDocument($result->getData(), $document);
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
                $this->loadEmbedCollection($collection);
                break;

            case ClassMetadata::LINK_LIST:
            case ClassMetadata::LINK_SET:
            case ClassMetadata::LINK_MAP:
                $this->loadReferenceCollection($collection);
                break;

            case ClassMetadata::LINK_BAG_EDGE:
                if ($mapping['indirect']) {
                    $this->loadIndirectReferenceCollection($collection);
                } else {
                    $this->loadReferenceCollection($collection);

                }
                break;
        }
    }

    private function loadEmbedCollection(PersistentCollection $collection) {
        $data = $collection->getData();
        if (count($data) === 0) {
            return;
        }

        if (is_array($data)) {
            $mapping  = $collection->getMapping();
            $useKey   = boolval($mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY);
            $metadata = $this->dm->getClassMetadata($mapping['targetDoc']);
            foreach ($data as $key => $v) {
                $document = $metadata->newInstance();
                $data     = $this->hydratorFactory->hydrate($document, $v);
                $this->uow->registerManaged($document, null, $data);
                if ($useKey) {
                    $collection->set($key, $document);
                } else {
                    $collection->add($document);
                }
            }
        }
    }

    private function loadReferenceCollection(PersistentCollection $collection) {
        $rows = $collection->getData();
        if (count($rows) === 0) {
            return;
        }
        $mapping = $collection->getMapping();
        $useKey  = boolval($mapping['association'] & ClassMetadata::ASSOCIATION_USE_KEY);
        if (is_string(reset($rows))) {
            if ($useKey) {
                $keys = array_flip($rows);
            }
            $results = $this->binding->query(sprintf('SELECT FROM [%s]', implode(',', array_values($rows))))->getResult();
        } else {
            // data was already loaded
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

    static private function &extractVertexes(array $rows, $prop) {
        $results = [];
        foreach ($rows as $row) {
            $results[$row->{'@rid'}] = $row->{$prop};
        }

        return $results;
    }

    private function loadIndirectReferenceCollection(PersistentCollection $collection) {
        $rows = $collection->getData();
        if (count($rows) === 0) {
            return;
        }
        $mapping = $collection->getMapping();
        $prop    = $mapping['direction'] === 'in' ? 'out' : 'in';

        $rids     = [];
        $results  = [];
        $edgeRids = [];
        foreach ($rows as $row) {
            if (is_string($row)) {
                // edge RID
                $edgeRids[] = $row;
                continue;
            }

            // edge is loaded, so we
            $edgeRid = $row->{'@rid'};
            if (is_string($row->{$prop})) {
                $rids[$row->{$prop}][] = $edgeRid;
            } else {
                $results[$edgeRid] = $rows->{$prop};
            }
        }

        // load edges and their immediate children (*:1)
        if ($edgeRids) {
            $loaded  = $this->binding->query(sprintf('SELECT FROM [%s]', implode(',', $edgeRids)), -1, '*:1')
                                     ->getResult();
            $results = array_merge($results, self::extractVertexes($loaded, $prop));
        }

        if ($rids) {
            $loaded = $this->binding->query(sprintf('SELECT FROM [%s]', implode(',', array_keys($rids))))
                                    ->getResult();
            foreach ($loaded as $row) {
                $rid = $row->{'@rid'};
                foreach ($rids[$rid] as $edge) {
                    $results[$edge] = $row;
                }
            }
        }

        foreach ($results as $key => $data) {
            $document = $this->uow->getOrCreateDocument($data);
            $collection->set($key, $document);
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