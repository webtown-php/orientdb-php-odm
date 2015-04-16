<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\LockException;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\ODMOrientDbException;
use Doctrine\ODM\OrientDB\Persister\CommitOrderCalculator;
use Doctrine\ODM\OrientDB\Persister\PersisterInterface;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\HttpBindingInterface;
use Doctrine\OrientDB\Types\Type;

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
        $co  = CommitOrderCalculator::getCommitOrderFromMetadata($this->metadataFactory);
        $set = self::prepareCommitOrderArray($co);

        $this->executeInserts($uow, $set);
        $this->executeUpdateDeletes($uow, $set);
    }

    private function executeInserts(UnitOfWork $uow, array &$set) {
        $this->references = [];
        $queryWriter      = new QueryWriter();

        $ordered = self::orderByType($uow->getDocumentInsertions(), $set);
        if (empty($ordered)) {
            return;
        }

        $docs = [];
        foreach ($ordered as $class => $inserts) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor($class);
            if ($md->isEmbeddedDocument()) {
                continue;
            }

            switch (true) {
                case $md->isVertex():
                    foreach ($inserts as $oid => $doc) {
                        $id   = $this->createDocVarReference($doc);
                        $data = $this->prepareData($md, $uow, $doc);
                        $queryWriter->addCreateVertexQuery($id->toValue(), $md->getOrientClass(), $data);
                        $docs[] = [$id, $doc, $md];
                    }
                    continue;

                case $md->isEdge():
                    foreach ($inserts as $oid => $doc) {
                        $id   = $this->createDocVarReference($doc);
                        $data = $this->prepareData($md, $uow, $doc);
                        if (!property_exists($data, 'in')) {
                        }

                        if (!property_exists($data, 'out')) {
                        }
                        $in  = $data->in;
                        $out = $data->out;
                        unset($data->in, $data->out);
                        $queryWriter->addCreateEdgeQuery($id->toValue(), $md->getOrientClass(), $out, $in, $data);
                        $docs[] = [$id, $doc, $md];
                    }
                    continue;

                default:
                    foreach ($inserts as $oid => $doc) {
                        $id   = $this->createDocVarReference($doc);
                        $data = $this->prepareData($md, $uow, $doc);
                        $queryWriter->addInsertQuery($id->toValue(), $md->getOrientClass(), $data);
                        $docs[] = [$id, $doc, $md];
                    }
                    continue;
            }
        }

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }

        $parts = [];
        foreach ($docs as $k => list ($v)) {
            $parts [] = sprintf('n%s : %s', $k, $v);
        }
        $queries [] = sprintf('return { %s }', implode(', ', $parts));
        $rids       = $this->executeQueries($queries);
        foreach ($rids as $k => $rid) {
            if (isset($docs[$k])) {
                /** @var ClassMetadata $md */
                list ($_, $doc, $md) = $docs[$k];
                unset($docs[$k]);
                $md->reflFields[$md->identifier]->setValue($doc, $rid);
                $data = $uow->getDocumentActualData($doc);
                $uow->registerManaged($doc, $rid, $data);
                $uow->raisePostPersist($md, $doc);
            }
        }

        if (!empty($docs)) {
            // we didn't receive info for all inserted documents
            throw new SQLBatchException('missing RIDs for one or more inserted documents');
        }

        $this->references = [];
    }

    private function executeUpdateDeletes(UnitOfWork $uow, array &$set) {
        $queryWriter = new QueryWriter();

        $updatedDocs = $this->processDocumentUpdates($queryWriter, $uow, $set);
        $this->processCollectionDeletions($queryWriter, $uow);
        $this->processCollectionUpdates($queryWriter, $uow);
        $removedDocs = $this->processDocumentDeletions($queryWriter, $uow, $set);

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }

        if (!empty($updatedDocs)) {
            $parts = [];
            foreach ($updatedDocs as $k => list ($v)) {
                $parts [] = sprintf('n%s : %s', $k, $v);
            }
            $queries [] = sprintf('return { %s }', implode(', ', $parts));
        }

        $updated = $this->executeQueries($queries);
        foreach ($updated as $k => $val) {
            if (isset($updatedDocs[$k]) && $val instanceof \stdClass) {
                /** @var ClassMetadata $md */
                list ($_, $doc, $md) = $updatedDocs[$k];
                unset($updatedDocs[$k]);
                $md->reflFields[$md->version]->setValue($doc, $val->value);
                $uow->raisePostUpdate($md, $doc);
            }
        }

        if (!empty($updatedDocs)) {
            // we didn't receive info for all updated documents
            throw LockException::lockFailed(array_map(function ($arg) {
                return $arg[1];
            }, $updatedDocs));
        }

        /**
         * @var object        $doc
         * @var ClassMetadata $md
         */
        foreach ($removedDocs as list($doc, $md)) {
            $uow->raisePostRemove($md, $doc);
        }
    }

    /**
     * @param QueryWriter $queryWriter
     * @param UnitOfWork  $uow
     * @param array       $set
     *
     * @return array
     */
    private function &processDocumentUpdates(QueryWriter $queryWriter, UnitOfWork $uow, array &$set) {
        $docs    = [];
        $ordered = self::orderByType($uow->getDocumentUpdates(), $set);
        if (empty($ordered)) {
            return $docs;
        }

        foreach ($ordered as $class => $updates) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor($class);
            if ($md->isEmbeddedDocument()) {
                continue;
            }

            foreach ($updates as $oid => $doc) {
                $id      = $this->createDocVarReference($doc);
                $rid     = $uow->getDocumentRid($doc);
                $data    = $this->prepareData($md, $uow, $doc);
                $version = null;
                if ($md->version) {
                    $version = $md->reflFields[$md->version]->getValue($doc);
                }
                $queryWriter->addUpdateQuery($rid, $data, $id->toValue(), $version);
                $docs[] = [$id, $doc, $md];
            }
        }

        return $docs;
    }

    private function &processDocumentDeletions(QueryWriter $queryWriter, UnitOfWork $uow, array &$set) {
        $ordered = self::orderByType($uow->getDocumentDeletions(), $set);
        if (empty($ordered)) {
            return self::$EMPTY;
        }

        $docs = [];
        foreach ($ordered as $class => $deletes) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor($class);
            if ($md->isEmbeddedDocument()) {
                continue;
            }

            if ($md->isVertex()) {
                foreach ($deletes as $doc) {
                    $queryWriter->addDeleteVertexQuery($uow->getDocumentRid($doc));
                    $docs[] = [$doc, $md];
                }
            } else {
                foreach ($deletes as $doc) {
                    $queryWriter->addDeleteQuery($uow->getDocumentRid($doc));
                    $docs[] = [$doc, $md];
                }
            }
        }

        return $docs;
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
                case ClassMetadata::LINK_BAG_EDGE:
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
                case ClassMetadata::LINK_BAG_EDGE:
                    if (!$assoc['indirect']) {
                        // nothing to do, as insert / update / delete is handled by cascade operations
                        continue;
                    }

                    if ($assoc['direction'] === 'out') {
                        $rids = [$ownerRef, null];
                        $pos  = 1;
                    } else {
                        $rids = [null, $ownerRef];
                        $pos  = 0;
                    }

                    // add new edges
                    foreach ($coll->getInsertDiff() as $key => $related) {
                        $rids[$pos] = $this->getDocReference($related);
                        $queryWriter->addCreateLightEdgeQuery($assoc['oclass'], $rids);
                    }
                    foreach ($coll->getDeleteDiff() as $related) {
                        $rids[$pos] = $this->getDocReference($related);
                        $queryWriter->addDeleteEdgeQuery($assoc['oclass'], $rids);
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
        foreach ($results[0] as $k => $v) {
            if ($k[0] === 'n') {
                $res[substr($k, 1)] = $v;
            }
        }

        return $res;
    }

    public static function &prepareCommitOrderArray(array $co) {
        $set = [];
        foreach ($co as $class) {
            $set[$class] = [];
        }

        return $set;
    }

    private static $EMPTY = [];

    /**
     * @param array $docs
     * @param array $co
     *
     * @return array
     */
    public static function &orderByType(array $docs, array $co) {
        if (empty($docs)) {
            return self::$EMPTY;
        }

        foreach ($docs as $oid => $doc) {
            $class            = ClassUtils::getClass($doc);
            $co[$class][$oid] = $doc;
        }

        $res = array_filter($co, function (&$v) {
            return !empty($v);
        });

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
     * @throws ODMOrientDbException
     */
    public function prepareData(ClassMetadata $class, UnitOfWork $uow, $document) {
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
            throw new ODMOrientDbException('missing reference');
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
}