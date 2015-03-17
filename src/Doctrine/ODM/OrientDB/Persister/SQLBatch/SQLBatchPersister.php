<?php

namespace Doctrine\ODM\OrientDB\Persister\SQLBatch;

use Doctrine\Common\Util\ClassUtils;
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

    private function executeUpdateDeletes(UnitOfWork $uow) {
        $queryWriter = new QueryWriter();

        foreach ($uow->getDocumentUpdates() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument) {
                continue;
            }

            $rid  = $uow->getDocumentRid($doc);
            $data = $this->prepareData($md, $uow, $doc);
            $queryWriter->addUpdateQuery($rid, $data);
        }

        foreach ($uow->getCollectionUpdates() as $coll) {
            $assoc = $coll->getMapping();
            if (isset($assoc['embedded'])) {
                continue;
            }

            $isMap     = boolval($assoc['association'] & ClassMetadata::LINK_MAP);
            $fieldName = $assoc['fieldName'];
            $owner     = $coll->getOwner();
            $ownerRef  = strval($this->getDocReference($owner));

            if ($isMap) {
                foreach ($coll->getInsertDiff() as $key => $doc) {
                    $queryWriter->addCollectionMapPutQuery($ownerRef, $fieldName, $key, $uow->getDocumentRid($doc));
                }

                foreach ($coll->getDeleteDiff() as $key => $doc) {
                    $queryWriter->addCollectionMapDelQuery($ownerRef, $fieldName, $key);
                }
            } else {
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
            }
        }

        foreach ($uow->getDocumentDeletions() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument) {
                continue;
            }

            $queryWriter->addDeleteQuery($uow->getDocumentRid($doc));
        }

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }

        $batch = [
            'transaction' => true,
            'operations'  => [
                [
                    'type'     => 'script',
                    'language' => 'sql',
                    'script'   => $queries
                ]
            ]
        ];
        $this->binding->batch(json_encode($batch));
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
            if ($md->isEmbeddedDocument) {
                continue;
            }
            $id   = $this->createDocVarReference($doc);
            $data = $this->prepareData($md, $uow, $doc);

            $queryWriter->addInsertQuery($id->toValue(), $md->getOrientClass(), $data);
            $docs [] = [$id, $doc, $md];
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
            throw new SQLBatchException('unexpected response from server when inserting new documents');
        }

        $rids = (array)$results[0];
        foreach ($rids as $var => $rid) {
            $k = substr($var, 1);
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

        if ($class->isEmbeddedDocument) {
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

    private function getRid($ref) {
        /** @var ClassMetadata $rmd */
        $rmd = $this->metadataFactory->getMetadataFor(get_class($ref));

        $rid = $rmd->getIdentifierValue($ref);
        if ($rid === null) {
            throw new OrientDBException('document has not been persisted');
        }
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
     * @param $ref
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