<?php

namespace Doctrine\ODM\OrientDB\Persister;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\HttpBindingInterface;

class SQLBatchPersister implements PersisterInterface
{
    /**
     * @var DocumentManager;
     */
    private $dm;

    /**
     * @var \Doctrine\ODM\OrientDB\Mapping\ClassMetadataFactory
     */
    protected $metadataFactory;

    /**
     * @var \Doctrine\OrientDB\Binding\BindingInterface
     */
    protected $binding;

    protected $queryDocumentMap = [];

    public function __construct(DocumentManager $dm) {
        $this->dm              = $dm;
        $this->metadataFactory = $dm->getMetadataFactory();
        $this->binding         = $dm->getBinding();
    }

    /**
     * @inheritdoc
     */
    public function process(UnitOfWork $uow) {
        $queryWriter = new QueryWriter();

        $docs = [];
        foreach ($uow->getDocumentInsertions() as $oid => $doc) {
            /** @var ClassMetadata $md */
            $md              = $this->metadataFactory->getMetadataFor(get_class($doc));
            $data            = $this->prepareData($uow, $doc);
            $position        = $queryWriter->addInsertQuery($oid, $md->getOrientClass(), $data);
            $docs[$position] = [$doc, $md];
        }

        foreach ($uow->getDocumentUpdates() as $oid => $doc) {
            $rid = $uow->getDocumentRid($doc);
            /** @var ClassMetadata $md */
            $data = $this->prepareData($uow, $doc);
            $queryWriter->addUpdateQuery($rid, $data);
        }

        foreach ($uow->getDocumentDeletions() as $oid => $doc) {
            $queryWriter->addDeleteQuery($uow->getDocumentRid($doc));
        }

        $queries = $queryWriter->getQueries();
        if (!$queries) {
            // nothing to do
            return;
        }
        if ($this->binding instanceof HttpBindingInterface) {
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


        } else {
            throw new \Exception('Only HttpBindingInterface is supported.');
        }

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
     * @param UnitOfWork $uow
     * @param object     $document
     *
     * @return array $insertData
     */
    public function prepareData(UnitOfWork $uow, $document) {
        /** @var ClassMetadata $class */
        $class = $this->metadataFactory->getMetadataFor(get_class($document));
        $cs    = $uow->getDocumentChangeSet($document);

        $insertData = [];
        foreach ($class->fieldMappings as $mapping) {

            $new = isset($cs[$mapping['fieldName']][1]) ? $cs[$mapping['fieldName']][1] : null;

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
                } elseif (isset($mapping['association']) && $mapping['association'] & ClassMetadata::LINK) {
                    if ($mapping['isInverseSide']) {
                        continue;
                    }

                    /** @var ClassMetadata $rmd */
                    $rmd = $this->metadataFactory->getMetadataFor(get_class($new));

                    $value = $rmd->getIdentifierValue($new);

                }
            }

            $insertData[$mapping['name']] = $value;
        }

        return $insertData;
    }
}