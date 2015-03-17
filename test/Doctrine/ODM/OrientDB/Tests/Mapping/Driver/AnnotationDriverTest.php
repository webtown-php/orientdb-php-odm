<?php

namespace Doctrine\ODM\OrientDB\Tests\Mapping\Driver;


use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use Doctrine\ODM\OrientDB\Mapping\Driver\AnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\MappingException;

class AnnotationDriverTest extends AbstractMappingDriverTest
{

    public function testLoadMetadataForNonEntityThrowsException() {
        $cm = new ClassMetadata('stdClass');
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $reader           = new \Doctrine\Common\Annotations\AnnotationReader(new \Doctrine\Common\Cache\ArrayCache());
        $annotationDriver = new AnnotationDriver($reader);

        $this->setExpectedException(MappingException::class);
        $annotationDriver->loadMetadataForClass('stdClass', $cm);
    }

    public function testColumnWithMissingTypeDefaultsToString() {
        $cm = new ClassMetadata(ColumnWithoutType::class);
        $cm->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $annotationDriver = $this->_loadDriver();

        $annotationDriver->loadMetadataForClass('Doctrine\Tests\ORM\Mapping\InvalidColumn', $cm);
        $this->assertEquals('string', $cm->fieldMappings['id']['type']);
    }

    /**
     * @inheritdoc
     */
    protected function _loadDriver() {
        return $this->createAnnotationDriver();
    }

    protected function _ensureIsLoaded($entityClassName) {
        new $entityClassName;
    }
}

/**
 * @Document(class="ColumnWithoutType")
 */
class ColumnWithoutType
{
    /** @RID */
    public $id;

    /** @Version */
    public $version;
}

