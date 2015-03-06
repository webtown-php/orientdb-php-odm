<?php

namespace Doctrine\ODM\OrientDB\Persisters;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Types\Type;
use Doctrine\ODM\OrientDB\UnitOfWork;

class PersistenceBuilder
{
    /**
     * The DocumentManager instance.
     *
     * @var DocumentManager
     */
    private $dm;

    /**
     * The UnitOfWork instance.
     *
     * @var UnitOfWork
     */
    private $uow;

    /**
     * Initializes a new PersistenceBuilder instance.
     *
     * @param DocumentManager $dm
     * @param UnitOfWork      $uow
     */
    public function __construct(DocumentManager $dm, UnitOfWork $uow) {
        $this->dm  = $dm;
        $this->uow = $uow;
    }

    /**
     * Prepares the array that is ready to be inserted to mongodb for a given object document.
     *
     * @param object $document
     *
     * @return array $insertData
     */
    public function prepareInsertData($document) {
        $class = $this->dm->getClassMetadata(get_class($document));
        $changeset = $this->uow->getDocumentChangeSet($document);

        $insertData = [];
        foreach ($class->fieldMappings as $mapping) {

            // @Link(List|Set|Map) @EmbedMany are inserted later
            if ($mapping['association'] & ClassMetadata::TO_MANY) {
                continue;
            }

            $new = isset($changeset[$mapping['fieldName']][1]) ? $changeset[$mapping['fieldName']][1] : null;

            // Don't store null values unless nullable === true
            if ($new === null && $mapping['nullable'] === false) {
                continue;
            }

            $value = null;
            if ($new !== null) {
                // @Field, @String, @Date, etc.
                if ( ! isset($mapping['association'])) {
                    $value = Type::getType($mapping['type'])->convertToDatabaseValue($new);

                    // @ReferenceOne
                } elseif (isset($mapping['association']) && $mapping['association'] & ClassMetadata::LINK) {
                    if ($mapping['isInverseSide']) {
                        continue;
                    }

                    $rmd = $this->dm->getClassMetadata(get_class($new));


                    $value = $rmd->getIdentifierValue($new);

                    // @EmbedOne
                } elseif (isset($mapping['association']) && $mapping['association'] & ClassMetadata::EMBED) {
                    $value = $this->prepareEmbeddedDocumentValue($mapping, $new);
                }
            }

            $insertData[$mapping['name']] = $value;
        }

        // add discriminator if the class has one
        if (isset($class->discriminatorField)) {
            $insertData[$class->discriminatorField] = isset($class->discriminatorValue)
                ? $class->discriminatorValue
                : $class->name;
        }

        return $insertData;
    }
}