<?php

namespace Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\Annotations\AbstractProperty;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Document;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Link;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkList;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkMap;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkSet;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Property;
use Doctrine\ODM\OrientDB\Mapping\Annotations\RID;
use Doctrine\ODM\OrientDB\Mapping\MappingException;

class AnnotationDriver extends AbstractAnnotationDriver
{
    protected $entityAnnotationClasses = [
        Document::class => 1
    ];
    /**
     * Registers annotation classes to the common registry.
     *
     * This method should be called when bootstrapping your application.
     */
    public static function registerAnnotationClasses() {
        AnnotationRegistry::registerFile(__DIR__ . '/../Annotations/DoctrineAnnotations.php');
    }

    /**
     * Loads the metadata for the specified class into the provided container.
     *
     * @param string        $className
     * @param ClassMetadata $metadata
     *
     * @throws MappingException
     */
    public function loadMetadataForClass($className, ClassMetadata $metadata) {
        /** @var \Doctrine\ODM\OrientDB\Mapping\ClassMetadata $metadata */
        /** @var Document $classAnnotation */
        $classAnnotation = $this->reader->getClassAnnotation($metadata->getReflectionClass(), Document::class);
        if (empty($classAnnotation)) {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }
        $metadata->setOrientClass($classAnnotation->class);

        foreach ($metadata->getReflectionClass()->getProperties() as $refProperty) {
            $pas = $this->reader->getPropertyAnnotations($refProperty);
            foreach ($pas as $ann) {
                $mapping = [
                    'fieldName' => $refProperty->getName(),
                ];

                if ($ann instanceof AbstractProperty) {
                    if (!$ann->name) {
                        $ann->name = $refProperty->getName();
                    }
                    $mapping['name'] = $ann->name;
                }

                switch (true) {
                    case $ann instanceof Property:
                        $mapping = $this->columnToArray($refProperty->getName(), $ann);
                        $metadata->mapField($mapping);
                        continue;

                    case $ann instanceof RID:
                        $metadata->setIdentifier($refProperty->getName());
                        $mapping = [
                            'fieldName' => $refProperty->getName(),
                            'name'      => '@rid',
                            'type'      => 'string',
                            'nullable'  => false,
                            'cast'      => 'string'
                        ];
                        $metadata->mapField($mapping);
                        continue;

                    case $ann instanceof Link:
                        $metadata->mapLink($mapping);
                        continue;

                    case $ann instanceof LinkList:
                        $metadata->mapLinkList($mapping);
                        continue;

                    case $ann instanceof LinkSet:
                        $metadata->mapLinkSet($mapping);
                        continue;

                    case $ann instanceof LinkMap:
                        $metadata->mapLinkMap($mapping);
                        continue;
                }
            }
        }

        if (!$metadata->getRidPropertyName()) {
            throw MappingException::missingRid($metadata->getName());
        }
    }

    public function &columnToArray($fieldName, Property $prop) {
        $mapping = [
            'fieldName' => $fieldName,
            'name'      => $prop->name,
            'type'      => $prop->type,
            'nullable'  => $prop->isNullable(),
            'cast'      => $prop->getCast(),
        ];

        return $mapping;
    }

    /**
     * Factory method for the Annotation Driver
     *
     * @param array|string $paths
     * @param Reader       $reader
     *
     * @return AnnotationDriver
     */
    public static function create($paths = array(), Reader $reader = null) {
        if ($reader === null) {
            $reader = new AnnotationReader();
        }

        return new self($reader, $paths);
    }
}