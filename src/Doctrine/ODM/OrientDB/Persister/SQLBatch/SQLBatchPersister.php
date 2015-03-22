<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\LockException;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\OrientDBException;
use Doctrine\ODM\OrientDB\Persister\CommitOrderCalculator;
use Doctrine\ODM\OrientDB\Persister\PersisterInterface;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\HttpBindingInterface;

class SQLBatchPersister implements PersisterInterface
{
    /**
     * @var \Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * @var HttpBindingInterface
     */
    private $binding;

    /**
     * @var DocReference[]
     */
    private $references;

    /**
     * @var QueryWriter
     */
    private $writer;

    /**
     * @var int
     */
    private $varId = 0;

    public function __construct(ClassMetadataFactory $mdf, HttpBindingInterface $binding) {
        $this->metadataFactory = $mdf;
        $this->binding         = $binding;
    }

    /**
     * @inheritdoc
     */
    public function process(UnitOfWork $uow) {
        $this->executeInserts($uow);
        $this->executeUpdateDeletes($uow);
    }

    private function executeInserts(UnitOfWork $uow) {
        $this->references = [];
        $this->writer     = $queryWriter = new QueryWriter();

        $co      = CommitOrderCalculator::getCommitOrderFromMetadata($this->metadataFactory);
        $ordered = self::orderByType($uow->getDocumentInsertions(), $co);

        $docs = [];
        foreach ($ordered as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument()) {
                continue;
            }
            $id   = $this->createDocVarReference($doc);
            $data = $this->prepareData($md, $uow, $doc);

            if ($md->isVertex()) {
                $queryWriter->addCreateVertexQuery($id->toValue(), $md->getOrientClass(), $data);
            } else {
                $queryWriter->addInsertQuery($id->toValue(), $md->getOrientClass(), $data);
            }
            $docs[] = [$id, $doc, $md];
        }

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }

        $parts = [];
        foreach ($docs as $k => list ($v)) {
            $parts [] = sprintf('d%s : %s', $k, $v);
        }
        $queries [] = sprintf('return { %s }', implode(', ', $parts));
        $rids       = $this->executeQueries($queries);
        foreach ($rids as $k => $rid) {
            if (isset($docs[$k])) {
                /** @var ClassMetadata $md */
                list ($_, $doc, $md) = $docs[$k];
                unset($docs[$k]);
                $md->setFieldValue($doc, $md->getRidPropertyName(), $rid);
                $data = $uow->getDocumentActualData($doc);
                $uow->registerManaged($doc, $rid, $data);
            }
        }

        if (!empty($docs)) {
            // we didn't receive info for all inserted documents
            throw new SQLBatchException('missing RIDs for one or more inserted documents');
        }

        $this->references = [];
    }

    private function executeUpdateDeletes(UnitOfWork $uow) {
        $queryWriter = new QueryWriter();

        $docs = [];
        foreach ($uow->getDocumentUpdates() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument()) {
                continue;
            }

            $id      = $this->createDocVarReference($doc);
            $rid     = $uow->getDocumentRid($doc);
            $data    = $this->prepareData($md, $uow, $doc);
            $version = null;
            if ($md->version) {
                $version = $md->getFieldValue($doc, $md->version);
            }
            $queryWriter->addUpdateQuery($rid, $data, $id->toValue(), $version);
            $docs[] = [$id, $doc, $md];
        }

        $this->processCollectionDeletions($queryWriter, $uow);
        $this->processCollectionUpdates($queryWriter, $uow);

        foreach ($uow->getDocumentDeletions() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument()) {
                continue;
            }

            if ($md->isVertex()) {
                $queryWriter->addDeleteVertexQuery($uow->getDocumentRid($doc));
            } else {
                $queryWriter->addDeleteQuery($uow->getDocumentRid($doc));
            }
        }

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }

        if (!empty($docs)) {
            $parts = [];
            foreach ($docs as $k => list ($v)) {
                $parts [] = sprintf('d%s : %s', $k, $v);
            }
            $queries [] = sprintf('return { %s }', implode(', ', $parts));
        }

        $updates = $this->executeQueries($queries);
        foreach ($updates as $k => $val) {
            if (isset($docs[$k]) && $val instanceof \stdClass) {
                /** @var ClassMetadata $md */
                list ($_, $doc, $md) = $docs[$k];
                unset($docs[$k]);
                $md->setFieldValue($doc, $md->version, $val->value);
            }
        }

        if (!empty($docs)) {
            // we didn't receive info for all inserted documents
            throw LockException::lockFailed(array_map(function ($arg) {
                return $arg[1];
            }, $docs));
        }
    }

    private function processCollectionDeletions(QueryWriter $queryWriter, UnitOfWork $uow) {
        foreach ($uow->getCollectionDeletions() as $coll) {
            $assoc = $coll->getMapping();
            if (isset($assoc['embedded'])) {
                continue;
            }

            $owner     = $coll->getOwner();
            $ownerRef  = strval($this->getDocReference($owner));
            $fieldName = $assoc['fieldName'];

            switch ($assoc['association']) {
                case ClassMetadata::LINK_BAG:
                    $queryWriter->addDeleteEdgeCollectionQuery($assoc['oclass'], $assoc['direction'], $ownerRef);
                    continue;

                case ClassMetadata::LINK_MAP:
                    $queryWriter->addCollectionMapClearQuery($ownerRef, $fieldName);
                    continue;

                default:
                    $queryWriter->addCollectionClearQuery($ownerRef, $fieldName);
                    continue;
            }

        }
    }
    private function processCollectionUpdates(QueryWriter $queryWriter, UnitOfWork $uow) {
        foreach ($uow->getCollectionUpdates() as $coll) {
            $assoc = $coll->getMapping();
            if (isset($assoc['embedded'])) {
                continue;
            }

            $owner     = $coll->getOwner();
            $ownerRef  = strval($this->getDocReference($owner));
            $fieldName = $assoc['fieldName'];

            switch ($assoc['association']) {
                case ClassMetadata::LINK_BAG:
                    if ($assoc['direction'] === 'out') {
                        $rids = [$ownerRef, null];
                        $pos  = 1;
                    } else {
                        $rids = [null, $ownerRef];
                        $pos  = 0;
                    }

                    if ($assoc['indirect']) {
                        // add new edges
                        foreach ($coll->getInsertDiff() as $key => $related) {
                            $rids[$pos] = $this->getDocReference($related);
                            $queryWriter->addCreateEdgeQuery($assoc['oclass'], $rids);
                        }
                        foreach ($coll->getDeleteDiff() as $related) {
                            $rids[$pos] = $this->getDocReference($related);
                            $queryWriter->addDeleteEdgeQuery($assoc['oclass'], $rids);
                        }
                    } else {
                        throw new \Exception('RelatedToVia updates not yet implemented');
                    }
                    continue;

                case ClassMetadata::LINK_MAP:
                    foreach ($coll->getInsertDiff() as $key => $doc) {
                        $queryWriter->addCollectionMapPutQuery($ownerRef, $fieldName, $key, $uow->getDocumentRid($doc));
                    }

                    foreach ($coll->getDeleteDiff() as $key => $doc) {
                        $queryWriter->addCollectionMapDelQuery($ownerRef, $fieldName, $key);
                    }
                    continue;

                default:
                    $rids = array_map(function ($doc) use ($uow) {
                        return $uow->getDocumentRid($doc);
                    }, $coll->getInsertDiff());
                    if (count($rids) > 0) {
                        $queryWriter->addCollectionAddQuery($ownerRef, $fieldName, implode(',', $rids));
                    }
                    $rids = array_map(function ($doc) use ($uow) {
                        return $uow->getDocumentRid($doc);
                    }, $coll->getDeleteDiff());
                    if (count($rids) > 0) {
                        $queryWriter->addCollectionDelQuery($ownerRef, $fieldName, implode(',', $rids));
                    }
                    continue;
            }

        }
    }

    /**
     * @param array $queries
     *
     * @return array
     * @throws SQLBatchException
     */
    private function executeQueries(array $queries) {
        $batch   = [
            'transaction' => true,
            'operations'  => [
                [
                    'type'     => 'script',
                    'language' => 'sql',
                    'script'   => $queries
                ]
            ]
        ];
        $results = $this->binding->batch(json_encode($batch))->getData()->result;
        if (!is_array($results) || count($results) !== 1) {
            throw new SQLBatchException('unexpected response from server when executing batch request');
        }

        $res = [];
        foreach (get_object_vars($results[0]) as $k => $v) {
            if ($k[0] === 'd') {
                $res[substr($k, 1)] = $v;
            }
        }

        return $res;
    }

    /**
     * @param array $docs
     * @param array $co
     *
     * @return array
     */
    private static function orderByType(array $docs, array $co) {
        if (count($docs) < 2) {
            return $docs;
        }

        $groups = [];
        foreach ($docs as $oid => $doc) {
            $class              = ClassUtils::getClass($doc);
            $pos                = array_search($class, $co);
            $groups[$pos][$oid] = $doc;
        }

        ksort($groups);

        $res = [];
        foreach ($groups as $group) {
            $res = array_merge($res, $group);
        }

        return $res;
    }

    /**
     * Prepares the array that is ready to be inserted to mongodb for a given object document.
     *
     * @param ClassMetadata $class
     * @param UnitOfWork    $uow
     * @param object        $document
     *
     * @return \stdClass $insertData
     * @throws OrientDBException
     */
    public function prepareData(ClassMetadata $class, UnitOfWork $uow, $document, $isInsert = false) {
        $insertData = new \stdClass();

        if ($class->isEmbeddedDocument()) {
            $insertData->{'@type'}  = 'd';
            $insertData->{'@class'} = $class->getOrientClass();
            $cs                     = $uow->getDocumentActualData($document);
        } else {
            $cs = $uow->getDocumentChangeSet($document);
            array_Walk($cs, function (&$val) {
                $val = $val[1];
            });
        }

        $mappings = &$class->fieldMappings;
        foreach ($cs as $name => $new) {
            $mapping = isset($mappings[$name]) ? $mappings[$name] : null;
            if ($mapping === null) {
                // don't store arbitrary values for now
                continue;
            }

            // Don't store null values unless nullable === true
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }

            $value = null;
            if ($new !== null) {

                switch (true) {
                    // @Property
                    case !isset($mapping['association']):
                        $value = Type::getType($mapping['type'])->convertToDatabaseValue($new);
                        break;

                    case $mapping['association'] & ClassMetadata::LINK:
                        $value = $this->getDocReference($new);
                        break;

                    case $mapping['association'] & ClassMetadata::LINK_MANY:
                        // initialize the link collection
                        if ($mapping['association'] & ClassMetadata::LINK_MAP) {
                            $value = new \stdClass();
                        } else {
                            $value = [];
                        }
                        break;

                    case $mapping['association'] & ClassMetadata::EMBED:
                        /** @var ClassMetadata $rmd */
                        $rmd = $this->metadataFactory->getMetadataFor(get_class($new));

                        $value = $this->prepareData($rmd, $uow, $new);
                        break;

                    case $mapping['association'] & ClassMetadata::EMBED_MANY:
                        $value = [];
                        if ($mapping['association'] & ClassMetadata::EMBED_MAP) {
                            foreach ($new as $k => $item) {
                                /** @var ClassMetadata $rmd */
                                $rmd       = $this->metadataFactory->getMetadataFor(get_class($item));
                                $value[$k] = $this->prepareData($rmd, $uow, $item);
                            }
                        } else {
                            foreach ($new as $k => $item) {
                                /** @var ClassMetadata $rmd */
                                $rmd     = $this->metadataFactory->getMetadataFor(get_class($item));
                                $value[] = $this->prepareData($rmd, $uow, $item);
                            }
                        }
                        break;

                }

            }

            $insertData->{$mapping['name']} = $value;
        }

        return $insertData;
    }

    private function getDocReference($ref) {
        $oid = spl_object_hash($ref);
        if (isset($this->references[$oid])) {
            return $this->references[$oid];
        }

        /** @var ClassMetadata $rmd */
        $rmd = $this->metadataFactory->getMetadataFor(get_class($ref));

        $rid = $rmd->getIdentifierValue($ref);
        if ($rid === null) {
            throw new OrientDBException('missing reference');
        }

        static $rid_type;
        if (!isset($rid_type)) {
            $rid_type = Type::getType('rid');
        }

        return $rid_type->convertToDatabaseValue($rid);
    }

    /**
     * @param object $ref
     *
     * @return DocReference
     */
    private function createDocVarReference($ref) {
        $oid = spl_object_hash($ref);
        if (!isset($this->references[$oid])) {
            $this->references[$oid] = DocReference::create('$d' . $this->varId++);
        }

        return $this->references[$oid];
    }

    /**
     * @param string $id
     *
     * @return DocReference
     */
    private function createVar($id) {
        if (!isset($this->references[$id])) {
            $this->references[$id] = DocReference::create('$d' . $this->varId++);
        }

        return $this->references[$id];
    }
}