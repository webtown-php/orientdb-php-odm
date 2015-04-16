<?php

namespace Doctrine\ODM\OrientDB\Tools;

use Doctrine\ODM\OrientDB\DocumentManager;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\OrientDB\Schema\OClass;
use Doctrine\OrientDB\Schema\OSchema;
use Doctrine\OrientDB\Schema\Visitor\CreateSchemaSqlVisitor;

/**
 * The SchemaTool is a tool to create/drop/update database schemas based on
 * <code>ClassMetadata</code> class descriptors.
 */
class SchemaTool
{
    /**
     * @var DocumentManager
     */
    private $_dm;

    public function __construct(DocumentManager $dm) {
        $this->_dm = $dm;
    }

    /**
     * Creates the OrientDB schema for the given array of ClassMetadata instances.
     *
     * @param ClassMetadata[] $classes
     */
    public function createSchema(array $classes) {
        $sql = $this->getCreateSchemaSql($classes);

        $b      = $this->_dm->getBinding();
        $batch  = [
            'transaction' => false,
            'operations'  => [
                [
                    'type'     => 'script',
                    'language' => 'sql',
                    'script'   => $sql
                ]
            ]
        ];
        $result = $b->batch(json_encode($batch));
        $res    = $result->getInnerResponse();
        if (in_array($res->getStatusCode(), [200, 204])) {
            return;
        }
    }

    /**
     * Detects instances of ClassMetadata that don't need to be processed in the SchemaTool context.
     *
     * @param ClassMetadata $class
     * @param array         $processedClasses
     *
     * @return bool
     */
    private function processingNotRequired($class, array $processedClasses) {
        return (
            isset($processedClasses[$class->name]) ||
            $class->isMappedSuperclass()
        );
    }

    /**
     * @param ClassMetadata[] $classes
     *
     * @return string[]
     */
    public function getCreateSchemaSql(array $classes) {
        $schema = $this->getSchemaFromMetadata($classes);

        $vis = new CreateSchemaSqlVisitor();
        $schema->accept($vis);

        return $vis->getSql();
    }

    /**
     * @param ClassMetadata[] $classes
     *
     * @return OSchema
     */
    public function getSchemaFromMetadata(array $classes) {
        // Reminder for processed classes, used for hierarchies
        $processedClasses = [];

        $children = [];
        $schema = new OSchema();
        foreach ($classes as $class) {
            if ($this->processingNotRequired($class, $processedClasses)) {
                continue;
            }
            $oclass = $schema->createClass($class->getOrientClass());
            if (!empty($class->parentClasses)) {
                $children[$oclass->getName()] = [$oclass, end($class->parentClasses)];
            }
            $this->gatherProperties($class, $oclass);
            if ($class->isVertex()) {
                // may declare RelatedTo edges
                $this->gatherEdgeClasses($schema, $class);
            }
        }

        /**
         * @var OClass $child
         * @var ClassMetadata $parentClass
         */
        foreach ($children as list($child, $parentClass)) {
            $parent = $this->_dm->getClassMetadata($parentClass);
            $child->setSuperClass($schema->getClass($parent->orientClass));
        }

        return $schema;
    }

    private function gatherEdgeClasses(OSchema $schema, ClassMetadata $class) {
        foreach ($class->associationMappings as $mapping) {
            if (
                isset($mapping['inherited']) ||
                !($mapping['association'] & ClassMetadata::LINK_BAG_EDGE) ||
                !$mapping['indirect'] ||
                $mapping['oclass'] === ClassMetadata::EDGE_BASE_CLASS
            ) {
                continue;
            }

            $oclass = $mapping['oclass'];
            if (!$schema->hasClass($oclass)) {
                $schema->createEdgeClass($oclass);
            }
        }

    }

    private function gatherProperties(ClassMetadata $class, OClass $oclass) {
        foreach ($class->fieldMappings as $mapping) {
            if (isset($mapping['inherited'])) {
                continue;
            }

            $name = $mapping['name'];

            if (OSchema::isSystemProperty($name)) {
                continue;
            }

            if (isset($mapping['association'])) {
                continue;
            }

            $options = [
                'readonly'  => $mapping['readonly'],
                'mandatory' => $mapping['mandatory'],
                'min'       => $mapping['min'],
                'max'       => $mapping['max'],
                'regexp'    => $mapping['regexp'],
                'notNull'   => !$mapping['nullable'],
                //'collate'   => $mapping['collate'],
            ];

            $oclass->addProperty($name, $mapping['type'], $options);
        }

        foreach ($class->associationMappings as $mapping) {
            if (isset($mapping['inherited'])) {
                continue;
            }

            if (isset($mapping['direction'])) {
                // no in / outgoing references
                continue;
            }

            $name = $mapping['name'];
            if (isset($mapping['targetDoc'])) {
                $linkedClass = $this->_dm->getClassMetadata($mapping['targetDoc'])->orientClass;
            } else {
                $linkedClass = null;
            }

            $options = [
                'notNull'     => !$mapping['nullable'],
                'linkedClass' => $linkedClass,
            ];

            $oclass->addProperty($name, $mapping['type'], $options);
        }

    }
}