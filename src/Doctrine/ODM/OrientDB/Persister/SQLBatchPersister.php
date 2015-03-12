<?php

namespace Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\HttpBindingInterface;

class SQLBatchPersister implements PersisterInterface
{
    /**
     * @var \Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var HttpBindingInterface
     */
    protected $binding;

    public function __construct(ClassMetadataFactory $mdf, HttpBindingInterface $binding) {
        $this->metadataFactory = $mdf;
        $this->binding         = $binding;
    }

    /**
     * @inheritdoc
     */
    public function process(UnitOfWork $uow) {
        $queryWriter = new QueryWriter();

        $docs = [];
        foreach ($uow->getDocumentInsertions() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md = $this->metadataFactory->getMetadataFor(get_class($doc));
            if ($md->isEmbeddedDocument) {
                continue;
            }

            $data            = $this->prepareData($md, $uow, $doc);
            $position        = $queryWriter->addInsertQuery($oid, $md->getOrientClass(), $data);
            $docs[$position] = [$doc, $md];
        }

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
        $batch   = array(
            'transaction' => true,
            'operations'  => [
                [
                    'type'     => 'script',
                    'language' => 'sql',
                    'script'   => $queryWriter->getQueries()
                ]
            ]
        );
        $results = $this->binding->batch(json_encode($batch))->getData()->result;

        // set the RID on the created documents
        foreach ($results as $position => $result) {
            if (isset($docs[$position])) {
                /** @var ClassMetadata $metadata */
                list ($document, $metadata) = $docs[$position];
                $rid = $result->{'@rid'};
                $metadata->setFieldValue($document, $metadata->getRidPropertyName(), $rid);
                $data = $uow->getDocumentActualData($document);
                $uow->registerManaged($document, $rid, $data);
            }
        }
    }

    /**
     * Prepares the array that is ready to be inserted to mongodb for a given object document.
     *
     * @param ClassMetadata $class
     * @param UnitOfWork    $uow
     * @param object        $document
     *
     * @return \stdClass $insertData
     */
    public function prepareData(ClassMetadata $class, UnitOfWork $uow, $document) {
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

        foreach ($class->fieldMappings as $mapping) {
            $new = isset($cs[$mapping['fieldName']]) ? $cs[$mapping['fieldName']] : null;

            // Don't store null values unless nullable === true
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }

            $value = null;
            if ($new !== null) {
                // @Property
                if (!isset($mapping['association'])) {
                    $value = Type::getType($mapping['type'])->convertToDatabaseValue($new);

                    // @Link
                } elseif ($mapping['association'] & ClassMetadata::LINK) {
                    if (!$mapping['isOwningSide']) {
                        continue;
                    }

                    /** @var ClassMetadata $rmd */
                    $rmd = $this->metadataFactory->getMetadataFor(get_class($new));

                    $value = $rmd->getIdentifierValue($new);

                } elseif ($mapping['association'] & ClassMetadata::EMBED) {
                    /** @var ClassMetadata $rmd */
                    $rmd = $this->metadataFactory->getMetadataFor(get_class($new));

                    $value = $this->prepareData($rmd, $uow, $new);
                } elseif ($mapping['association'] & ClassMetadata::EMBED_MANY) {
                    $value = [];
                    if ($mapping['association'] & ClassMetadata::EMBED_MAP) {
                        foreach ($new as $k => $item) {
                            /** @var ClassMetadata $rmd */
                            $rmd = $this->metadataFactory->getMetadataFor(get_class($item));
                            $value[$k] = $this->prepareData($rmd, $uow, $item);
                        }
                    } else {
                        foreach ($new as $k => $item) {
                            /** @var ClassMetadata $rmd */
                            $rmd = $this->metadataFactory->getMetadataFor(get_class($item));
                            $value[] = $this->prepareData($rmd, $uow, $item);
                        }
                    }


                }
            }

            $insertData->{$mapping['name']} = $value;
        }

        return $insertData;
    }
}