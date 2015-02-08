<?php

namespace test\Doctrine\ODM\OrientDB\Mapping\Driver;

use Doctrine\Common\Persistence\Mapping\Driver\AnnotationDriver as AbstractAnnotationDriver;
use Doctrine\ODM\OrientDB\Mapping\ClassMetadata;
use test\PHPUnit\TestCase;

abstract class AbstractMappingDriverTest extends TestCase {

    /**
     * @return AbstractAnnotationDriver
     */
    abstract protected function _loadDriver();

    public function createClassMetadata($entityClassName)
    {
        $mappingDriver = $this->_loadDriver();

        $class = new ClassMetadata($entityClassName);
        $class->initializeReflection(new \Doctrine\Common\Persistence\Mapping\RuntimeReflectionService);
        $mappingDriver->loadMetadataForClass($entityClassName, $class);

        return $class;
    }

    public function testLoadMapping()
    {
        $entityClassName = User::class;
        return $this->createClassMetadata($entityClassName);
    }

    /**
     * @depends testLoadMapping
     *
     * @param ClassMetadata $class
     *
     * @return ClassMetadata
     */
    public function testOClassName($class)
    {
        $this->assertEquals('OUser', $class->getOrientClass());

        return $class;
    }
}

/**
 * @Document(class="OUser")
 * @HasLifecycleCallbacks
 */
class User
{
    /**
     * @RID
     **/
    public $id;

    /**
     * @PrePersist
     */
    public function doStuffOnPrePersist()
    {
    }

    /**
     * @PrePersist
     */
    public function doOtherStuffOnPrePersistToo() {
    }

    /**
     * @PostPersist
     */
    public function doStuffOnPostPersist()
    {

    }

    public static function loadMetadata(ClassMetadata $metadata)
    {

    }
}