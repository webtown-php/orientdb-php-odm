<?php

namespace Doctrine\ODM\OrientDB\Persistence;

use Doctrine\Common\Inflector\Inflector;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ODM\OrientDB\Caster\ReverseCaster;
use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\UnitOfWork;
use Doctrine\OrientDB\Binding\HttpBindingInterface;

/**
 * Class DocumentPersister
 *
 * @package    Doctrine\ODM
 * @subpackage OrientDB
 * @author     Tamás Millián <tamas.millian@gmail.com>
 */
class SQLBatchPersister implements PersisterInterface
{
    protected $metadataFactory;
    protected $caster;
    protected $binding;
    protected $queryDocumentMap = array();

    public function __construct(DocumentManager $dm) {
        $this->metadataFactory = $dm->getMetadataFactory();
        $this->binding         = $dm->getBinding();
        $this->caster          = new ReverseCaster();
    }


    /**
     * Processes the changeSet and maps the RIDs back to new documents
     * so it can be used in userland.
     *
     * @param ChangeSet $changeSet
     *
     * @throws \Exception
     */
    public function process(ChangeSet $changeSet) {
        $queryWriter = new QueryWriter();
        foreach ($changeSet->getInserts() as $identifier => $item) {
            /** @var ClassMetadata $metadata */
            $metadata                          = $this->metadataFactory->getMetadataFor(ClassUtils::getClass($item['document']));
            $fields                            = $this->getCastedFields($item['changes']);
            $position                          = $queryWriter->addInsertQuery($identifier, $metadata->getOrientClass(), $fields);
            $this->queryDocumentMap[$position] = array('document' => $item['document'], 'metadata' => $metadata);
        }

        foreach ($changeSet->getUpdates() as $identifier => $item) {
            $fields = $this->getCastedFields($item['changes']);
            $queryWriter->addUpdateQuery($identifier, $fields);
        }

        foreach ($changeSet->getRemovals() as $identifier => $item) {
            $metadata = $this->metadataFactory->getMetadataFor(ClassUtils::getClass($item['document']));
            $queryWriter->addDeleteQuery($identifier, $metadata->getOrientClass());
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
            if (isset($this->queryDocumentMap[$position])) {
                $map      = $this->queryDocumentMap[$position];
                $document = $map['document'];
                $metadata = $map['metadata'];

                $metadata->setFieldValue($document, $metadata->getRidPropertyName(), $result->{'@rid'});
            }
        }
    }


    /**
     * Casts the fields and returns an array mapped fieldname => value
     *
     * @param array $changes
     *
     * @return array
     */
    protected function getCastedFields(array $changes) {
        $castedChanges = [];
        foreach ($changes as $change) {
            $castedChanges[$change['field']] = $this->castProperty($change['mapping'], $change['value']);
        }

        return $castedChanges;
    }

    /**
     * Casts a value according to how it was annotated.
     *
     * @param  array $mapping
     * @param  mixed $propertyValue
     *
     * @return mixed
     */
    protected function castProperty($mapping, $propertyValue) {
        $method = 'cast' . Inflector::classify($mapping['type']);

        $this->caster->setValue($propertyValue);
        $this->caster->setProperty('annotation', $mapping);

        return $this->caster->$method();
    }

}