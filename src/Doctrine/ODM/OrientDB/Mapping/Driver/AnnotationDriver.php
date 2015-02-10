<?php

namespace Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Document;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Embedded;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedDocument;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedList;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedMap;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedPropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\EmbeddedSet;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Link;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkList;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkMap;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkPropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\LinkSet;
use Doctrine\ODM\OrientDB\Mapping\Annotations\Property;
use Doctrine\ODM\OrientDB\Mapping\Annotations\PropertyBase;
use Doctrine\ODM\OrientDB\Mapping\Annotations\RID;
use Doctrine\ODM\OrientDB\Mapping\MappingException;

class AnnotationDriver extends AbstractAnnotationDriver
{
    protected $entityAnnotationClasses = [
        Document::class         => 1,
        EmbeddedDocument::class => 2,
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
        $classAnnotations = $this->reader->getClassAnnotations($metadata->getReflectionClass());
        if (count($classAnnotations) === 0) {
            throw MappingException::classIsNotAValidEntityOrMappedSuperClass($className);
        }

        if (count($classAnnotations) > 1){
            $documentAnnots = [];
            foreach ($classAnnotations as $annot) {

                foreach ($this->entityAnnotationClasses as $annotClass => $i) {
                    if ($annot instanceof $annotClass) {
                        $documentAnnots[$i] = $annot;
                        continue 2;
                    }
                }
            }
            // find the winning document annotation
            ksort($documentAnnots);
            $docAnnotation = reset($documentAnnots);
        } else {
            $docAnnotation = end($classAnnotations);
        }

        $metadata->setOrientClass($docAnnotation->class);
        if ($docAnnotation instanceof EmbeddedDocument) {
            $metadata->isEmbeddedDocument = true;
        }

        foreach ($metadata->getReflectionClass()->getProperties() as $refProperty) {
            $pas = $this->reader->getPropertyAnnotations($refProperty);
            foreach ($pas as $ann) {
                $mapping = [
                    'fieldName' => $refProperty->getName(),
                    'nullable'  => false,
                ];

                if ($ann instanceof PropertyBase) {
                    if (!$ann->name) {
                        $ann->name = $refProperty->getName();
                    }
                    $mapping['name'] = $ann->name;
                }

                switch (true) {
                    case $ann instanceof Property:
                        $mapping = $this->propertyToArray($refProperty->getName(), $ann);
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
                        $mapping = $this->linkToArray($mapping, $ann);
                        $metadata->mapLink($mapping);
                        continue;

                    case $ann instanceof LinkList:
                        $mapping = $this->linkToArray($mapping, $ann);
                        $metadata->mapLinkList($mapping);
                        continue;

                    case $ann instanceof LinkSet:
                        $mapping = $this->linkToArray($mapping, $ann);
                        $metadata->mapLinkSet($mapping);
                        continue;

                    case $ann instanceof LinkMap:
                        $mapping = $this->linkToArray($mapping, $ann);
                        $metadata->mapLinkMap($mapping);
                        continue;

                    case $ann instanceof Embedded:
                        $mapping = $this->embeddedToArray($mapping, $ann);
                        $metadata->mapEmbedded($mapping);
                        continue;

                    case $ann instanceof EmbeddedList:
                        $mapping = $this->embeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedList($mapping);
                        continue;

                    case $ann instanceof EmbeddedSet:
                        $mapping = $this->embeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedSet($mapping);
                        continue;

                    case $ann instanceof EmbeddedMap:
                        $mapping = $this->embeddedToArray($mapping, $ann);
                        $metadata->mapEmbeddedMap($mapping);
                        continue;
                }
            }
        }

        if (!$metadata->isEmbeddedDocument && !$metadata->getRidPropertyName()) {
            throw MappingException::missingRid($metadata->getName());
        }
    }

    public function &propertyToArray($fieldName, Property $prop) {
        $mapping = [
            'fieldName' => $fieldName,
            'name'      => $prop->name,
            'type'      => $prop->type,
            'nullable'  => $prop->nullable,
            'cast'      => $prop->getCast(),
        ];

        return $mapping;
    }

    private function &linkToArray(&$mapping, LinkPropertyBase $link) {
        $mapping['cascade']     = $link->cascade;
        $mapping['targetClass'] = $link->targetClass;

        return $mapping;
    }

    private function &embeddedToArray(&$mapping, EmbeddedPropertyBase $embed) {
        $mapping['targetClass'] = $embed->targetClass;

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